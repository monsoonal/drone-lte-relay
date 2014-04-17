<?php

ini_set( 'display_errors', true );

class TCP_Server {

	protected $_host = '';
	protected $_port = 5555;
	protected $_clients = array();
	protected $_socket = null;
	protected $_socket_timeout = 300000;

	public function __construct( $host = '0.0.0.0', $port = 5555 ) {
		$this->_host = $host;
		$this->_port = $port;

		$this->_socket = self::_create_tcp_server( $host, $port );
	}

	public function __destruct() {
		fclose( $this->_socket );
	}

	protected static function _create_tcp_server( $host = '0.0.0.0', $port = 5555 ) {
		$address = 'tcp://' . $host . ':' . $port;
		$stream = stream_socket_server( $address, $error_number, $error_message );
		if( ! $stream ) {
			die( 'could not create a tcp server on ' . $address . '; [' . $error_number . '] - ' . $error_message );
		}

		stream_set_blocking( $stream, false );
		return $stream;
	}

	public function listen() {
		$sockets_to_check = $this->_clients;
		$sockets_to_check[] = $this->_socket;

		// see if there are any changes with any of the sockets
		if( ! stream_select( $sockets_to_check, $write, $except, $this->_socket_timeout ) ) {
			die( 'there was a problem with the tcp server using `stream_select`.' );
		}

		// check for a new connection to the server
		if( in_array( $this->_socket, $sockets_to_check ) ) {
			$new_client = stream_socket_accept( $this->_socket );
			if( $new_client ) {
				echo 'Connection accepted from: ', stream_socket_get_name( $new_client, true ), "\n";
				$this->_clients[] = $new_client;
				echo 'Now there are ', count( $this->_clients ), ' clients connected...', "\n";
			}
		}

		// check for any messages OR disconnects
		foreach( $sockets_to_check as $client_socket ) {
			if( $client_socket === $this->_socket ) {
				continue;
			}

			$buffer = fgets( $client_socket, 1024 );
			if( ! $buffer ) {
				unset( $this->_clients[ array_search( $client_socket, $this->_clients ) ] );
				fclose( @$client_socket );
				echo 'Client disconnected!', "\n", 'Now there are ' . count( $this->_clients ), ' clients connected...', "\n";
				continue;
			}

			$this->_broadcast_message( $client_socket, $buffer );
		}
	}

	protected function _broadcast_message( $socket = null, $message = '' ) {
		foreach( $this->_clients as $client ) {
			if( $client === $socket ) {
				continue;
			}
			fwrite( $client, $message );
		}
	}

}

$drone_video_stream = new TCP_Server( '0.0.0.0', 5555 );

while( 1 ) {
	$drone_video_stream->listen();
}