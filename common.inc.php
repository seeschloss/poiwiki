<?php
// Various functions used by the application.

function error($e) {
	error_log($e);
	die();
}

function form($args, $error = '') {
	$id = '';
	$lat = '';
	$lng = '';
	$title = '';
	$description = '';
	$message = '';
	$action = '';

	if (isset($args['new'])) {
		$action = 'create';
		list($lat, $lng) = explode(',', $args['new']);
	}

	if (isset($args['id'])) {
		$action = 'edit';
		global $db;
		$sql = 'SELECT * FROM poi WHERE id=' . (int)$args['id'];
		$data = $db->query($sql)->fetch();
		
		$id = $args['id'];
		$lat = $data['latitude'];
		$lng = $data['longitude'];
		$title = $data['title'];
		$description = $data['description'];
	}

	if (isset($args['lat'])) {
		$lat = (float)$args['lat'];
	}

	if (isset($args['lng'])) {
		$lng = (float)$args['lng'];
	}

	if (isset($args['title'])) {
		$title = $args['title'];
	}

	if (isset($args['description'])) {
		$title = $args['description'];
	}

	if ($error) {
		$message = '<div class="form-error">' . $error . '</div>';
	}

	return <<<HTML
	$message
	<form method="post" action="">
		<input type="hidden" name="q" value="$action" />
		<input type="hidden" name="id" value="$id" />
		<input type="hidden" name="lat" value="$lat" />
		<input type="hidden" name="lng" value="$lng" />
		<input type="text" name="title" value="$title" placeholder="Title" />
		<textarea name="description" placeholder="Description">$description</textarea>
		<input type="submit" name="submit" value="Submit" />
	</form>
HTML;
}

function form_submit($data) {
	if (!empty($data['lat']) and
		!empty($data['lng']) and
		!empty($data['title']) and
		!empty($data['description'])) {
		global $db;

		if ($data['q'] == 'create') {
			$db->prepare('INSERT INTO poi (latitude, longitude, title, description) VALUES(?, ?, ?, ?);')
				->execute(array($data['lat'], $data['lng'], $data['title'], $data['description']));
		} else {
			$db->prepare('UPDATE poi SET latitude=?, longitude=?, title=?, description=? WHERE id=?;')
				->execute(array($data['lat'], $data['lng'], $data['title'], $data['description'], $data['id']));
		}

		return true;
	} else {
		return "Form incomplete";
	}
}

function delete_poi($id) {
	global $db;

	$db->prepare('DELETE FROM poi WHERE id=?;')
		->execute(array($id));
}

function list_json() {
	$data = array();

	$sql = 'SELECT * FROM poi';

	global $db;
	foreach ($db->query($sql) as $row) {
		$data[] = array(
			'id' => $row['id'],
			'latitude' => (float)$row['latitude'],
			'longitude' => (float)$row['longitude'],
			'title' => $row['title'],
			'description' => $row['description'],
		);
	}

	return '<script type="text/javascript">var poi = ' . json_encode($data) . ';</script>';
}

