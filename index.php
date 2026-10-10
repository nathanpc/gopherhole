<?php
// User configuration.
$title = 'webhole';
$gopher_host = '127.0.0.1';
$gopher_port = 70;
$buffer_size = 2048;
error_reporting(E_ALL);

require_once __DIR__ . '/gopher.php';

// Get request parameters.
$type = $_GET['type'];
$selector = $_GET['selector'];

// Setup Gopher object.
$gopher = new Gopher($gopher_host, $gopher_port, $buffer_size);
$error = null;
$response = null;
try {
	// Get response from server.
	$response = $gopher->getContents($selector);

	// Get the title of the page from the first line if possible.
	if (($type == '1') && (substr($response, 0, 1) == 'i') &&
			strpos($response, "\t")) {
		$title = substr($response, 1, strpos($response, "\t") - 1) .
			" - $title";
	} else {
		$fname = substr(strrchr($selector, '/'), 1);
		$fpath = substr($selector, 0, strrpos($selector, $fname));
		$title = "$fname - $fpath - $title";
	}
} catch (GopherException $e) {
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
	<title><?= $title ?></title>
</head>
<body>
	<div id="gophermap"><?php
		// Check for errors.
		if (!is_null($error)) {
			echo "Error when requesting {$error->getSelector()}: " .
				"{$error->getMessage()}\n";
			goto end;
		} elseif (is_null($response)) {
			echo "Error: Empty response\n";
			goto end;
		}

		// Handle special types.
		if ($type == GopherEntry::TYPE_DIRECTORY) {
			// Directory listing.
			$entries = $gopher->parseEntries($response);
			foreach ($entries as $entry) {
				echo $entry->asHTML();
			}
		} else {
			// Other types.
			echo "<div id=\"response\">$response</div>";
		}

		end:
	?></div>
</body>
</html>
