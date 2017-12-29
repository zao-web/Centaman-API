<?php
namespace Zao\ZCSDK\Services;

class Member_Services extends API_Request {
	protected $args;

	protected function init() {
		$this->set_endpoint( 'member_services' );
		return $this;
	}

	public function authenticate( Array $args = array() ) {

		$args = wp_parse_args( $args, array(
			'MemberNumber' => '',
			'Surname'      => '',
			'Email'        => '',
			'Password'     => '',
		) );

		$this
			->set_endpoint( 'Authenticaton' )
			->set_args( array( 'body' => wp_json_encode( $args ) ) )
			->dispatch( 'POST' );

		// TODO: Determine authentication state management - WP User? Local Storage? Cookie?
		return $this;
	}

	/**
	 * @return boolean [description]
	 */
	public function is_authenticated() {
		return 200 === $this->get_code();
	}

}
