<?php
namespace Zao\ZCSDK\Services;

class Stock_Services extends API_Request {
	protected $args;

	protected function init() {
		$this->set_endpoint( 'stock_services' );
	}

}
