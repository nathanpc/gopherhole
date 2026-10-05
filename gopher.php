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
			throw new GopherException("socket_create() failed: reason: " .
				socket_strerror(socket_last_error()));
		}

		// Connect to gopher server.
		$result = socket_connect($socket, $this->host, $this->port);
		if ($result === false) {
			socket_close($socket);
			throw new GopherException('socket_connect() failed. Reason: ' .
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
}

/**
 * Gopher exception when fetching or parsing content.
 */
class GopherException extends Exception {
}
