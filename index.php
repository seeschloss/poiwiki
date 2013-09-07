<?php

// This is a quick and dirty map-based wiki.

require 'config.inc.php';
require 'common.inc.php';

try {
	$db = new PDO($pdo_dsn, $pdo_user, $pdo_pass);
}
catch (PDOException $e) {
	error($e);
}

$db->query("
	CREATE TABLE IF NOT EXISTS poi (
		latitude NUMERIC, longitude NUMERIC,
		title TEXT, description TEXT
	)
");


