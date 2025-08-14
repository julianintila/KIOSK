<?php

$config = require __DIR__ . '/../config.php';

$serverName   = $config['db_server'];
$databaseName = $config['db_name'];
$username     = $config['db_user'];
$password     = $config['db_pass'];

$pdo = new PDO("sqlsrv:Server=$serverName;Database=$databaseName", $username, $password);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
