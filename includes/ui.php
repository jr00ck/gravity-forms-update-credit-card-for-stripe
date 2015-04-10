<?php

/*
This class is responsible for all interaction with the user or the
Stripe WebHook.
*/

class GFUCC4S_UI
{
	const STRIPE_WEBHOOK = 'fu_stripe_handler';

	// TODO: Hard code these values before testing.
	const OPTIONS_FORM_ID = '1';
	const UPDATE_CC_LINK = 'http://freeupplugins.d.stimulimedia.net:8080/?page_id=4';

	private $Stripe;

	public function __construct($stripe)
	{
		$this->Stripe = $stripe;

		add_action('init', array($this, 'ProcessWebHook'));
		add_action('init', array($this, 'ProcessUpdateCreditCardForm'));
	}



	// ** ENTRY POINT: called by "init" hook

	// Delegates processing of different Stripe WebHook event types
	// SAMPLE:
	// {
	//   "id": "tok_15mvga2Z364Xq6RATsH6j9EF",
	//   "livemode": false,
	//   "created": 1427932552,
	//   "used": false,
	//   "object": "token",
	//   "type": "card",
	//   "card": {
	//     "id": "card_15mvga2Z364Xq6RAp2fnDhYB",
	//     "object": "card",
	//     "last4": "4242",
	//     "brand": "Visa",
	//     "funding": "credit",
	//     "exp_month": 12,
	//     "exp_year": 2034,
	//     "country": "US",
	//     "name": "asdfsad",
	//     "address_line1": null,
	//     "address_line2": null,
	//     "address_city": null,
	//     "address_state": null,
	//     "address_zip": null,
	//     "address_country": null,
	//     "cvc_check": null,
	//     "address_line1_check": null,
	//     "address_zip_check": null,
	//     "dynamic_last4": null,
	//     "customer": null
	//   },
	//   "client_ip": "198.23.71.113"
	// }
	public function ProcessWebHook()
	{
		if (!(isset($_GET[self::STRIPE_WEBHOOK]) && $_GET[self::STRIPE_WEBHOOK] == '1'))
			return;
		

		// Retrieve the request's body and parse it as JSON
		$input = @file_get_contents("php://input");
		$event_json = json_decode($input);
		$event_id = $event_json->id;

		if (empty($event_id)) return;

		$event = $this->Stripe->RetrieveEvent($event_id);

		switch($event->type)
		{
			case 'charge.failed':
				$this->ProcessChargeFailed($event);
			break;

			// Add other types here if we're interested in them...
		}
	}

		// Sends an email to the user prompting them to update their credit card information.
		// Args:
		//		- $event : \Stripe\Event object.
		private function ProcessChargeFailed($event)
		{
			// TODO: Figure out how this is supposed to get set.
			$customer_id = '1'; // $invoice->customer was used in the original plugin, but I'm not sure where $invoice comes from or how it gets set.
			$customer = $this->Stripe->RetrieveCustomer($customer_id);

			$body = sprintf('
				Hello %s,\n\n
				We have failed to process your payment.\n\n
				Please click here to <a href="%s?customer_id=%s">update your payment information</a>.\n\n
				Thank you!',
				$customer->description,
				self::UPDATE_CC_LINK,
				$customer_id
			);

			$mail = array(
				'from' => '"' . html_entity_decode(get_bloginfo('name')) . '" <' . get_bloginfo('admin_email') . '>',
				'subject' => __('Failed Payment', self::STRIPE_WEBHOOK),
				'body' => $body
			);

			$this->Stripe->NotifyCustomerChargeFailed($customer_id, $mail);
		}

	// ** ENTRY POINT: called by "init" hook

	// Handles the credit card update form. We have the form settings hard coded for now
	// but we will need to create an admin interface for the user to configure the form
	// at a later date.
	// Sample: http://clients-freeupwebstudio-com.freeupinvoice.staging.wpengine.com/update-credit-card/?customer_id=cus_54zi2l1lydemYq
	public function ProcessUpdateCreditCardForm()
	{
		$isForm = $_POST[sprintf('is_submit_%s', self::OPTIONS_FORM_ID)];
		if (empty($isForm)) return;

		// Get the user id to send to stripe
		$customer_id = $this->GetUser();

		$response = json_decode(str_replace('\\"', '"', $_POST['stripe_response']));
		$this->Stripe->UpdateCustomerCreditCard($customer_id, $response);
	}

		// Responsible for grabbing the customer id. Checks the login first, then the post, then the get.
		private function GetUser()
		{
			if (!empty($_POST['customer_id']))
				return $_POST['customer_id'];
			
			if (!empty($_GET['customer_id']))
				return $_GET['customer_id'];
		}

}