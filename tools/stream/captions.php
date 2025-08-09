<?php

// Copyright 2012-2024 OpenBroadcaster, Inc.
// SPDX-License-Identifier: AGPL-3.0-or-later

require('../../components.php');

$db = OBFDB::get_instance();
$user = OBFUser::get_instance();
$auth_id = null;
$auth_key = null;

// are we logged in?
if (!empty($_COOKIE['ob_auth_id']) && !empty($_COOKIE['ob_auth_key'])) {
    $auth_id = $_COOKIE['ob_auth_id'];
    $auth_key = $_COOKIE['ob_auth_key'];
}

// authorize our user (from post data, cookie data, whatever.)
$user->auth($auth_id, $auth_key);

if (!$user->is_admin) {
    die('Please log in as an admin user before using this tool.');
}

$message = '';
function ob_caption_upload()
{
    global $message, $db;

    // make sure we have a form submit
    if (!isset($_POST['submit'])) {
        return;
    }

    // check media id
    $db->where('id', trim($_POST['media_id']));
    $db->where('type', 'video');
    $media = $db->get_one('media');

    if (!$media) {
        $message = 'Please enter a valid video media ID.';
        return;
    }

    // make sure we have a vtt file
    if (empty($_FILES['file']['size']) || strtolower(substr($_FILES['file']['name'], -4))!='.vtt') {
        $message = 'Please select a valid VTT caption file.';
        return;
    }

    move_uploaded_file($_FILES['file']['tmp_name'], __DIR__.'/captions/'.$media['id'].'.vtt');
    $message = 'Caption file uploaded successfully.';
}

ob_caption_upload();

?>
<html>
<head>
<title>OpenBroadcaster Captions Uploader</title>
</head>
<body>

<style>
td
{
  padding: 5px;
}

td:first-child
{
  text-align: right;
}

p
{
  color: #a00;
}
</style>


<h1>OpenBroadcaster Captions Uploader</h1>

<form method="post" action="captions.php" enctype="multipart/form-data">

<p><?=$message?></p>

<table>
<tr>
<td>Media ID: </td>
<td><input type="number" name="media_id"></td>
</tr>
<tr>
<td>VTT File: </td>
<td><input type="file" name="file"></td>
</tr>
<tr>
<td>&nbsp;</td>
<td><input type="submit" name="submit" value="Upload"></td>
</tr>
</table>

</form>