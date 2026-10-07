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

		$html .= "<div class=\"gopher-entry gopher-entry-type-{$this->type}\"" .
			">\n";
		if ($this->type != GopherEntry::TYPE_DIRECTORY)
			$html .= "\t<span class=\"entry-type\">{$this->type}</span>\n";
		$html .= "\t<span class=\"entry-label\">{$this->label}</span>\n";
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
