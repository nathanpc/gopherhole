<?php
// User configuration.
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
} catch (GopherException $e) {
	$error = $e;
}

// TODO: Get title from first entry.
?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html lang="en">
<head>
	<!-- Character set. -->
	<meta charset="UTF-8">

	<!-- Mobile definitions. -->
	<!--<meta name="viewport"
		content="width=600, initial-scale=1, user-scalable=1" />-->

	<!-- Stylesheets -->
	<link rel="stylesheet" href="style.css">

	<!-- Page information. -->
	<title>webhole</title>
</head>
<body>
	<?php
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
			echo "<pre id=\"gophermap\">$response</pre>";
		}

		end:
	?>
</body>
</html>
