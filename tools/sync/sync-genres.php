<?php

if(php_sapi_name()!='cli') die('Command line tool only.');

header('Content-Type: application/json');
require_once('../../components.php');
require_once('extras/getid3/getid3/getid3.php');
$getID3 = new getID3;
$db = OBFDB::get_instance();
$models = OBFModels::get_instance();
$user_agent = 'OpenBroadcaster/'.trim(file_get_contents('VERSION'));

if(php_sapi_name()!='cli') die('Command line tool only.');

if(!defined('OB_SYNC_USERID') || !defined('OB_SYNC_SOURCE') || !defined('OB_ACOUSTID_KEY')) die('OB_SYNC_USERID, OB_SYNC_SOURCE, and OB_ACOUSTID_KEY must be defined in config.php.'.PHP_EOL);

// create thumbnail directory if needed
if(!file_exists(OB_CACHE.'/thumbnails'))
{
  if(!mkdir(OB_CACHE.'/thumbnails', 0755)) die('Unable to create thumbnail directory. Make sure the OB cache directory is writable.'.PHP_EOL);
}

while(true)
{
  echo 'getting items requiring genres'.PHP_EOL;

  $db->query('SELECT media_id as id, sync_releasegroup_id  FROM `media_metadata` WHERE sync_musicbrainz_raw is null and sync_releasegroup_id is not null and sync_releasegroup_id!="" order by media_id desc limit 25');
  $rows = $db->assoc_list();

  foreach($rows as $row)
  {
    usleep(500000);
    
    $genres = [];
    
    // get genres from musicbrainz
    $ch = curl_init('https://musicbrainz.org/ws/2/release-group/'.$row['sync_releasegroup_id'].'?inc=genres&fmt=json');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
    $musicbrainz_raw_response = curl_exec($ch);
    $http_response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if($http_response_code!=200 && $http_response_code!=404) { echo 'musicbrainz unexpected response code '. $http_response_code.', wait 5 seconds then skip.'.PHP_EOL; sleep(5); continue; }
    else echo 'musicbrainz http response code '.$http_response_code.PHP_EOL;
    curl_close($ch);
    $response = json_decode($musicbrainz_raw_response);
    if(!empty($response->genres) && is_array($response->genres)) foreach($response->genres as $genre)
    {
      $genres[] = $genre->name;
    }

    $db->where('media_id',$row['id']);
    $db->update('media_metadata',[
      'sync_musicbrainz_raw'=>$musicbrainz_raw_response
    ]);
    
    // update database with genres
    if(!empty($genres))
    {
      echo $row['id'].': '.implode(', ', $genres).PHP_EOL;
      $db->where('media_id',$row['id']);
      $db->update('media_metadata',[
        'genres'=>implode(',', $genres)
      ]);
      $db->where('media_id',$row['id']);
      $db->delete('media_metadata_tags');
      foreach($genres as $genre)
      {
        $db->insert('media_metadata_tags',['media_id'=>$row['id'], 'media_metadata_column_id'=>1, 'tag'=>$genre]);
      }
    }
  }

  echo PHP_EOL.'restarting script in 5 seconds'.PHP_EOL;
  sleep(5);
}
