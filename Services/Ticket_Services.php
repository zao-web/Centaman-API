<?php
namespace Zao\ZCSDK\Services;

class Ticket_Services extends API_Request {
	protected $args;

	protected function init() {
		return $this->set_endpoint( 'ticket_services' );
	}

	public function get_tickets( $booking_type = null ) {
		$this->set_endpoint( 'TimedTicket' );

		if ( ! empty( $booking_type ) ) {
			$this->set_query_args( array( 'TimedTicketTypeId' => $booking_type ) );
		}

		return $this->dispatch( 'GET' )
			->get_response();
	}

	public function create_contact( $args ) {

		$args = wp_parse_args( $args, array(
			'FirstName' => '',
			'LastName'  => '',
			'Email'     => '',
			'Address'   => array(),
		) );

		$args['Address'] = wp_parse_args( $args['Address'], array(
			'Street1'     => '',
			// 'Street2'     => '',
			'City'        => '',
			'State'       => '',
			'Postalcode'  => '',
			'Country'     => '',
			'HomePhone'   => '',
			// 'WorkPhone'   => '',
			// 'MobilePhone' => '',
		) );

		return $this
			->set_endpoint( 'TimedTicket' )
			->set_args( array( 'body' => wp_json_encode( $args ) ) )
			->dispatch( 'POST' )
			->get_response();
	}

	/**
	 * This method will return TimedTicketType for a booking type.
	 *
	 * If there is no booking type passed to this method, it will return all the TimedTicketType for all the booking type.
	 * If there is no start or end date passed then it will return the timed tickets setup for today's date.
	 *
	 * @param  integer $booking_type_id [description]
	 * @param  string  $start           [description]
	 * @param  string  $end             [description]
	 * @return [type]                   [description]
	 */
	public function get_timed_ticket_types( $booking_type_id = 0, $start = '', $end = '' ) {

		$args = array();

		if ( $booking_type_id ) {
			$args['BookingTypeId'] = $booking_type_id;
		}

		if ( $start ) {
			$args['StartDate'] = $start;
		}

		if ( $end ) {
			$args['EndDate'] = $end;
		}

		$this->set_endpoint( 'TimedTicketType' );

		if ( ! empty( $args ) ) {
			$this->set_query_args( $args );
		}

		return $this->dispatch( 'GET' )
			->get_response();
	}


	public function get_capacity( $timed_ticket_type_id, $start_date ) {
		return $this->set_endpoint( 'TimedTicketType' )
			->set_query_args( array(
				'TimedTicketTypeId' => $timed_ticket_type_id,
				'StartDate'         => $start_date,
			) )
			->dispatch( 'GET' )
			// TODO: Likely need to iterate through this response, as I believe it may return multiple booking types.
			->get_response();
	}

	/**
	 * This method is called to temporarily reserve tickets while the customer completes the transaction.
	 *
	 * Tickets that have been reserved reduce the Vacancy count for other web
	 * sessions as well as desktop Point Of Sale.
	 *
	 * The temporary reservation is removed when the customer completes purchase and the reservation becomes permanent
	 * or when they end the browser session
	 *
	 * @param  [type] $args [description] Expects an array of arrays: array( array( 'TimedTicket' => '2' ), array() )
	 * @return [type]       [description]
	 */
	public function hold_spot( $args ) {
		return $this->set_endpoint( 'TimedTicketType' )
			->set_args( array( 'body' => wp_json_encode( $args ) ) )
			->dispatch( 'POST' )
			->get_response();
	}

	public function create_transaction( $args ) {
		if ( empty( $args ) ) {
			return false;
		}

		$request_objects = array();
		if ( isset( $args[0]['Item'] ) ) {
			foreach ( $args as $rquest_args ) {
				$request_objects[] = self::create_transaction_object( $rquest_args );
			}
		} else {
			$request_objects[] = self::create_transaction_object( $args );
		}

		return $this
			->set_endpoint( 'TimedTicketTransaction' )
			->set_args( array( 'body' => wp_json_encode( $request_objects ) ) )
			->dispatch( 'POST' )
			->get_response();
	}

