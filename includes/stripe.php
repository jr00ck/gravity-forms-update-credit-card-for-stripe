<?php

/*
This class is responsible for all interactivity with the Stripe API.
*/

class GFUCC4S_Stripe
{

	private $ApiKeySetup = false;

	// TODO: Set options
	public function __construct()
	{

	}


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

	public function RetrieveEvent($event_id)
	{
		$this->SetupApiKey();
		return Stripe_Event::retrieve($event_id);
	}

	public function RetrieveCustomer($customer_id)
	{
		$this->SetupApiKey();
		return Stripe_Customer::retrieve($customer_id);
	}

	public function NotifyCustomerChargeFailed($customer_id, $mail)
	{
		$this->SetupApiKey();

		$customer = $this->RetrieveCustomer($customer_id);
		wp_mail($customer->email, $mail['subject'], $mail['body'], 'From: ' . $mail['from']);
	}

	public function UpdateCustomerCreditCard($customer_id, $card_token)
	{
		$this->SetupApiKey();

		// $customer = $this->RetrieveCustomer($customer_id);
		// $customer->card = $card_token;
		// $customer->save();
	}
}