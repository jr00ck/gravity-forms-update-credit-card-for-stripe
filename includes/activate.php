<?php

/*
This class is responsible for the entire activation process for the plugin
itself. Any initialization, version checking, etc should all happen here.
*/

class GFUCC4S_Activate
{
	const VERSION = '0.1.0.dev';
 	const DB_VERSION = '1';
 
    const OPTIONS_VERSION = 'gfucc4s_version';
    const OPTIONS_DB_VERSION = 'gfucc4s_db_version';

    public $PluginName;
    private $Deactivate = false;

	public function __construct()
	{
		$dir = explode('/', plugin_basename(__DIR__))[0];
		$this->PluginName = sprintf('%s/%s.php', $dir, $dir);

		register_activation_hook($this->PluginName, array($this, 'Activate'));

		add_action('admin_init', array($this, 'AdminInit'));
	}

	// Activate our plugin.
	public function Activate()
	{
		if (!$this->CheckDependencies()) return;

		$this->InitOptions();
		$this->MaybeUpdate();
	}

		// Dependencies are:
		// - GravityForms
		// - GravityFormsStripeAddOn
		private function CheckDependencies()
		{
			return (is_plugin_active('gravityforms/gravityforms.php')
				&& is_plugin_active('gravityformsstripe/stripe.php'));
		}

		// This method will ensure all options at least exist. Keeping them
		// set appropriately will be handled elsewhere.
		private function InitOptions() 
		{
			update_option(self::OPTIONS_VERSION, self::VERSION);
			update_option(self::OPTIONS_DB_VERSION, self::DB_VERSION);
		}

		// Make sure we are up-to-date with the latest version
		private function MaybeUpdate()
		{
			if (get_option(self::OPTIONS_DB_VERSION) >= self::DB_VERSION) return;

			// TODO: Do update operations here...

		}

	// 
	public function AdminInit()
	{
		if (!$this->CheckDependencies())
		{
			deactivate_plugins($this->PluginName);
			add_action('admin_notices', array($this, 'PluginDeactivatedNotice'));
		}
	}

	public function PluginDeactivatedNotice()
	{
		echo '<div class="error">
			<p>Gravity Forms Update Credit Card for Stripe plugin deactivated! The required dependencies are not installed or activated.</p>
		</div>';

	    remove_action('admin_notices', array($this, 'PluginDeactivatedNotice'));
	}
}