<?php

require_once __DIR__ . '/mimetype.php';

/**
 * Gopher abstraction library.
 *
 * @author Nathan Campos <hi@nathancampos.me>
 */
class Gopher {
	private $host;
	private $port;
	private $bufsize;

	/**
	 * Constructs the Gopher handler.
	 *
	 * @param string  $host    IP address of the Gopher server.
	 * @param integer $port    Port where the Gopher server is listening on.
	 * @param integer $bufsize Size of the socket read buffer.
	 */
	public function __construct($host, $port, $bufsize = 2048) {
		$this->host = $host;
		$this->port = $port;
		$this->bufsize = $bufsize;
	}

	/**
	 * Requests a resource from the server.
	 *
	 * @param string $selector Selector to be sent to the Gopher server.
	 *
	 * @return string Response from the server.
	 *
	 * @throws GopherException When a fetching or parsing error occurs.
	 */
	public function getContents($selector) {
		// Create socket for connection with gopher server.
		$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		if ($socket === false) {
			throw new GopherException($selector, 'socket_create() failed: ' .
				socket_strerror(socket_last_error()));
		}

		// Connect to gopher server.
		$result = socket_connect($socket, $this->host, $this->port);
		if ($result === false) {
			socket_close($socket);
			throw new GopherException($selector, 'socket_connect() failed: ' .
				socket_strerror(socket_last_error($socket)));
		}

		// Send selector to gopher server.
		socket_write($socket, "$selector\r\n");

		// Read the response from the server.
		$response = '';
		while ($out = socket_read($socket, $this->bufsize)) {
			$response .= $out;
		}

		// Close the socket.
		socket_close($socket);
		return $response;
	}

	/**
	 * Builds an appropriate title for the webpage based on the information from
	 * the server response and the selector.
	 *
	 * @param string $name     Name of the website, to always be displayed.
	 * @param string $type     Requested Gopher entry type.
	 * @param string $selector Selector used to request the content.
	 * @param string $response Response from the server.
	 *
	 * @return string An appropriate title for the webpage.
	 */
	public static function BuildPageTitle($name, $type, $selector, $response) {
		// Get the title of the page from the first line if possible.
		if (($type == '1') && (substr($response, 0, 1) == 'i') &&
				strpos($response, "\t")) {
			return substr($response, 1, strpos($response, "\t") - 1) .
				" - $name";
		}

		// Build a regular, path-based, title.
		$fname = self::SelectorBasename($selector);
		$fpath = substr($selector, 0, strrpos($selector, $fname));
		return "$fname - $fpath - $name";
	}

	/**
	 * Checks if a host is in fact ourselves.
	 *
	 * @param string $host     Host to be checked.
	 * @param array  $specials Special hosts that are also considered to be us.
	 *
	 * @return bool True if the host is ourselves, false otherwise.
	 */
	public static function IsHostOurs($host, $specials = []) {
		// Check for common host names that are used for ourselves or invalid.
		if (empty($host) || ($host == 'localhost') || ($host == 'null.host'))
			return true;

		// Check each of the hosts contained in the specials array.
		return in_array($host, $specials);
	}

	/**
	 * Tries to guess the type of the response from a Gopher server
	 * automatically based on the response and the selector.
	 *
	 * @param string $selector Selector used to request the content.
	 * @param string $response Response from the server.
	 *
	 * @return string Guessed Gopher type of the response.
	 */
	public static function DetectResponseType($selector, $response) {
		$tabs = substr_count($response, "\t");
		$newlines = substr_count($response, "\r\n");

		// If we have 3 tabs per line it means that we have a listing.
		if ($tabs > ($newlines * 2.5))
			return '1';

		return '0';
	}

	/**
	 * Gets a filename (basename) from a selector.
	 *
	 * @param string $selector Selector to extract the basename from.
	 *
	 * @return string File name extracted from the selector.
	 */
	public static function SelectorBasename($selector) {
		return substr(strrchr($selector, '/'), 1);
	}
}

