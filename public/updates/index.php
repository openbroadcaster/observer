<?php

// Copyright 2012-2024 OpenBroadcaster, Inc.
// SPDX-License-Identifier: AGPL-3.0-or-later

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once('updates.php');

if (empty($_GET['run']) || $_GET['run'] != 1) {
    $run = false;
} else {
    $run = true;
}

?>
<html>
<head>
  <title>OpenBroadcaster Admin</title>

  <style>
  body
  {
    font-family: sans-serif;
  }

  h1
  {
    font-size: 22px;
  }

  h2
  {
    font-size: 16px;
    font-weight: bold;
  }

  p, td, li
  {
    font-size: 13px;
  }

  #container
  {
    max-width: 700px;
  }

  #check_table
  {
    border-collapse: separate;
  }

  #check_table td
  {
    padding: 5px;
  }

  #check_table td:first-child
  {
    font-weight: bold;
  }

  .check_error, .ob-update-error > div
  {
    background-color: #faa !important;
  }

  .check_warning, .ob-update-pending > div
  {
    background-color: #ffc !important;
  }

  .check_ok, .ob-update-success > div
  {
    background-color: #cfc !important;
  }

  .ob-updates
  {
    display: flex;
    flex-direction: column-reverse;
    font-size: 13px;
  }

  .ob-update
  {
    display: flex;
    align-items: stretch;
  }

  .ob-update p,
  .ob-update h2,
  .ob-update ul
  {
    margin-top: 0;
    margin-bottom: 0;
    font-size: 13px;
  }

  .ob-update li
  {
    font-size: 12px;
  }

  .ob-update ul
  {
    padding-left: 10px;
  }

  .ob-update li:not(:first-child)
  {
    margin-top: 10px;
  }

  .ob-update > div
  {
    background-color: #f3f3f3;
    margin: 1px;
    padding: 10px;
  }

  .ob-update-name
  {
    flex: 0 0 75px;
    display: flex;
  }

  .ob-update-description
  {
    flex: 1 1 auto;
  }

  .ob-update-status
  {
    flex: 0 0 75px;
    display: flex;
  }

  </style>
</head>

<body>

<div id="container">

<h1>OpenBroadcaster Install Checker</h1>

<table id="check_table">
<?php foreach ($u->checker_results as $result) { ?>
<tr class="check_<?php if ($result[2] == 0) {
    echo 'ok';
                 } elseif ($result[2] == 1) {
                     echo 'warning';
                 } else {
                     echo 'error';
                 } ?>">
  <td><?=htmlspecialchars($result[0])?></td>
  <td><?php if (is_array($result[1])) {
        $output = implode("\n\n", $result[1]);
        echo nl2br(htmlspecialchars($output));
      } else {
          echo nl2br(htmlspecialchars($result[1]));
      } ?></td>
</tr>
<?php } ?>
</table>

<?php if (!$u->checker_status) {
    ?><p style="font-weight: bold; padding-top: 25px;">Errors above (red) must be corrected before updates can be run.</p><?php
} ?>

<?php if ($u->checker_status) { ?>
  <h1>OpenBroadcaster Updates</h1>

    <?php $list = $u->updates(); // don't reverse, causes updates to run in wrong order.?>

  <p>This will complete database and other updates required when upgrading OpenBroadcaster.</p>

  <p style="font-weight: bold;">Please make a backup of the database before running updates!</p>

  <p style="text-align: center; font-size: 1.1em; padding: 10px 0;"><a href="index.php?run=1">Run Updates Now</a></p>


  <div class="ob-updates">
    <?php

    $has_error = false;

    foreach ($list as $update) {
        if (!$update->needed) {
            $status = 'Installed';
        } elseif ($run && !$has_error) {
            $result = $u->run($update);
            if ($result == true) {
                $status = 'Success';
            } else {
                $status = 'Error';
                $has_error = true;
            }
        } else {
            $status = 'Pending';
        } ?>

  <div class="ob-update ob-update-<?=strtolower($status)?>">

    <div class="ob-update-name"><h2><?=$update->version?></h2></div>

    <div class="ob-update-description">
        <?php $items = $update->items(); ?>
      <ul>
        <?php if ($status == 'Error') {
            ?><li><?=htmlspecialchars($update->error)?></li><?php
        } ?>
        <?php foreach ($items as $item) { ?>
        <li><?=htmlspecialchars($item)?></li>
        <?php } ?>
      </ul>
    </div>

    <div class="ob-update-status"><?=$status?></div>
  </div>
        <?php
    } ?>
</div>
<?php } ?>

</div>

</body>
</html>
