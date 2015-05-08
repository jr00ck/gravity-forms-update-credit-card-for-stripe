<?php

require_once (__DIR__.'/includes/activate.php');
require_once (__DIR__.'/includes/stripe.php');
require_once (__DIR__.'/includes/ui.php');

class GFUCC4S
{
    private $Activate;
    private $Stripe;
    private $UI;

	// Register any hooks here.
	public function __construct()
	{
		$this->Activate = new GFUCC4S_Activate();
		$this->Stripe = new GFUCC4S_Stripe();
		$this->UI = new GFUCC4S_UI($this->Stripe);
	}
}