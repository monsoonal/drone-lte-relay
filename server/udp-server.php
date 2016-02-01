<?php

ini_set( 'display_errors', true );

class UDP_Server {

	protected $_socket = null;
	protected $_host = '';
	protected $_port = 0;
	protected $_clients = array();
	protected $_debug = false;

	public function __construct( $host = '', $port = 0 ) {
		$this->_host = $host;
		$this->_port = $port;
		$this->_socket = $this->_create_udp_server( $host, $port );
	}

	public function set_debug( $value = false ) {
		$this->_debug = $value;
	}

	public function process() {
		$buffer = stream_socket_recvfrom( $this->_socket, 1024, 0, $remote_host );
		if( empty( $buffer ) ) {
			return;
		}

		if( $this->_debug ) {
			echo $remote_host, ': ', $buffer, "\n";
		}

		if( strpos( $buffer, 'udp.register.ip' ) !== false ) {
			if( ! in_array( $remote_host, $this->_clients ) ) {
				$this->_clients[] = $remote_host;
			}

			stream_socket_sendto( $this->_socket, 'udp.register.complete', 0, $remote_host );
			return;
		}

		foreach( $this->_clients as $client ) {
			if( $client === $remote_host ) {
				continue;
			}
			stream_socket_sendto( $this->_socket, $buffer, 0, $client );
		}
	}

	public function __destruct() {
		fclose( $this->_socket );
	}

	protected static function _create_udp_server( $host = '0.0.0.0', $port = 0 ) {
		$address = 'udp://' . $host . ':' . $port;
		$socket = stream_socket_server( $address, $error_number, $error_message, STREAM_SERVER_BIND );
		if( ! $socket ) {
			die( 'could not create UDP server for ' . $address . '; Reason: [' . $error_number . '] - ' . $error_message );
		}

		stream_set_blocking( $socket, 0 );
		return $socket;
	}

}

$at_data_server  = new UDP_Server( '0.0.0.0', 5556 );
//$nav_data_server = new UDP_Server( '0.0.0.0', 5554 );
$at_data_server->set_debug( true );

while( 1 ) {
	$at_data_server->process();
	//$nav_data_server->process();
}
