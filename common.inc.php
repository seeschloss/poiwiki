<?php
// Various functions used by the application.

function error($e) {
	error_log($e);
	die();
}

