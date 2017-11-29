<?php
namespace Zao\ZCSDK\Services;

class Ticket_Services extends API_Request {
	protected $args;

	protected function init() {
		$this->set_endpoint( 'cashless_services' );
	}

}
