<?php

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
	 * Parses the response from a Gopher server of type directory (1) into an
	 * array of entries.
	 *
	 * @param string $response Response from the server with entries.
	 *
	 * @return array An array of entries as represented in the response.
	 */
	public function parseEntries($response) {
		$entries = array();

		foreach (explode("\n", $response) as $line) {
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
		$fname = substr(strrchr($selector, '/'), 1);
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
		return "<span class=\"entry-type\">$type</span>";
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
