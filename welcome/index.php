<?php

require('../components.php');
$models = OBFModels::get_instance();
$db = OBFDB::get_instance();
$db->where('name', 'client_login_message');
$result = $db->get_one('settings');
$welcome_message = $result ? $result['value'] : '';

if(is_file('VERSION')) $version = trim(file_get_contents('VERSION'));
else $version = 4;

$update_required = $models->updates('update_required');

?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
	<script src="//ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
	<script src="../extras/jquery.json.js"></script>
	<script src="welcome.js?v=<?=urlencode($version)?>"></script>
	<link href='//fonts.googleapis.com/css?family=Droid+Sans:400,700' rel='stylesheet' type='text/css'>
	<link rel="stylesheet" href="welcome.css?v=<?=urlencode($version)?>" type="text/css">
  <title>OpenBroadcaster</title>
</head>
<body>

<div id="container">

	<?php if($update_required) { ?>
	
		<div class="error">
			<h1>Update Required</h1>
			<p>A database update is required using one of the following methods:</p>
			<ul>
				<li><strong><a href="/updates">Web Browser</a></strong><br>You will need to have <span>OB_UPDATES_USER</span> and <span>OB_UPDATES_PW</span> set in config.php.</li>
				<li><strong>Command Line</strong><br>By running "php index.php run" in the /updates directory. Please note this functionality is currently experimental.</li></p>
		</div>
	
	<?php } else { ?>

		<div class="section" id="login">
			<?php include('login.php'); ?>
		</div>

		<div class="section" id="forgotpass" style="display: none;">
			<?php include('forgotpass.php'); ?>
		</div>

		<div class="section" id="newaccount" style="display: none;">
			<?php include('newaccount.php'); ?>
		</div>
		
		<p>Running version <?=$version?>.</p>
	
	<?php }  ?>

	<p>OpenBroadcaster is released under Affero GPL v3 and may be downloaded at <a href="https://openbroadcaster.com/observer">openbroadcaster.com</a>.  View&nbsp;<a href="https://openbroadcaster.com/observer_licence">license</a>.</p>

</div>

</body>
</html>