/**
 * Handles the request of content from a Gopher server.
 */
class GopherRequest {
	private $server;
	public $type;
	public $selector;
	public $response;
	public $mime;

	/**
	 * Sets up the Gopher request object. Automatically cleans the selector and
	 * type variables to ensure they are not malicious.
	 *
	 * @param Gopher $server   Gopher server to request the content from.
	 * @param string $selector Selector to be sent to the Gopher server.
	 * @param string $type     Gopher request entry type.
	 */
	public function __construct($server, $selector = '', $type = null) {
		$this->server = $server;
		$this->selector = trim($selector);
		$this->type = is_null($type) ? null : substr(trim($type), 0, 1);
		$this->response = null;
		$this->mime = is_null($type) ? null :
			GopherEntryType::MimeType($type, $selector);
	}

	/**
	 * Requests the content from the server.
	 *
	 * @throws GopherException if an error occurs with the connection.
	 */
	public function request() {
		// Get response from server.
		$this->response = $this->server->getContents($this->selector);

		// Infer type automatically if needed.
		if (is_null($this->type)) {
			$this->type = Gopher::DetectResponseType($this->selector,
				$this->response);
			$this->mime = GopherEntryType::MimeType($this->type,
				$this->selector);
		}
	}

	/**
	 * Parses the response from a Gopher server of the directory type (1) into
	 * an array of entries.
	 *
	 * @return array An array of entries as represented in the response.
	 */
	public function parseEntries() {
		$entries = array();

		foreach (explode("\n", $this->response) as $line) {
			// Ignore empty lines or the terminating dot line.
			$line = trim($line);
			if (empty($line) || ($line == '.'))
				continue;

			// Parse the entry and append it to the array.
			$entries[] = new GopherEntry($line);
		}

		return $entries;
	}

	/**
	 * Sets the HTTP headers for a raw response from the Gopher server.
	 */
	public function setRawHeaders() {
		header("Content-type: {$this->mime}");
		if ((substr($this->mime, 0, 11) == 'application') ||
				($this->mime == 'text/calendar')) {
			$fname = Gopher::SelectorBasename($this->selector);
			header("Content-Disposition: attachment; filename=\"$fname\"");
		}
	}

	/**
	 * Checks if we should return the contents from the server's response in raw
	 * format.
	 *
	 * @param bool|string $raw  Should we force the output to be raw?
	 *
	 * @return bool True if we should provide a raw output, false otherwise.
	 */
	public function shouldReturnRaw($raw) {
		// Force raw output?
		if ((is_bool($raw) && $raw) ||
				filter_var($raw, FILTER_VALIDATE_BOOLEAN)) {
			return true;
		}

		// Use MIME type to determine the output format.
		return !preg_match("/^text\/(?!calendar)/", $this->mime);
	}

	/**
	 * Gets a webpage title based on the request.
	 *
	 * @param string $name Name of the website, to always be displayed.
	 *
	 * @return string An appropriate title for the webpage.
	 */
	public function getPageTitle($name) {
		return Gopher::BuildPageTitle($name, $this->type, $this->selector,
			$this->response);
	}
}

/**
 * Gopher entry type identifier.
 */
class GopherEntryType {
	const TEXT = '0';
	const DIRECTORY = '1';
	const INFO = 'i';
	const CSO = '2';
	const ERROR = '3';
	const BINHEX = '4';
	const DOS = '5';
	const UNIX = '6';
	const SEARCH = '7';
	const TELNET = '8';
	const BINARY = '9';
	const CALENDAR = 'c';
	const GIF = 'g';
	const IMAGE = 'I';
	const BITMAP = ':';
	const VIDEO = ';';
	const AUDIO = 's';
	const DOCUMENT = 'd';
	const HTML = 'h';

	/**
	 * Get the HTML representation of an entry type.
	 *
	 * @param string $type Gopher entry type.
	 *
	 * @return string HTML representation of the entry type.
	 */
	public static function asHTML($type) {
		// Correct for types that cannot be in a filename.
		$imgtype = $type;
		if ($type == self::BITMAP)
			$imgtype = self::IMAGE;

		return "<span class=\"entry-type\"><img src=\"/_assets/icons/" .
			"$imgtype.png\" alt=\"$type\" /></span>";
	}

