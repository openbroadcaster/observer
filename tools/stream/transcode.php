<?php

/*
    Copyright 2012-2020 OpenBroadcaster, Inc.

    This file is part of OpenBroadcaster Server.

    OpenBroadcaster Server is free software: you can redistribute it and/or modify
    it under the terms of the GNU Affero General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    OpenBroadcaster Server is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Affero General Public License for more details.

    You should have received a copy of the GNU Affero General Public License
    along with OpenBroadcaster Server.  If not, see <http://www.gnu.org/licenses/>.
*/

// this tool transcodes audio and video into HTTP streaming files in order to use HTTP streaming
// this tool also generates thumbnails
// media must be public, approved, and not archived

// run from command line, output list of missing media files.
if (php_sapi_name()!='cli') {
    die('cli only');
}

// echo PHP_EOL.'*** Stream transcode script start: '.date('r').PHP_EOL.PHP_EOL;

// some settings
define('OB_STREAM_VERSION', 1); // update this to re-transcode, etc.
define('OB_THUMBNAIL_VERSION', 1); // update this to regenerate thumbnails

// db init
require(__DIR__.'/../../components.php');
$db = OBFDB::get_instance();

// make sure our media & cache directorys are defined
if (!defined('OB_MEDIA') || !defined('OB_CACHE') || !is_dir(OB_MEDIA) || !is_dir(OB_CACHE)) {
    die('Configuration invalid; please complete OB setup.'.PHP_EOL);
}

