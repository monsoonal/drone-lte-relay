<?php

ini_set( 'display_errors', true );

class UDP_Relay {

	protected $_host_one = '';
	protected $_port_one = '';
	protected $_host_two = '';
	protected $_port_two = '';
	protected $_stream_one = null;
	protected $_stream_two = null;
	protected $_last_register_time = 0;
	protected $_debug = false;

	const BUFFER_SIZE = 1024;
	const REGISTRATION_INTERVAL = 2;

	public function __construct( $host_one = '', $port_one = '', $host_two = '', $port_two = '' ) {
		$this->_host_one = $host_one;
		$this->_port_one = $port_one;
		$this->_host_two = $host_two;
		$this->_port_two = $port_two;

		$this->_stream_one = self::_create_udp_client( $host_one, $port_one );
		$this->_stream_two = self::_create_udp_client( $host_two, $port_two );
	}

	public function __destruct() {
		fclose( $this->_stream_one );
		fclose( $this->_stream_two );
	}

	public function set_debug( $value = false ) {
		$this->_debug = $value;
	}

	public function process() {
		$this->_register_with_server( $this->_stream_two );
		$this->_read_stream_data( $this->_stream_one, $this->_stream_two );
		$this->_read_stream_data( $this->_stream_two, $this->_stream_one );
	}

	protected function _register_with_server( $stream = null ) {
		$diff = time() - $this->_last_register_time;
		if( $diff >= self::REGISTRATION_INTERVAL ) {
			fwrite( $stream, 'udp.register.ip' );
			$this->_last_register_time = time();
		}
	}

	protected function _read_stream_data( $read_stream = null, $write_stream = null ) {
		$buffer = fgets( $read_stream, self::BUFFER_SIZE );

		if( empty( $buffer ) ) {
			return;
		}

		if( $this->_debug ) {
			echo 'recvd: ', $buffer, "\n";
		}

		if( strpos( $buffer, 'udp.register.complete' ) !== false ) {
			return;
		}

		fwrite( $write_stream, $buffer );
	}

	protected static function _create_udp_client( $host = '', $port = 0 ) {
		$address = 'udp://' . $host . ':' . $port;
		$stream = stream_socket_client( $address, $error_number, $error_message );
		if( ! $stream ) {
			die( 'could not create UDP client for ' . $address . '; Reason: [' . $error_number . '] - ' . $error_message );
		}

		stream_set_blocking( $stream, 0 );
		return $stream;
	}
}

$drone_ip       = '192.168.0.99';
$server_ip      = gethostbyaddr( '192.168.0.102' );
$at_data_relay  = new UDP_Relay( $drone_ip, 14550, $server_ip, 14550 );
//$nav_data_relay = new UDP_Relay( $drone_ip, 5554, $server_ip, 5554 );

$at_data_relay->set_debug( true );

while( 1 ) {
	$at_data_relay->process();
	//$nav_data_relay->process();
}