	/**
	 * Gets the appropriate MIME type of the Gopher type.
	 *
	 * @param string $type     Gopher entry type.
	 * @param string $selector Selector used to request the content.
	 *
	 * @return string MIME type of the Gopher type.
	 */
	public static function MimeType($type, $selector) {
		switch ($type) {
			case self::BINHEX:
			case self::DOS:
			case self::UNIX:
			case self::BINARY:
				return 'application/octet-stream';
			case self::CALENDAR:
				return 'text/calendar';
			case self::IMAGE:
			case self::VIDEO:
			case self::AUDIO:
			case self::DOCUMENT:
				$mime = mime_from_fname(Gopher::SelectorBasename($selector));
				if ($mime === false)
					return 'application/octet-stream';
				return $mime;
			case self::GIF:
				return 'image/gif';
			case self::BITMAP:
				return 'image/bmp';
			case self::HTML:
				return 'text/html';
			case self::TEXT:
			default:
				return 'text/plain';
		}
	}
}

/**
 * Gopher directory entry object.
 */
class GopherEntry {
	public $type;
	public $label;
	public $selector;
	public $host;
	public $port;

	/**
	 * Constructs a Gopher directory entry object.
	 *
	 * @param string $line Response line from a directory listing.
	 */
	public function __construct($line) {
		$this->type = substr($line, 0, 1);
		$parts = explode("\t", substr($line, 1));
		$this->label = $parts[0];
		$this->selector = $parts[1];
		$this->host = $parts[2];
		$this->port = intval($parts[3]);
	}

	/**
	 * Gets the HTML representation of this entry line.
	 *
	 * @return string HTML representation of the object.
	 */
	public function asHTML() {
		$html = '';

		// Build the address of the entry's link.
		$href = "?type={$this->type}&selector={$this->selector}";
		if (preg_match("/^URL:/", $this->selector)) {
			$href = substr($this->selector, 4);
		} elseif (!Gopher::IsHostOurs($this->host)) {
			$href = "https://portal.mozz.us/gopher/{$this->host}:" .
				"{$this->port}/{$this->type}{$this->selector}";
		}

		// Build the HTML component.
		$html .= "<div class=\"gopher-entry gopher-entry-type-{$this->type}\"" .
			">\n";
		if ($this->type == GopherEntryType::INFO) {
			$label = $this->label;
			if (empty(trim($this->label))) {
				$html .= "\t<span class=\"entry-label\" style=\"visibility: " .
					"hidden\">  </span>\n";
			} else {
				$html .= "\t<span class=\"entry-label\">$label</span>\n";
			}
		} else {
			$html .= "\t" . GopherEntryType::asHTML($this->type) . "\n";
			$html .= "\t<a class=\"entry-label\" href=\"$href\">" .
				"{$this->label}</a>\n";
		}
		$html .= '</div>';

		return $html;
	}
}

/**
 * Gopher exception when fetching or parsing content.
 */
class GopherException extends Exception {
	private $selector;

	/**
	 * Constructs a new Gopher error object.
	 *
	 * @param string $selector Selector which caused the issue.
	 * @param string $message  Error message detailing issue.
	 */
	public function __construct($selector, $message) {
		parent::__construct($message);
		$this->selector = $selector;
	}

	/**
	 * Gets the selector which caused the issue.
	 *
	 * @return string Selector that caused the issue.
	 */
	public function getSelector() {
		return $this->selector;
	}
}

/**
 * Gets the contents of a URL parameter.
 *
 * @param string $key     Name of the URL argument.
 * @param string $default Value to be used if the parameter wasn't provided.
 *
 * @return string|null Content of the URL parameter.
 */
function urlparam($key, $default = null) {
	return isset($_GET[$key]) ? $_GET[$key] : $default;
}
