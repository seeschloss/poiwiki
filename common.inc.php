<?php
// Various functions used by the application.

function error($e) {
	error_log($e);
	die();
}

function form($args, $error = '') {
	$id = '';
	$message = '';
	$action = '';
	$autocomplete_types = '';

	$data = array(
		'id' => '',
		'latitude' => '',
		'longitude' => '',
		'title' => '',
		'description' => '',
		'type' => '',
	);

	if (isset($args['new'])) {
		$action = 'create';
		list($data['latitude'], $data['longitude']) = explode(',', $args['new']);
	}

	if (isset($args['id'])) {
		$action = 'edit';
		global $db;
		$sql = 'SELECT * FROM poi WHERE id=' . (int)$args['id'];
		$data = $db->query($sql)->fetch();
	}

	foreach ($data as $key => $value) {
		if (isset($args[$key])) {
			$data[$key] = $args[$key];
		}
	}

	if ($error) {
		$message = '<div class="form-error">' . $error . '</div>';
	}

	global $db;
	$autocomplete_types = '<datalist id="types">';
	$sql = 'SELECT type FROM poi GROUP BY type ORDER BY type';
	foreach ($db->query($sql)->fetchAll() as $type) {
		$autocomplete_types .= <<<HTML
	<option value="${type['type']}" />
HTML;
	}
	$autocomplete_types .= '</datalist>';

	$icons = '<ul class="icons">';
	foreach (glob('icons/*.svg') as $icon) {
		$title = basename($icon, '.svg');
		$icons .= <<<HTML
	<li><img title="$title" src="$icon" /></li>
HTML;
	}
	$icons .= '</ul>';

	return <<<HTML
	$message
	$icons
	$autocomplete_types
	<form class="poi-form $action" method="post" action="">
		<input type="hidden" name="q" value="$action" />
		<input type="hidden" name="id" value="${data['id']}" />
		<input type="hidden" name="latitude" value="${data['latitude']}" />
		<input type="hidden" name="longitude" value="${data['longitude']}" />
		<input type="text" name="icon" value="${data['icon']}" placeholder="Icon" />
		<input type="text" name="title" value="${data['title']}" placeholder="Title" />
		<input type="text" list="types" name="type" value="${data['type']}" placeholder="Type" />
		<textarea name="description" placeholder="Description">${data['description']}</textarea>
		<input type="submit" name="submit" value="Submit" />
	</form>
HTML;
}

function form_submit($data) {
	if (!empty($data['latitude']) and
		!empty($data['longitude']) and
		!empty($data['title']) and
		!empty($data['description'])) {
		global $db;

		if ($data['q'] == 'create') {
			$db->prepare('INSERT INTO poi (latitude, longitude, icon, title, type, description) VALUES(?, ?, ?, ?, ?, ?);')
				->execute(array($data['latitude'], $data['longitude'], $data['icon'], $data['title'], $data['type'], $data['description']));
		} else {
			$db->prepare('UPDATE poi SET latitude=?, longitude=?, icon=?, title=?, type=?, description=? WHERE id=?;')
				->execute(array($data['latitude'], $data['longitude'], $data['icon'], $data['title'], $data['type'], $data['description'], $data['id']));
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
		$description = str_replace("\n", " \n", $row['description']);
		$description = preg_replace_callback('#(https?://([^/ ,<]+)[^ ,<]*)#', function($matches) {
			$html = '<a href="' . $matches[1] . '">' . $matches[2] . '</a>';

			if ($ogp = opengraph($matches[1])) {
				$html .= $ogp;
			}

			return $html;
		}, $description);
		$description = nl2br($description);
		$description = '<h2>' . $row['title'] . '</h2>' . $description;

		$data[] = array(
			'id' => $row['id'],
			'latitude' => (float)$row['latitude'],
			'longitude' => (float)$row['longitude'],
			'icon' => $row['icon'],
			'title' => $row['title'],
			'type' => $row['type'],
			'description' => $description,
		);
	}

	return '<script type="text/javascript">var poi = ' . json_encode($data) . ';</script>';
}

function opengraph($url) {
	if (!file_exists('cache')) {
		mkdir('cache');
	}

	$cache = 'cache/' . md5($url) . '.ogp';

	if (!file_exists($cache)) {
		$return = FALSE;

		$c = curl_init();
		curl_setopt_array($c, array(
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => TRUE,
			CURLOPT_FOLLOWLOCATION => TRUE,
			CURLOPT_CONNECTTIMEOUT => 2,
			CURLOPT_TIMEOUT => 2,
			CURLOPT_HEADER => TRUE,
			CURLOPT_NOBODY => TRUE,
		));
		curl_exec($c);
		$headers = curl_getinfo($c);

		if (strpos($headers['content_type'], 'text/html') === 0) {
			$c = curl_init();
			curl_setopt_array($c, array(
				CURLOPT_URL => $url,
				CURLOPT_RETURNTRANSFER => TRUE,
				CURLOPT_FOLLOWLOCATION => TRUE,
				CURLOPT_CONNECTTIMEOUT => 2,
				CURLOPT_TIMEOUT => 2,
			));
			$data = curl_exec($c);

			if ($data) {
				$dom = new DOMDocument();
				$dom->loadHTML($data);

				foreach ($dom->getElementsByTagName('meta') as $meta) {
					$property = $meta->getAttribute('property');

					switch ($property) {
						case 'og:image':
							$return = '<img class="og-preview" src="' . $meta->getAttribute('content') . '" />';
							break;
						case 'twitter:image':
							$return = '<img class="og-preview" src="' . $meta->getAttribute('value') . '" />';
							break;
					}
				}
			}
		} else if (strpos($headers['content_type'], 'image') === 0) {
			$return = '<img class="og-preview" src="' . $url . '" />';
		}

		file_put_contents($cache, serialize($return));
	}

	return unserialize(file_get_contents($cache));
}

