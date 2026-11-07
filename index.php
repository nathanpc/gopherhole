<?php
error_reporting(E_ALL);
require_once __DIR__ . '/gopher.php';

// User configuration.
$config = GopherConfig::getInstance();
$config->set('title', 'webhole');
$config->set('proxy_url', '//portal.mozz.us/gopher/');
$config->set('base_domain', 'domain.tld');

// Initialize the Gopher handler and request the selector.
$error = null;
try {
	// Set up the request.
	$gopher = new Gopher($config);
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
	<title><?= $req->getPageTitle($config->get('title')) ?></title>
</head>
<body>
	<!-- Header -->
	<div id="header">
		<span class="title">
			<a href="/"><?= $config->get('title') ?></a>
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
		} else if ($req->type == GopherEntryType::TEXT) {
			// Plain text.
			$content = htmlspecialchars($req->response,
				ENT_SUBSTITUTE | ENT_HTML401);
			echo "<div id=\"response\" class=\"plain-text\">\n" .
				autolink($content) . "\n</div>";
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
