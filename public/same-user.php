<?php

$token = preg_replace('/[^a-f0-9]/', '', $_GET['token'] ?? '');
$tmpFile = "/tmp/ob_cli_{$token}";

if (strlen($token) !== 64 || ! is_file($tmpFile) || time() - filemtime($tmpFile) > 10) {
    http_response_code(401);
    exit();
}

$fileOwnerUid = fileowner($tmpFile);
$webServerUid = posix_geteuid();

http_response_code($fileOwnerUid === $webServerUid ? 200 : 403);
exit();
