<?php
// User configuration.
$title = 'webhole';
$gopher_base_domain = 'server.domain';
$gopher_host = '127.0.0.1';
$gopher_port = 70;
$buffer_size = 2048;
error_reporting(E_ALL);

require_once __DIR__ . '/gopher.php';
$error = null;
try {
	// Set up the request.
	$gopher = new Gopher($gopher_host, $gopher_port, $buffer_size);
	$req = new GopherRequest($gopher, urlparam('selector', ''),
		urlparam('type'));

	// Perform the request to the Gopher server.
	$req->request();

	// Determine if we should return the raw content of the request.
	if ($req->shouldReturnRaw(urlparam('raw', false))) {
		$req->setRawHeaders();
		echo $req->response;
		return;
	}
} catch (GopherException $e) {
	http_response_code(500);
	$error = $e;
}
?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html lang="en">
<head>
	<!-- Character set. -->
	<meta charset="UTF-8">

	<!-- Mobile definitions. -->
	<meta name="viewport"
		content="width=750, initial-scale=1, user-scalable=1" />

	<!-- Stylesheets -->
	<link rel="stylesheet" href="style.css">

	<!-- Page information. -->
	<title><?= $req->getPageTitle($title) ?></title>
</head>
<body>
	<!-- Header -->
	<div id="header">
		<span class="title">
			<a href="/"><?= $title ?></a>
		</span>

		<hr>
	</div><!-- #header -->

	<!-- Content -->
	<div id="gophermap"><?php
		// Check for errors.
		if (!is_null($error)) {
			echo "Error when requesting {$error->getSelector()}: " .
				"{$error->getMessage()}\n";
			goto end;
		} elseif (is_null($req->response)) {
			echo "Error: Empty response\n";
			goto end;
		}

		// Handle special types.
		if ($req->type == GopherEntryType::DIRECTORY) {
			// Directory listing.
			$entries = $req->parseEntries();
			foreach ($entries as $entry)
				echo $entry->asHTML();
		} else {
			// Other types.
			echo "<div id=\"response\">{$req->response}</div>";
		}

		end:
	?></div><!-- #gophermap -->

	<!-- Footer -->
	<div id="footer">
		<hr>

		<a href="gopher://<?= $gopher_base_domain ?>/<?= $req->type .
			$req->selector ?>">gopher</a> -
		<a href="?<?= $_SERVER['QUERY_STRING'] .
			(empty($_SERVER['QUERY_STRING']) ? '' : '&') . "raw=1" ?>">raw</a>
	</div><!-- #footer -->
</body>
</html>
