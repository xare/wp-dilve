<?php

namespace Inc\Dilve\Pages;

use Inc\Dilve\Api\SettingsApi;
use Inc\Dilve\Base\BaseController;
use Inc\Dilve\Api\Callbacks\AdminCallbacks;

class Dashboard extends BaseController {
    public $settings;
    public $pages = [];
	public $subpages = []; // Add this line to define subpages
    public $callbacks;


    public function register() {
        $this->settings = new SettingsApi();
        $this->callbacks = new AdminCallbacks();
        $this->setPages();
		$this->setSubpages();
        $this->setSettings();
		$this->setSections();
		$this->setFields();
        $this->settings
			->addPages( $this->pages )
			->withSubPage( 'Dashboard' )
			->addSubPages( $this->subpages )
			->register();
        /* $this->storeDilve(); */

    }

    /* public function storeDilve() {
        $option = get_option('Dilve') ?: '';

    } */

    public function setPages(){
		$this->pages = [
			[
				'page_title' => __('Dilve','Dilve'),
				'menu_title' =>  __('Dilve','Dilve'),
				'capability' => 'manage_options',
				'menu_slug' => 'Dilve',
				'callback' => [$this->callbacks, 'adminDashboard'] ,
				'icon_url' => 'dashicons-admin-plugins',
				'position' => 110
			]
		];
	}

	// Define this new method to add your subpages
    public function setSubpages() {
        $this->subpages = [
            [
                'parent_slug' => 'dilve', // Parent menu slug
                'page_title' => 'Dilve Logger', // Page title
                'menu_title' => 'Dilve Logger', // Menu title
                'capability' => 'manage_options', // Capability
                'menu_slug' => 'dilve_logger', // Menu slug
                'callback' => [$this->callbacks, 'adminDilveLogger'] // Callback function, define it in AdminCallbacks class
            ]
        ];
    }
    public function setSettings()
	{
		$args = [
			[
				'option_group'=> 'dilve_settings',
				'option_name' => 'dilve_settings',
				'callback' => [$this->callbacks, 'textSanitize']
            ]
		];

		$this->settings->setSettings( $args );

		// Save the default option if it doesn't exist
		if ( !get_option('dilve_settings') ) {
			$default_settings = [
				'dilve_user' => ''
			];
			update_option('dilve_settings', $default_settings);
		}
	}

    public function setSections()
	{
		$args = [
					[
						'id'=> 'dilve_admin_index',
						'title' => 'Settings Manager',
						'callback' => [$this->callbacks , 'adminSectionManager'],
						'page' => 'dilve' //From menu_slug
					]
		];
		$this->settings->setSections( $args );
	}

    public function setFields()
	{
		$args = [
                    [
						'id'=> 'dilve_user',
						'title' => 'Dilve User Name',
						'callback' => [$this->callbacks, 'textField'],
						'page' => 'dilve', //From menu_slug
						'section' => 'dilve_admin_index',
						'args' => [
							'option_name' => 'dilve_settings',
							'label_for' => 'dilve_user',
							'class' => 'regular-text'
						]
					],
					[
						'id'=> 'dilve_pass',
						'title' => 'Dilve Password',
						'callback' => [$this->callbacks, 'textField'],
						'page' => 'dilve', //From menu_slug
						'section' => 'dilve_admin_index',
						'args' => [
							'option_name' => 'dilve_settings',
							'label_for' => 'dilve_pass',
							'class' => 'regular-text'
						]
					]
                ];
		$this->settings->setFields( $args );
	}
}