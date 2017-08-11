<?php

class easy_events_init {

	public function __construct($case = false) {

		switch($case) {
			case 'activate' :
				$this->easy_events_activate();
			break;

			case 'deactivate' :
				$this->easy_events_deactivate();
			break;

			default:
				wp_die('Invalid Access');
			break;
		}
	}

	public function on_activate() {
		new easy_events_init('activate');
	}

	public function on_deactivate() {
		new easy_events_init('deactivate');
	}

	private function easy_events_activate() {
		global $wpdb, $easy_events_db_version;

		add_option('easy_events_options', array('date_format'=>'%Y-%m-%d'));

		$role = get_role('administrator');

		if(!$role->has_cap('manage_easy_events')) { $role->add_cap('manage_easy_events'); }

	}

	private function easy_events_deactivate() {
		// do nothing
	}
}

?>