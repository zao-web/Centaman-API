<?php
namespace Zao\ZCSDK\Services;

class UDF_Services extends API_Request {
	protected $args;

	protected function init() {
		$this->set_endpoint( 'udf_services' );
	}

}
