<?php

// This is a quick and dirty map-based wiki.

require 'config.inc.php';
require 'common.inc.php';

try {
	$db = new PDO(PDO_DSN, PDO_USER, PDO_PASS);
}
catch (PDOException $e) {
	error($e);
}

$db->query("
	CREATE TABLE IF NOT EXISTS poi (
		id INTEGER PRIMARY KEY AUTOINCREMENT,
		latitude NUMERIC, longitude NUMERIC,
		title TEXT, description TEXT,
		type TEXT, icon TEXT
	)
");

if (isset($_POST['q'])) {
	switch ($_POST['q']) {
		case 'create':
		case 'edit':
			if (($result = form_submit($_POST)) === TRUE) {
				header("Location: /");
			} else {
				require 'header.html';
				print form($_POST, 'Error');
				require 'footer.html';
			}
			break;
	}
} else if (isset($_GET['q'])) {
	switch ($_GET['q']) {
		case 'edit':
			require 'header.html';
			print form(array('id' => $_GET['id']));
			require 'footer.html';
			break;
		case 'create':
			require 'header.html';
			print form(array('new' => $_GET['latlng']));
			require 'footer.html';
			break;
		case 'delete':
			print delete_poi($_GET['id']);
			header("Location: /");
			break;
	}
} else {
	require 'header.html';
	print list_json();
	require 'map.html';
	require 'footer.html';
}

