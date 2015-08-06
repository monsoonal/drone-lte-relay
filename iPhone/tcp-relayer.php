<?php

ini_set( 'display_errors', true );

class TCP_Relay {

	protected $_host_one = '';
	protected $_port_one = 0;
	protected $_stream_one = null;
	protected $_host_two = '';
	protected $_port_two = 0;
	protected $_stream_two = null;
	protected $_debug = false;

	const BUFFER_SIZE = 1024;

	public function set_debug( $value = false ) {
		$this->_debug = $value;
	}

	public function __construct( $host_one = '', $port_one = 0, $host_two = '', $port_two = 0 ) {
		$this->_host_one = $host_one;
		$this->_port_one = $port_one;
		$this->_host_two = $host_two;
		$this->_port_two = $port_two;

		$this->_stream_one = self::_create_tcp_client( $host_one, $port_one );
		$this->_stream_two = self::_create_tcp_client( $host_two, $port_two );
	}

	public function __destruct() {
		fclose( $this->_stream_one );
		fclose( $this->_stream_two );
	}

	public static function _create_tcp_client( $host = '', $port = 0 ) {
		$address = 'tcp://' . $host . ':' . $port;
		$socket = stream_socket_client( $address, $error_number, $error_message );
		if( ! $socket ) {
			die( 'could not create tcp client on ' . $address . '. [' . $error_number . '] - ' . $error_message );
		}
		stream_set_blocking( $socket, false );
		echo 'Connected to ', $host, ':', $port, "\n";
		return $socket;
	}

	public function process() {
		$this->_read_stream_data( $this->_stream_one, $this->_stream_two );
		$this->_read_stream_data( $this->_stream_two, $this->_stream_one );
	}

	protected function _read_stream_data( $read_stream = null, $write_stream = null ) {
		$buffer = fgets( $read_stream, self::BUFFER_SIZE );
		if( empty( $buffer ) ) {
			return;
		}

		if( $this->_debug ) {
			echo $buffer;
		}

		fwrite( $write_stream, $buffer );
	}
}

$drone_address  = '192.168.0.99';
$server_address = gethostbyaddr( '192.168.0.102' );
$video_steam    = new TCP_Relay( $drone_address, 5555, $server_address, 5555 );

while( 1 ) {
	$video_steam->process();
}
