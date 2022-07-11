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

header("Access-Control-Allow-Origin: *");

require_once('../../components.php');

if(!defined('OB_STREAM_API') || OB_STREAM_API!==true || (empty($_GET['category_id']) && empty($_GET['genre_id']) && empty($_GET['media_id'])))
{
  http_response_code(404);
  die();
}

$return = [
  'genres'=>[],
  'media'=>[]
];

$db = OBFDB::get_instance();

$category_id = $_GET['category_id'] ?? null;
$genre_id = $_GET['genre_id'] ?? null;
$media_id = $_GET['media_id'] ?? null;

$limit = $_GET['limit'] ?? null;
$offset = $_GET['offset'] ?? null;

$category_id = trim($category_id);
$genre_id = trim($genre_id);
$media_id = trim($media_id);

// get metadata columns to add
$metadata = $db->get('media_metadata_columns');
foreach($metadata as $metadata_column) $db->what('media_metadata.'.$metadata_column['name'], 'metadata_'.$metadata_column['name']);

// add other columns
$db->what('media.id');
$db->what('media.artist');
$db->what('media.title');
$db->what('media.album');
$db->what('media.year');
$db->what('media.language_id');
$db->what('media_languages.name', 'language_name');
$db->what('media.category_id');
$db->what('media_categories.name', 'category_name');
$db->what('media.genre_id');
$db->what('media_genres.name', 'genre_name');
$db->what('media.country_id');
$db->what('media_countries.name', 'country_name');
$db->what('media.comments');
$db->what('media.type');
$db->what('media.format');
$db->what('media.file_location');
$db->what('media.stream_version');

// public approved unarchived only
$db->where('media.status', 'public');
$db->where('media.is_approved', 1);
$db->where('media.is_archived', 0);

// get media by category or genre
if($category_id) $db->where('media.category_id', $category_id);
elseif($genre_id) $db->where('media.genre_id', $genre_id);
elseif($media_id) $db->where('media.id', $media_id);

// handle limit and offset
$limit = max(0, (int) $limit);
$offset = max(0, (int) $offset);
if($limit)
{
  $db->limit($limit);
  $db->offset($offset);
}

// the rest
$db->leftjoin('media_metadata', 'media.id', 'media_metadata.media_id');
$db->leftjoin('media_categories', 'media.category_id', 'media_categories.id');
$db->leftjoin('media_genres', 'media.genre_id', 'media_genres.id');
$db->leftjoin('media_languages', 'media.language_id', 'media_languages.id');
$db->leftjoin('media_countries', 'media.country_id', 'media_countries.id');
$db->calc_found_rows();
$media = $db->get('media');
$return['media_total'] = $db->found_rows();

foreach($media as $item)
{
  $item['download'] = 'download.php?media_id='.$item['id'];

  // stream available
  if($item['stream_version'])
  {
    $item['mime']='application/x-mpegURL';
    $item['stream']='streams/'.$item['file_location'][0].'/'.$item['file_location'][1].'/'.$item['id'].'/'.($item['type']=='audio' ? 'audio.m3u8' : 'prog_index.m3u8');
  }

  // if image, set stream to download URL. TODO srcset.
  if($item['type']=='image')
  {
    $item['mime'] = $item['type'].'/'.$item['format'];
    $item['stream'] = $item['download'];
  }

  $thumbnail_file = 'streams/'.$item['file_location'][0].'/'.$item['file_location'][1].'/'.$item['id'].'/thumb.jpg';
  if(file_exists(OB_CACHE.'/'.$thumbnail_file))
  {
    $item['thumbnail'] = $thumbnail_file;
  }
  else
  {
    $thumbnail_file = 'thumbnails/'.$item['file_location'][0].'/'.$item['file_location'][1].'/'.$item['id'].'.jpg';
    if(file_exists(OB_CACHE.'/'.$thumbnail_file))
    {
      $item['thumbnail'] = 'thumbnail.php?id='.$item['id'];
    }
  }

  $item_return = [
    'id'=>$item['id'],
    'artist'=>$item['artist'],
    'title'=>$item['title'],
    'album'=>$item['album'],
    'year'=>$item['year'],
    'language_id'=>$item['language_id'],
    'language_name'=>$item['language_name'],
    'category_id'=>$item['category_id'],
    'category_name'=>$item['category_name'],
    'genre_id'=>$item['genre_id'],
    'genre_name'=>$item['genre_name'],
    'country_id'=>$item['country_id'],
    'country_name'=>$item['country_name'],
    'comments'=>$item['comments'],
    'type'=>$item['type'],
    'mime'=>$item['mime'] ?? null,
    'stream'=>$item['stream'] ?? null,
    'thumbnail'=>$item['thumbnail'] ?? null,
    'download'=>$item['download']
  ];

  // add caption file if we have it
  if(file_exists(__DIR__.'/captions/'.$item['id'].'.vtt')) $item_return['captions']='tools/stream/captions/'.$item['id'].'.vtt';
  else $item_return['captions']=false;

  foreach($metadata as $metadata_column) $item_return['metadata_'.$metadata_column['name']] = $item['metadata_'.$metadata_column['name']];

  // get our genre from this media item if we are just selecting a single media item. used below.
  if($media_id) $genre_id = $item['genre_id'];

  $return['media'][] = $item_return;
}

// get a list of genres. if getting media by genre or media id, this will only return the single genre.
if($genre_id || $category_id)
{
  if($category_id) $db->where('media_category_id', $category_id);
  else $db->where('id', $genre_id);
  $genres = $db->get('media_genres');

  foreach($genres as $genre)
  {
    $return['genres'][] = ['id'=>$genre['id'], 'name'=>$genre['name'], 'description'=>$genre['description']];
  }
}



echo json_encode($return);
