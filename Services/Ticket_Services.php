<?php
namespace Zao\ZCSDK\Services;

class Ticket_Services extends API_Request {
	protected $args;

	protected function init() {
		$this->set_endpoint( 'ticket_services' );
		return $this;
	}

	public function get_tickets( $booking_type = null ) {
		if ( empty( $booking_type ) ) {
			$this->set_endpoint( 'TimedTicket' )->dispatch( 'GET' );
		} else {
			$this->set_endpoint( 'TimedTicket' )->set_query_args( array( 'TimedTicketTypeId' => $booking_type ) )->dispatch( 'GET' );
		}

		return $this->get_response();
	}

	public function create_contact( $args ) {

		$args = wp_parse_args( $args, array(
			'FirstName' => '',
			'LastName'  => '',
			'Email'     => '',
			'Address'   => array(),
		) );

		// TODO Flesh out address once we know what the fields look like in CMB2.
		/*
		"Address": {
		    "Street1": "sample string 1",
		    "Street2": "sample string 2",
		    "City": "sample string 3",
		    "State": "sample string 4",
		    "Postalcode": "sample string 5",
		    "Country": "sample string 6",
		    "HomePhone": "sample string 7",
		    "WorkPhone": "sample string 8",
		    "MobilePhone": "sample string 9"
		},
		*/

		$this
			->set_endpoint( 'TimedTicket' )
			->set_args( $args )
			->dispatch( 'POST' );

		return $this;
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

		$args = [];

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

		$this->dispatch( 'GET' );

		return $this->get_response();

	}


	public function get_capacity( $timed_ticket_type_id, $start_date ) {

		$this->set_endpoint( 'TimedTicketType' )->set_query_args( array( 'TimedTicketTypeId' => $timed_ticket_type_id, 'StartDate' => $start_date ) )->dispatch( 'GET' );


		// TODO: Likely need to iterate through this response, as I believe it may return multiple booking types.
		return $this->get_response();

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
		$this->set_endpoint( 'TimedTicketType' )->set_args( array( 'body' => $args ) )->dispatch( 'POST' );
	}

	public function create_transaction( $args ) {

		if ( empty( $args ) ) {
			return false;
		}

		// TODO Once we have the ticket objects from CMB2, we should be able to gather most of this.

		$valid_items = [];

		// Validate Ticket objects
		foreach ( $args['Item'] as $item ) {
			// Validate required fields, data types, etc.
			// TODO: Get clarification from Tim on taxes
			$item = array(
				'ItemDescription' => $item['ticket_description'], // TicketDescription returned from GET ticket_services/TimedTicket?TimedTicketTypeId={TimedTicketTypeId}.
				'ItemCode'        => $item['ticket_id'], // TicketId of the Ticket
				'Quantity'        => $item['count'],
				'ItemCost'        => $item['ticket_price'],
				'TotalPaid'       => $item['line_total'],
				'TaxPaid'         => 0,
				'AttendeeName'    => '',
				'Barcode'         => '',
				'IsExtraItem'     => false,
			 );

			 array_push( $valid_items, $item );
		}

		$request_object = array(
			'TimedTicketTypeId'          => $args['timed_ticket_type_id'],
			'TimedTicketTypeDescription' => $args['timed_ticket_type_description'],
			'BookingTypeId'              => $args['booking_type_id'],
			'StartDate'                  => $args['booking_date'],
			'StartTime'                  => $args['booking_start_time'],
			'EndTime'                    => $args['booking_end_time'],
			'PaymentReference'           => $args['transaction_id'], // Should pass through from global Payments
			'BookingCost'                => $args['total'],
			'TotalPaid'                  => $args['total'],
			'BookingContactId'           => $args['member_code'], //Should get as part of create_contact() response
			'TotalTickets'               => $args['total_tickets'],
			'TaxPaid'                    => 0,
			'TransactionDate'            => date( 'Y-m-d' ),
			'BalanceAmount'              => '',
			'ReceiptNo'                  => '',
			'BookingId'                  => '',
			'Item'                       => $valid_items
		);

		$this
			->set_endpoint( 'TimedTicketTransaction' )
			->set_args(
				array(
					'body' => $request_object
				)
			)
			->dispatch( 'POST' );

		return $this->get_response();
	}

}
