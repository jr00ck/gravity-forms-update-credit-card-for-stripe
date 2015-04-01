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

	// Delegates processing of different Stripe WebHook event types
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

	// Handles the credit card update form. We have the form settings hard coded for now
	// but we will need to create an admin interface for the user to configure the form
	// at a later date.
	public function ProcessUpdateCreditCardForm()
	{
		$qs = $_POST['gform_ajax'];
		if (empty($qs)) return;

		$form_id = $this->GetKeyValue($qs, 'form_id');
		if ($form_id != self::OPTIONS_FORM_ID) return;

		// Get the logged in user id and stripe user id
		$customer_id = $this->GetLoggedInUser();

		// Grab all the fields. Minmum we need is:
		// - Card Token
		// - add more here...
		$response = json_decode(str_replace('\\"', '"', $_POST['stripe_response']));
		$this->Stripe->UpdateCustomerCreditCard($customer_id, $response->card->id);
	}

		// Responsible for checking the current WordPress login if any. Then uses that to
		// retrieve a Stripe Customer ID from the WP meta data.
		private function GetLoggedInUser()
		{
			return is_user_logged_in() ? get_user_meta(get_current_user_id(), '_stripe_customer_id', true) : false;
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