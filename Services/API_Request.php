<?php
namespace Zao\ZCSDK\Services;

abstract class API_Request {
	protected $endpoint = 'https://tickets.niagaracruises.com/CENTAMAN.API_Staging/'; // To be set via environment variable, at some point
	protected $args;
	protected $username;
	protected $password;
	protected $response;
	protected $request;
	protected $code;

	public function __construct() {
		$this->username = CENTAMAN_USER;
		$this->password = CENTAMAN_PASS;
		$this->args     = array();
		$this->init();
	}

	abstract protected function init();

	protected function set_endpoint( $endpoint ) {
		$this->endpoint .= ltrim( $endpoint, '/' );
		$this->endpoint  = trailingslashit( $this->endpoint );

		return $this;
	}

	protected function set_query_args( $args ) {
		$this->endpoint = add_query_arg( $args, $this->endpoint );
		return $this;
	}

	protected function set_args( Array $args ) {
		$this->args = $args;
		return $this;
	}

	public function dispatch( $method ) {

		$url  = $this->get_endpoint();

		$args = array_merge( $this->args, $this->get_default_args() );

		switch ( $method ) :
			case 'PUT' :
				$request = wp_remote_request( $url, array_merge( $args, array( 'method' => 'PUT' ) ) );
				break;
			case 'POST' :
				$request = wp_remote_post( $url, $args );
				break;
			default :
			case 'GET' :
				$request = wp_remote_get( $url, $args );
		endswitch;

		$this->request  = $request;
		$this->response = wp_remote_retrieve_body( $request );
		$this->code     = wp_remote_retrieve_response_code( $request );

		return $this;
	}

	public function get_request() {
		return $this->request;
	}

	public function get_response() {
		return json_decode( $this->response );
	}

	public function get_code() {
		return $this->code;
	}

	protected function get_endpoint() {
		return $this->endpoint;
	}

	private function get_default_args() {
		return array(
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode( $this->username . ':' . $this->password ),
				'Content-Type'  => 'application/json'
			)
		);
	}

}
