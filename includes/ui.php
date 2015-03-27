<?php

/*
This class is responsible for all interaction with the user or the
Stripe WebHook.
*/

class GFUCC4S_UI
{
	const STRIPE_WEBHOOK = 'fu_stripe_handler';

	const OPTIONS_FORM_ID = '1';
	const OPTIONS_FORM_FIELD_CC_TOKEN = 'stripe_response[\'card\'][\'id\']';

	private $Stripe;

	public function __construct($stripe)
	{
		$this->Stripe = $stripe;

		add_action('init', array($this, 'ProcessWebHook'));

		// TODO: Figure out what hook to use here.
		add_action('init', array($this, 'ProcessUpdateCreditCardForm'));
	}

	// Controls the delegation of processing for different Stripe WebHook events
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
				// This is really all we're worried about at this point
				$this->ProcessChargeFailed($event);
			break;
		}
	}

		// Sends an email to the user prompting them to update their credit card information.
		// Args:
		//		- $event : \Stripe\Event object.
		private function ProcessChargeFailed($event)
		{
			$customer_id = '';

			$body = sprintf('
				Hello %s,\n\n
				We have failed to process your payment.\n\n
				Please click here to <a href="[link to gravity form]?customer_id=%s">update your payment information</a>.\n\n
				Thank you!',
				$customer_name,
				$customer_id
			);

			$mail = array(
				'from' => '"' . html_entity_decode(get_bloginfo('name')) . '" <' . get_bloginfo('admin_email') . '>',
				'subject' => __('Failed Payment', self::STRIPE_WEBHOOK),
				'body' => $body
			);

			$this->Stripe->NotifyCustomerChargeFailed($customer_id, $mail);
		}

	// Handles the credit card update form. We have the form settings hard coded for now
	// but we will need to create an admin interface for the user to configure the form.
	// 
	public function ProcessUpdateCreditCardForm()
	{
		$qs = $_POST['gform_ajax'];
		if (empty($qs)) return;

		$form_id = $this->GetKeyValue($qs, 'form_id');
		if ($form_id != self::OPTIONS_FORM_ID) return;

		// Get the logged in user id and stripe user id
		$customer_id = is_user_logged_in() ? get_user_meta(get_current_user_id(), '_stripe_customer_id', true) : false;

		// Grab all the fields. Minmum we need is:
		// - Card Token
		// - add more here...
		$card_token = $_POST['stripe_response'];
var_dump($card_token);
		$this->Stripe->UpdateCustomerCreditCard($customer_id, $card_token);
	}

		// TODO: Consider moving this to a utility type object so other classes can access it.
		private function GetKeyValue($qs, $key)
		{
			$pairs = explode('&', $qs);
			foreach ($pairs as $pair)
			{
				$kv = explode('=', $pair);
				if ($kv[0] == $key)
					return $kv[1];
			}

			return '';
		}
}