// get media without stream information
$db->query('
  SELECT stream_version, thumbnail_version, file_location, filename, id, type, duration
  FROM media 
  WHERE
    status="public" 
    AND is_approved=1 
    AND is_archived=0 
    AND (
      stream_version IS NULL 
      OR stream_version < '.OB_STREAM_VERSION.'
      OR thumbnail_version IS NULL
      OR thumbnail_version < '.OB_THUMBNAIL_VERSION.'
    )
');

$media = $db->assoc_list();

// handle stream/transcode first
foreach ($media as $item) {
    // skip item if up to date
    if ($item['stream_version']>=OB_STREAM_VERSION) {
        continue;
    }

    $filename = OB_MEDIA.'/'.$item['file_location'][0].'/'.$item['file_location'][1].'/'.$item['filename'];
    $output_dir = OB_CACHE.'/streams/'.$item['file_location'][0].'/'.$item['file_location'][1].'/'.$item['id'];
    $transcode_return = null;

    // create output directory if needed
    if (!is_dir($output_dir) && !mkdir($output_dir, 0775, true)) {
        die('Unable to create output directory; check cache directory permissions.'.PHP_EOL);
    }

    // output directory should be empty, but don't delete thumbnail.
    $output_dir_files = glob($output_dir.'/*');
    foreach ($output_dir_files as $file) {
        if (is_file($file) && !preg_match('/\/thumb\.jpg$/', $file)) {
            var_dump($file);
            unlink($file);
        }
    }

    // transcode video
    if ($item['type']=='video') {
        $info = json_decode(shell_exec('avprobe -show_format -show_streams -of json '.escapeshellarg($filename)));

        // if didn't get info properly, ignore this item.
        if (!$info) {
            continue;
        }

        // search for video stream to get video width/height
        $streams = $info->streams;

        $source_width = null;
        $source_height = null;
        $audiocopy = false;
        $videocopy = false;

        foreach ($streams as $stream) {
            if ($stream->codec_type=='video' && $stream->codec_name=='h264') {
                $videocopy = true;
            }
            if ($stream->codec_type=='audio' && $stream->codec_name=='aac') {
                $audiocopy = true;
            }

            if ($stream->codec_type=='video' && !empty($stream->width) && !empty($stream->height) && (empty($source_width) || empty($source_height))) {
                $source_width = $stream->width;
                $source_height = $stream->height;
            }
        }

        // disable audio/video copy for now since we need to better account for quality settings
        $audiocopy = false;
        $videocopy = false;

        // if we don't have a video size, ignore this item.
        if ($source_width==null) {
            continue;
        }

        // scaling info: https://trac.ffmpeg.org/wiki/Scaling

        // create LD stream

        // only video copy codec if source is LD
        if ($videocopy && $source_width<=640 && $source_height<=360) {
            $videoargs = '-bsf:v h264_mp4toannexb -vcodec copy';
        } else {
            // tall video vs wide video
            if (640/360 > $source_width/$source_height) {
                $scale = '-2:360';
            } else {
                $scale = '640:-2';
            }

            // $videoargs = '-vf "scale=\'min(640,iw)\':\'min(360,ih)\':force_original_aspect_ratio=decrease" -b:v 800k -maxrate 856k ';
            $videoargs = '-vf "scale='.$scale.'" -b:v 800k -maxrate 856k ';
        }
        if ($audiocopy) {
            $audioargs = '-acodec copy';
        } else {
            $audioargs = '-b:a 96k';
        }


        $command = 'avconv -i '.escapeshellarg($filename).' '.$videoargs.' '.$audioargs.' -hls_list_size 0 -hls_time 6 -strict -2 '.escapeshellarg($output_dir.'/360p.m3u8').' -hide_banner';
        echo PHP_EOL.$command.PHP_EOL.PHP_EOL;
        passthru($command, $transcode_return);

        $prog_index = '#EXTM3U'.PHP_EOL.'#EXT-X-VERSION:3'.PHP_EOL.'#EXT-X-STREAM-INF:BANDWIDTH=800000,RESOLUTION=640x360'.PHP_EOL.'360p.m3u8'; // TODO get proper bitrate?

        // create HD stream if previous command successful and source dimensions exceed low definition dimensions
        if ($transcode_return===0 && ($source_width>640 || $source_height>360)) {
            // update video args. don't copy codec if >1080p. audio args stay the same.
            if ($videocopy && $source_width<=1920 && $source_height<=1080) {
                $videoargs = '-bsf:v h264_mp4toannexb -vcodec copy';
            } else {
                // tall video vs wide video
                if (1920/1080 > $source_width/$source_height) {
                    $scale = '-2:1080';
                } else {
                    $scale = '1920:-2';
                }

                // $videoargs = '-vf "scale=\'min(1920,iw)\':\'min(1080,ih)\':force_original_aspect_ratio=decrease" -b:v 5000k -maxrate 5350k';
                $videoargs = '-vf "scale='.$scale.'" -b:v 5000k -maxrate 5350k';
            }

            if ($audiocopy) {
                $audioargs = '-acodec copy';
            } else {
                $audioargs = "-b:a 192k";
            }

            $command = 'avconv -i '.escapeshellarg($filename).' '.$videoargs.' '.$audioargs.' -hls_list_size 0 -hls_time 6 -strict -2 '.escapeshellarg($output_dir.'/1080p.m3u8').' -hide_banner';
            echo PHP_EOL.$command.PHP_EOL.PHP_EOL;
            passthru($command, $transcode_return);

            $prog_index .= PHP_EOL.'#EXT-X-STREAM-INF:BANDWIDTH=5000000,RESOLUTION=1920x1080'.PHP_EOL.'1080p.m3u8'; // TODO get proper bitrate?
        }

        // if successful transcode, create prog_index.m3u8.
        if ($transcode_return===0) {
            file_put_contents($output_dir.'/prog_index.m3u8', $prog_index);
        }
    }

    // transcode audio
    elseif ($item['type']=='audio') {
        $command = 'avconv -i '.escapeshellarg($filename).' -map 0:a -hls_list_size 0 -hls_time 6 -strict -2 '.escapeshellarg($output_dir.'/audio.m3u8').' -hide_banner';
        echo PHP_EOL.$command.PHP_EOL.PHP_EOL;
        passthru($command, $transcode_return);
    }

    // if not audio/video, nothing to do, so we can report success and have the stream_version updated.
    else {
        $transcode_return = 0;
    }

    // if transcode successful, update db with data
    if ($transcode_return===0) {
        // actually, since subtitles are not in the m3u8 file, we can generate all the m3u8 files here and don't need to keep data in the database (just version).
        // javascript will ask for stream info and get m3u8 file and text tracks and generate html from that.
        $db->where('id', $item['id']);
        $db->update('media', ['stream_version'=>OB_STREAM_VERSION]);
    }
}

// handle thumbnail creation next
// handle stream/transcode first
foreach ($media as $item) {
    // skip item if up to date
    if ($item['thumbnail_version']>=OB_THUMBNAIL_VERSION) {
        continue;
    }

    $input_file = OB_MEDIA.'/'.$item['file_location'][0].'/'.$item['file_location'][1].'/'.$item['filename'];
    $output_file = OB_CACHE.'/streams/'.$item['file_location'][0].'/'.$item['file_location'][1].'/'.$item['id'].'/thumb.jpg';

    $success = false;

    if ($item['type']=='video') {
        $duration = $item['duration'];
        if ($duration) {
            // for short videos, start at the beginning.
            // for long videos, start at 25%
            if ($duration<60) {
                $start = 0.00;
            } else {
                $start = $duration/4;
            }

            $start_hours = floor($start/3600);
            $start -= $start_hours*3600;
            $start_minutes = floor($start/60);
            $start -= $start_minutes*60;
            $start_seconds = $start;

            if ($start_hours<10) {
                $start_hours = '0'.$start_hours;
            }
            if ($start_minutes<10) {
                $start_minutes = '0'.$start_minutes;
            }
            if ($start_seconds<10) {
                $start_seconds= '0'.$start_seconds;
            }

            $start = $start_hours.':'.$start_minutes.':'.round($start_seconds, 2);

            // get unique dir name
            $tmp_dir = tempnam(sys_get_temp_dir(), 'ob_');

            // tempnam creates a file, unlink and make it a directory
            unlink($tmp_dir);
            mkdir($tmp_dir);

            // get 5 keyframes starting at 25% into the video.
            $command = 'avconv -ss '.escapeshellarg($start).' -i '.escapeshellarg($input_file).' -vf "select=eq(pict_type\,I), scale=w=300:h=300:force_original_aspect_ratio=decrease" -vsync vfr -vframes 5 '.escapeshellarg($tmp_dir.'/thumb%04d.jpg').' -hide_banner';
            passthru($command);

            // pick thumbnail with largest filesize
            $thumbs = scandir($tmp_dir);

            $thumb_size = 0;
            $thumb_selected = false;

            foreach ($thumbs as $thumb) {
                if (!preg_match('/^thumb/', $thumb)) {
                    continue;
                }
                if (filesize($tmp_dir.'/'.$thumb)>$thumb_size) {
                    $thumb_selected = $tmp_dir.'/'.$thumb;
                    $thumb_size = filesize($thumb_selected);
                }
            }

            if ($thumb_selected) {
                copy($thumb_selected, $output_file);
                $success = true;
            }

            // clean up
            $thumbs = scandir($tmp_dir);
            foreach ($thumbs as $thumb) {
                if (!preg_match('/^thumb/', $thumb)) {
                    continue;
                }
                unlink($tmp_dir.'/'.$thumb);
            }
            rmdir($tmp_dir);
        }
    } elseif ($item['type']=='audio') {
        $command = 'avconv -y -i '.escapeshellarg($input_file).' -vf "scale=w=300:h=300:force_original_aspect_ratio=decrease" '.escapeshellarg($output_file).' -hide_banner';
        echo PHP_EOL.$command.PHP_EOL.PHP_EOL;
        passthru($command);
        $success = true; // assume success, because will fail if no album art, but that's okay.
    } elseif ($item['type']=='image') {
        $command = 'avconv -y -i '.escapeshellarg($input_file).' -vf "scale=w=300:h=300:force_original_aspect_ratio=decrease" '.escapeshellarg($output_file).' -hide_banner';
        echo PHP_EOL.$command.PHP_EOL.PHP_EOL;
        $return = null;
        passthru($command, $return);
        if ($return===0) {
            $success = true;
        }
    } else {
        $success = true;
    }

    if ($success) {
        $db->where('id', $item['id']);
        $db->update('media', ['thumbnail_version'=>OB_THUMBNAIL_VERSION]);
    }
}
