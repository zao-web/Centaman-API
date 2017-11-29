<?php
namespace Zao\ZCSDK\Services;

class Retail_Services extends API_Request {
	protected $args;

	protected function init() {
		$this->set_endpoint( 'retail_services' );
	}

}