	public function create_transaction_object( $args ) {
		if ( empty( $args ) || empty( $args['Item'] ) ) {
			return false;
		}

		// Docs: https://tickets.niagaracruises.com/CENTAMAN.API_Staging/Help/Api/POST-ticket_services-TimedTicketTransaction

		$request_object = wp_parse_args( $args, array(
			// (Integer, Required) Internal id for the TimedTicketType(Primary Booking).
			'TimedTicketTypeId' => 0,
			// (String) The description of the TimedTicketType.
			'TimedTicketTypeDescription' => '',
			// (Integer, Required) Centaman booking type id.
			'BookingTypeId' => 0,
			// (Date, Required) The Date for which the booking is made.
			'StartDate' => '',
			// (String) The start time for the booking. whatever value passed in request will return to the response.
			'StartTime' => '',
			// (String) Finish time for the booking. whatever value passed in request will return to the response.
			'EndTime' => '',
			// (String, Required) The reference returned from payment gateway.
			'PaymentReference' => '',
			// (DateTime, Required) Date when transaction is made.
			'TransactionDate' => self::timestamp(),
			// (Integer, Required) The contact id of the person making booking. Should get as part of create_contact() response
			'BookingContactID' => 0,
			// (String) the contact name for the booking.
			'BookingContactName' => '',
			// (Integer) if customer is paying using foreign currency then have to pass currencyid which you can get from GET ticket_services/ForeignCurrency method.
			// 'ForeignCurrencyId' => 0,
		) );

		 // (Integer) These will always be null for request object, it is used for response object.
		// $request_object['ReceiptNo'] = $request_object['BookingId'] = $request_object['BalanceAmount'] = 0;

		// (Integer, Required) The total number of tickets.
		$request_object['TotalTickets'] = 0;

		// (Decimal, Required) Total tax paid for the booking.
		$request_object['TaxPaid'] = 0.0;

		// (Decimal, Required) The total booking cost excluding tax.
		$request_object['BookingCost'] = 0.0;

		// (Array, Required) Array of ticket items.
		$request_object['Item'] = array();

		// Validate Ticket objects
		foreach ( $args['Item'] as $item ) {
			// Validate required fields, data types, etc.
			$item = wp_parse_args( $item, array(
				// (String) TicketDescription returned from GET ticket_services/TimedTicket?TimedTicketTypeId={TimedTicketTypeId}.
				'ItemDescription' => '',
				// (Integer, Required) TicketId of the Ticket if TimedTicket, ExtraId if Extra item.
				'ItemCode' => 0,
				// (Integer, Required) Total Quantity of this item.
				'Quantity' => 0,
				// (Decimal, Required) Unit Tax paid for this item.
				// TODO : Get clarification from Tim on taxes
				'TaxPaid' => 0.0,
				// (Decimal, Required) Unit cost of the item excluding tax.
				'ItemCost' => 0.0,
				// (object) The details of the attendees.
				// 'AttendeesDetails' => array(
					// array(
					// 	'AttendeeFirstName'  => 'sample string 1',
					// 	'AttendeeLastName'   => 'sample string 2',
					// 	'AttendeeMemberCode' => 1,
					// ),
				// ),
				// (Bool, Required) If the item is TimedTicket then this will be false and for extra items this will be true.
				'IsExtraItem' => false,
				// (String) The coupon code applied, This coupon code has to be setup in Centaman.
				// 'CouponCode' => '',
			) );

			// (String) This will always be null for request, It is used for response.
			// $item['Barcode'] = '';
			$total_tax = $item['Quantity'] * $item['TaxPaid'];
			$total_cost = $item['Quantity'] * $item['ItemCost'];

			// (Decimal, Required) Total paid for this item including tax.
			$item['TotalPaid'] = $total_tax + $total_cost;

			$request_object['Item'][] = $item;

			$request_object['TotalTickets'] += $item['Quantity'];
			$request_object['TaxPaid'] += $total_tax;
			$request_object['BookingCost'] += $total_cost;
		}

		// (Decimal, Required) Total deposit paid for the booking including tax.
		$request_object['TotalPaid'] = $request_object['TaxPaid'] + $request_object['BookingCost'];

		return $request_object;
	}

	public function foreign_currency() {
		return $this->set_endpoint( 'ForeignCurrency' )
			->dispatch( 'GET' )
			->get_response();
	}

	protected static function timestamp() {

		$timestamp = current_time( 'mysql' );
		try {
			$tzstring = get_option( 'timezone_string' );
			if ( empty( $tzstring ) ) { // Create a UTC+- zone if no timezone string exists
				$check_zone_info = false;
				if ( 0 == $current_offset ) {
					$tzstring = 'UTC+0';
				} elseif ( $current_offset < 0 ) {
					$tzstring = 'UTC' . $current_offset;
				} else {
					$tzstring = 'UTC+' . $current_offset;
				}
			}

			$datetime = new \DateTime( $timestamp, new \DateTimeZone( $tzstring ) );
			$timestamp = $datetime->format( 'Y-m-d\TH:i:s.uP' ); // RFC3339_EXTENDED

		} catch ( \Exception $e ) {
		}

		return $timestamp;
	}

}
