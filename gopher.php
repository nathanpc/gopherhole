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
				"($result) " . socket_strerror(socket_last_error($socket)));
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
		foreach ($specials as $item) {
			if ($host == $item)
				return true;
		}

		return false;
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

	// Gopher entry types.
	public const TYPE_TEXT = '0';
	public const TYPE_DIRECTORY = '1';
	public const TYPE_INFO = 'i';

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
		if ($this->type == GopherEntry::TYPE_INFO) {
			$label = $this->label;
			if (empty(trim($this->label))) {
				$html .= "\t<span class=\"entry-label\" style=\"visibility: " .
					"hidden\">  </span>\n";
			} else {
				$html .= "\t<span class=\"entry-label\">$label</span>\n";
			}
		} else {
			$html .= "\t<span class=\"entry-type\">{$this->type}</span>\n";
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
