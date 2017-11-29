<?php
namespace Zao\ZCSDK\Services;

class Coupon_Services extends API_Request {
	protected $args;

	protected function init() {
		$this->set_endpoint( 'coupon_services' );
	}

}
