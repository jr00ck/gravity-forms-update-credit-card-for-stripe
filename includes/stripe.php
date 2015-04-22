<?php

/*
This class is responsible for all interactivity with the Stripe API.
*/

class GFUCC4S_Stripe
{

	private $ApiKeySetup = false;

	public function __construct()
	{
		// TODO: Set options

	}


	// Responsible for initializing Stripe API communication. This needs to be
	// called before any stripe methods will work properly.
	public function SetupApiKey()
	{
		if ($this->ApiKeySetup) return;

		// Get the Stripe library files
		if (!class_exists('Stripe'))
			require_once( WP_PLUGIN_DIR . '/gravityformsstripe/includes/stripe-php/lib/Stripe.php' );
		
		// get Stripe API Key from Gravity Forms Stripe Add-on settings
		$stripe_settings = get_option( 'gravityformsaddon_gravityformsstripe_settings' );
		$api_key = $stripe_settings['api_mode'] == 'test' ? $stripe_settings['test_secret_key'] : $stripe_settings['live_secret_key'];

		// set Stripe API Key
		Stripe::setApiKey($api_key);

		$this->ApiKeySetup = true;
	}

	// Responsible for retrieving an event from Stripe. Use this to process
	// a Stripe WebHook to ensure the event really came from Stripe.
	// $args:
	//		- $event_id => String; Should be a valid Stripe Event ID
	public function RetrieveEvent($event_id)
	{
		$this->SetupApiKey();
		return Stripe_Event::retrieve($event_id);
	}

	// Responsible for retrieving a customer from Stripe
	// $args:
	//		- $customer_id => String; Should be a valid Stripe Customer ID
	public function RetrieveCustomer($customer_id)
	{
		$this->SetupApiKey();
		return Stripe_Customer::retrieve($customer_id);
	}

	// This is responsible for sending a charge failure message to a customer
	// when we get the notification via a Stripe WebHook. We want to keep the
	// message itself outside of the method because other objects might use
	// this method and want to send differnt message for different situations.
	// $args:
	//		- $customer => Customer; Should be a valid Stripe Customer
	//		- $mail => Array;
	//			- from => String; Who the mail is from
	//			- subject => String; The subject of the email
	//			- body => String; The body of the email in HTML format.
	public function NotifyCustomerChargeFailed($customer, $mail)
	{
		//var_dump('NotifyCustomerChargeFailed', $customer, $mail);

		$this->SetupApiKey();

		add_filter('wp_mail_content_type', array($this, 'SetNotificationContentType'));
			wp_mail($customer->email, $mail['subject'], $mail['body'], 'From: ' . $mail['from']);
		remove_filter('wp_mail_content_type', array($this, 'SetNotificationContentType'));
	}

		// TODO: Consider a more general way to accomplish this. Perhaps move this to a utility of sorts.
		public function SetNotificationContentType($content_type)
		{
			return 'text/html';
		}


	// Responsible for updating the customer credit card. 
	// $args:
	//		- $customer_id => String; Should be a valid Stripe Customer ID
	//		- $response => String; A valid card response from the stripe.js api
	public function UpdateCustomerCreditCard($customer_id, $response)
	{
		$this->SetupApiKey();

		$customer = $this->RetrieveCustomer($customer_id);
		$customer->card = $response->id;
		$save = $customer->save();

		return $save;
	}
}