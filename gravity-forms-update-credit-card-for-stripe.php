<?php
/*
Plugin Name: Gravity Forms Update Credit Card for Stripe
Plugin URI: https://github.com/jr00ck/gravity-forms-update-credit-card-for-stripe
Description: Use Gravity Forms to automatically notify your Stripe customers when their subscription payment fails and allow them to update their card on file to resume payments.
Version: 1.0
Author: FreeUp
Author URI: http://freeupwebstudio.com
Author Email: jeremy@freeupwebstudio.com
Github Plugin URI: https://github.com/jr00ck/gravity-forms-update-credit-card-for-stripe
*/

if (!defined('ABSPATH')) die();


if (!class_exists(GFUCC4S))
	require_once('_gfucc4s.php');

global $GFUCC4S;
$GFUCC4S = new GFUCC4S(); 

