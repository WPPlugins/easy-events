<?php
/*
Plugin Name: Easy Events
Description: This is a events programme management plugin and widget.
Author: BrokenCrust
Version: 1.0.1
Author URI: http://brokencrust.com/
Plugin URI: http://brokencrust.com/plugins/easy-events/
License: GPLv2 or later
*/

/*  Copyright 2009-15 BrokenCrust

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/* Activation, Deactivation and Uninstall */

require_once(plugin_dir_path(__FILE__).'easy-events-init.class.php');

register_activation_hook(__FILE__, array('easy_events_init', 'on_activate'));

register_deactivation_hook(__FILE__, array('easy_events_init', 'on_deactivate'));


/* Include the widget code */

require_once(plugin_dir_path(__FILE__).'/easy-events-widget.php');


/* Include the admin list table class */

require_once(plugin_dir_path(__FILE__).'/easy-events-list-table.class.php');


/* Add plugin CSS for the widget and shortcode */

function easy_events_load_styles(){
	wp_enqueue_style('easy-events', plugin_dir_url(__FILE__).'easy-events.css');
}

add_action('wp_enqueue_scripts', 'easy_events_load_styles');


/* Add easy event item to the Admin Bar "New" drop down */

function easy_events_add_event_to_menu() {
	global $wp_admin_bar;

	if (!current_user_can('manage_easy_events') || !is_admin_bar_showing()) { return; }

	$wp_admin_bar->add_node(array(
		'id'     => 'add-easy-event',
		'parent' => 'new-content',
		'title'  => __('Easy Event', 'easy-events'),
		'href'   => admin_url('admin.php?page=easy-events'),
		'meta'   => false));
}

add_action('admin_bar_menu', 'easy_events_add_event_to_menu', 999);


/* Add easy events menu to the main admin menu */

function easy_events_add_menu() {
	global $easy_events;

	$easy_events = add_object_page(__('Easy Events', 'easy-events'), __('Easy Events', 'easy-events'), 'manage_easy_events', 'easy-events', 'easy_events', 'dashicons-list-view');
	add_action('load-'.$easy_events, 'easy_events_add_help_tab');
	add_action('load-'.$easy_events, 'easy_events_load_admin_css');
}

add_action('admin_menu', 'easy_events_add_menu');


/* Add sub-menu for event types */

function easy_events_add_events_submenu() {

	add_submenu_page( 'easy-events', __('Easy Events', 'easy-events'), __('Programmes', 'easy-events'), 'manage_easy_events', 'edit-tags.php?taxonomy=programme' );
}

add_action('admin_menu', 'easy_events_add_events_submenu');


/* Highlight the correct top level menu */

function easy_events_menu_correction($parent_file) {
	global $current_screen;

	$taxonomy = $current_screen->taxonomy;

	if ($taxonomy == 'programme') { $parent_file = 'easy-events'; }

	return $parent_file;
}

add_action('parent_file', 'easy_events_menu_correction');


/* Add plugin settings */

add_action('admin_menu', 'easy_events_options_menu');

function easy_events_options_menu() {
	add_options_page('Easy Events Options', 'Easy Events', 'manage_options', 'easy-events-settings', 'easy_events_options');
}

function easy_events_options() {
	if (!current_user_can('manage_options'))  {
		wp_die(__('You do not have sufficient permissions to access this page.', 'easy-events'));
	}
		?>
	<div class="wrap">
		<h2><?php _e('Easy Events Options', 'tidh'); ?></h2>
		<form action="options.php" method="post">
			<?php settings_fields('easy_events_options'); ?>
			<?php do_settings_sections('easy-events'); ?>
			<p class="submit"><input name="Submit" type="submit" class="button-primary" value="<?php esc_attr_e('Save Changes'); ?>" /></p>
		</form>
	</div>
<?php
}

add_action('admin_init', 'easy_events_admin_init');

function easy_events_admin_init(){
	register_setting('easy_events_options', 'easy_events_options', 'easy_events_options_validate');
	add_settings_section('easy_events_admin', __('Easy Events Settings', 'easy-events'), 'easy_events_admin_section_text', 'easy-events');
	add_settings_field('date_format', __('Event Date Format', 'easy-events'), 'easy_events_date_format', 'easy-events', 'easy_events_admin');
}

function easy_events_admin_section_text() {
	echo '<p>'.__('Settings for the administration pages.', 'easy-events').'</p>';
}

function easy_events_date_format() {
	$options = get_option('easy_events_options');
	$formats = array(1 => '%Y-%m-%d', 2 => '%m-%d-%Y', 3 => '%d-%m-%Y');
	$labels = array(1 => __('Year First (YYYY-MM-DD)','easy-events'), 2 => __('Month First (MM-DD-YYYY)','easy-events'), 3 => __('Day First (DD-MM-YYYY)', 'easy-events'));
	echo '<select id="easy_events_date_format" name="easy_events_options[date_format]">';
	for ($p = 1; $p < 4; $p++) {
		if ($formats[$p] == $options['date_format']) {
			echo '<option selected="selected" value="'.$formats[$p].'">'.$labels[$p].'</option>';
		} else {
			echo '<option value="'.$formats[$p].'">'.$labels[$p].'</option>';
		}
	}
	echo "</select>";
}

function easy_events_options_validate($input) {

	// nowt

	return $input;
}


/* Add Help and Screen Options tabs */

function easy_events_add_help_tab () {
	global $easy_events, $EventListTable;

	$screen = get_current_screen();

	if ($screen->id != $easy_events) { return; }

	$screen->add_help_tab(array(
			'id'	=> 'easy_events_overview',
			'title'	=> __('Overview', 'easy-events'),
			'content'	=> '<p>'.__('This page provides the ability for you to add, edit and remove one or more programmes of events that you wish to display via the Easy Events widget or shortcode.', 'easy-events').'</p>',
	));
	$screen->add_help_tab(array(
			'id'	=> 'easy_events_date_format',
			'title'	=> __('Date Format', 'easy-events'),
			'content'	=> '<p>'.sprintf(__('You must enter a full date in the format %s - for example the 20<sup>th</sup> November 1497 should be entered as %s.', 'easy-events'), easy_events_date(), easy_events_date('example')).'</p>',
	));
	$screen->add_help_tab(array(
			'id'	=> 'easy_events_names',
			'title'	=> __('Event Titles', 'easy-events'),
			'content'	=> '<p>'.__('You must enter title for the event - for example <em>Red Shirts vs. Black Feet</em> or <em>Branch Outing to the West Coast</em> or <em>Prof. Brian Cox will play a unpluged session with D:Ream</em>.', 'easy-events').'</p>',
	));
	$screen->add_help_tab(array(
			'id'	=> 'easy_events_programmes',
			'title'	=> __('Programmes', 'easy-events'),
			'content'	=> '<p>'.__('You can choose a programme for each event from a list of programmes which you can enter on the Programmes screen.  A programme is optional.', 'easy-events').'</p>',
	));
	$screen->add_help_tab(array(
			'id'	=> 'easy_events_shortcode',
			'title'	=> __('Shortcode', 'easy-events'),
			'content'	=> '<p>'.__('You can add a easy-events shortcode to any post or page to display the list of all events or, for a given programme.', 'easy-events').'</p><p>'.__('There are two optional attributes for the shortcode:', 'easy-events').'</p><ul><li>'.__('p (programme slug)- shows events for the given programme.', 'easy-events').'</li><li>'.__('h (header text)- adds a header to the event list.', 'easy-events').'</li></ul><p>'.__('Example use:', 'easy-events').'</p><p>'.__('[easy-events] - This shows a list of all events for all programmes.', 'easy-events').'</p><p>'.__('[easy-events p="milestones" h="Bob\'s Events"] - This shows all events assigned to the programme (slug) of milestones and a list header of Bob\'s Events.', 'easy-events').'</p>',
	));
	$screen->set_help_sidebar('<p><b>'.__('Easy Events', 'easy-events').'</b></p><p><a href="http://brokencrust.com/plugins/easy-events">'.__('Plugin Information', 'easy-events').'</a></p><p><a href="http://wordpress.org/support/plugin/easy-events">'.__('Support Forum', 'easy-events').'</a></p><p><a href="http://wordpress.org/support/view/plugin-reviews/easy-events">'.__('Rate and Review', 'easy-events').'</a></p>');
	$screen->add_option('per_page', array('label' => __('Easy Events', 'easy-events'), 'default' => 10, 'option' => 'easy_events_per_page'));

	add_filter("mce_buttons", "easy_events_editor_buttons", 0);
	add_filter("mce_buttons_2", "easy_events_editor_buttons_2", 0);

	$EventListTable = new easy_events_list_table();
}

/* Change the options for the editor */

function easy_events_editor_buttons($buttons) {
	return array("bold", "italic", "underline", "strikethrough", "charmap", "link", "unlink", "undo", "redo");
}

function easy_events_editor_buttons_2($buttons) {
	return array();
}


/* Set the screen options */

function easy_events_set_option($status, $option, $value) {
	return $value;
}

add_filter('set-screen-option', 'easy_events_set_option', 10, 3);


/* Load the admin css only on the tdih pages */

function easy_events_load_admin_css(){
	add_action('admin_enqueue_scripts', 'easy_events_enqueue_styles');
}

function easy_events_enqueue_styles() {
	wp_enqueue_style('easy-events', plugin_dir_url(__FILE__).'easy-events.css');
}

/* Display main admin screen */

function easy_events() {
	global $wpdb, $EventListTable;

	require_once(plugin_dir_path(__FILE__).'/easy-events-list-table.class.php');

	$EventListTable->prepare_items();

	if ($EventListTable->show_main_section) {

		?>
			<div id="easy-events" class="wrap">
				<h2><?php _e('Easy Events', 'easy-events'); ?><?php if (!empty($_REQUEST['s'])) { printf('<span class="subtitle">'.__('Search results for &#8220;%s&#8221;', 'easy-events').'</span>', esc_html(stripslashes($_REQUEST['s']))); } ?></h2>
				<div id="ajax-response"></div>
				<div id="col-right">
					<div class="col-wrap">
						<div class="form-wrap">
							<form class="search-events" method="post">
								<input type="hidden" name="action" value="search" />
								<?php $EventListTable->search_box(__('Search Events', 'easy-events'), 'event_date' ); ?>
							</form>
							<form id="events-filter" method="post">
								<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
								<?php $EventListTable->display() ?>
							</form>
						</div>
					</div>
				</div>
				<div id="col-left">
					<div class="col-wrap">
						<div class="form-wrap">
							<h3><?php _e('Add New Event', 'easy-events'); ?></h3>
							<form id="addevent" method="post" class="validate">
								<input type="hidden" name="action" value="add" />
								<input type="hidden" name="page" value="<?php echo $_REQUEST['page']; ?>" />
								<?php wp_nonce_field('easy_events'); ?>
								<div class="form-field form-required">
									<label for="event_date_v"><?php _e('Date', 'easy-events'); ?></label>
									<input type="date" name="event_date_v" id="event_date_v" value="" required="required" placeholder="<?php echo easy_events_date(); ?>" />
									<p><?php printf(__('The date of the event (enter date in %s format).', 'easy-events'), easy_events_date()); ?></p>
								</div>
								<div class="form-field form-required">
									<label for="event_title_v"><?php _e('Title', 'easy-events'); ?></label>
									<?php wp_editor('', 'event_title_v', array('media_buttons' => false, 'textarea_rows' => 3)); ?>
									<p><?php _e('The name of the event.', 'easy-events'); ?></p>
								</div>
								<div class="form-field">
									<label for="event_place_v"><?php _e('Place', 'easy-events'); ?></label>
									<input type="text" id="event_place_v" name="event_place_v" placeholder="Event Location" />
									<p><?php _e('The location of the event (optional).', 'easy-events'); ?></p>
								</div>
								<div class="form-field">
									<label for="event_programme_v"><?php _e('Programme', 'easy-events'); ?></label>
									<select name="event_programme_v" id="event_programme_v">
										<?php

										$programmes = get_terms('programme', 'hide_empty=0');

										echo "<option class='theme-option' value=''>".__('none', 'easy-events')."</option>\n";

										if (count($programmes) > 0) {
											foreach ($programmes as $programme) {
												echo "<option class='theme-option' value='" . $programme->slug . "'>" . $programme->name . "</option>\n";
											}
										}
										?>
									</select>
									<p><?php _e('The programme to which this event belongs.', 'easy-events'); ?></p>
								</div>
								<p class="submit">
									<input type="submit" name="submit" class="button button-primary" value="<?php _e('Add New Event', 'easy-events'); ?>" />
								</p>
							</form>
						</div>
					</div>
				</div>
			</div>

		<?php

	}
}


/* Display dates in the chosen order */

function easy_events_date($type = 'format') {
	$options = get_option('easy_events_options');

	switch ($options['date_format']) {

	case '%m-%d-%Y':
				$format = 'MM-DD-YYYY';
				$example = '11-20-1497';
				break;

	case '%d-%m-%Y':
				$format = 'DD-MM-YYYY';
				$example = '20-11-1497';
				break;

	default:
				$format = 'YYYY-MM-DD';
				$example = '1497-11-20';
	}

	$result = ($type == 'example' ? $example : $format);

	return $result;
}


/* Register Programme taxonomy */

function easy_events_build_taxonomies() {

	$labels = array(
		'name' => _x('Programmes', 'taxonomy general name', 'easy-events'),
		'singular_name' => _x('Programme', 'taxonomy singular name', 'easy-events'),
		'search_items' =>  __('Search Programmes', 'easy-events'),
		'popular_items' => __('Popular Programmes', 'easy-events'),
		'all_items' => __('All Programmes', 'easy-events'),
		'parent_item' => null,
		'parent_item_colon' => null,
		'edit_item' => __('Edit Programme', 'easy-events'),
		'update_item' => __('Update Programme', 'easy-events'),
		'add_new_item' => __('Add New Programme', 'easy-events'),
		'new_item_name' => __('New Programme Name', 'easy-events'),
		'separate_items_with_commas' => __('Separate programme with commas', 'easy-events'),
		'add_or_remove_items' => __('Add or remove programmes', 'easy-events'),
		'choose_from_most_used' => __('Choose from the most used programmes', 'easy-events'),
		'menu_name' => __('Programmes', 'easy-events'),
	);

	$args = array(
		'labels'            => $labels,
		'public'            => false,
		'show_in_nav_menus' => false,
		'show_ui'           => true,
		'query_var'         => false
	);

	register_taxonomy('programme', 'easy_event', $args);
}

add_action('init', 'easy_events_build_taxonomies', 0);


/* Change Programme taxonomy screen column title */

function easy_events_manage_programme_event_column( $columns ) {

	unset( $columns['posts'] );

	$columns['events'] = __('Events', 'easy-events');

	return $columns;
}

add_filter( 'manage_edit-programme_columns', 'easy_events_manage_programme_event_column' );


/* Change Programme taxonomy screen count and link */

function easy_events_manage_programme_column($display, $column, $term_id) {

	if ('events' === $column) {
		$term = get_term($term_id, 'programme');
		echo '<a href="admin.php?page=easy-events&programme='.$term->slug.'">'.$term->count.'</a>';
	}
}

add_action('manage_programme_custom_column', 'easy_events_manage_programme_column', 10, 3);


/* Register easy_event post type */

function easy_events_register_post_types() {

	$labels = array(
		'name' => _x('Events', 'post type general name', 'easy-events'),
		'singular_name' => _x('Event', 'post type singular name', 'easy-events'),
		'add_new' => _x('Add New', 'event', 'easy-events'),
		'add_new_item' => __('Add New Event', 'easy-events'),
		'edit_item' => __('Edit Event', 'easy-events'),
		'new_item' => __('New Event', 'easy-events'),
		'all_items' => __('All Events', 'easy-events'),
		'view_item' => __('View Event', 'easy-events'),
		'search_items' => __('Search Events', 'easy-events'),
		'not_found' =>  __('No events found', 'easy-events'),
		'not_found_in_trash' => __('No events found in Trash', 'easy-events'),
		'parent_item_colon' => null,
		'menu_name' => __('Events', 'easy-events')
	);

	$args = array(
		'labels' => $labels,
		'public' => false
	);

	register_post_type('easy_event', $args );
}

add_action('init', 'easy_events_register_post_types');


/* Add Settings to plugin page */

function easy_events_plugin_action_links($links, $file) {
	static $this_plugin;

	if (!$this_plugin) { $this_plugin = plugin_basename(__FILE__); }

	if ($file == $this_plugin) {
		$settings_link = '<a href="'.get_bloginfo('wpurl').'/wp-admin/admin.php?page=easy-events-settings">'.__('Settings', 'easy-events').'</a>';
		array_unshift($links, $settings_link);
	}

	return $links;
}

add_filter('plugin_action_links', 'easy_events_plugin_action_links', 10, 2);


/* Enqueue the jQuery for the shortcode */

function easy_events_scripts() {
	wp_enqueue_script('easy-events-js', plugins_url( '/easy-events.js', __FILE__ ), array('jquery'));
}

add_action('wp_enqueue_scripts', 'easy_events_scripts');


/* Add easy-events shortcode */

function easy_events_shortcode($atts) {
	global $wpdb;

	extract(shortcode_atts(array('p' => '*', 'h' => '*'), $atts));

	$p == '*' ? $filter = '' : ($p == '' ? $filter = " AND ts.slug IS NULL" : $filter = " AND ts.slug='".$p."'");

	$events = $wpdb->get_results("SELECT p.post_title AS event_title, p.post_content AS event_place, DATE_FORMAT(p.post_date, '%d %b %Y') AS event_date, CASE WHEN DATE(p.post_date) < DATE(NOW()) THEN 'easy-events-past' WHEN DATE(p.post_date) = DATE(NOW()) THEN 'easy-events-today' ELSE 'easy-events-future' END AS class, ts.name AS programme, ts.slug AS slug, ts.description AS description FROM ".$wpdb->prefix."posts p LEFT JOIN (SELECT tr.object_id, t.name, t.slug, tt.description FROM ".$wpdb->prefix."terms t LEFT JOIN ".$wpdb->prefix."term_taxonomy tt ON t.term_id = tt.term_id LEFT JOIN ".$wpdb->prefix."term_relationships tr ON tt.term_taxonomy_id = tr.term_taxonomy_id WHERE tt.taxonomy='programme') ts ON p.ID = ts.object_id WHERE p.post_type = 'easy_event' ".$filter." ORDER BY DATE_FORMAT(p.post_date, '%Y %m %d') ASC, ts.slug ASC");

	if (!empty($events)) {

		$easy_event_text = '<div class="easy-events-list"><table class="table table-hover table-ndar"><thead>';

		$p == '*' ? $columns = 4 : $columns = 2;

		$h == '*' ? $header = '' : $header = $h;

		$easy_event_text .= '<th colspan="'.$columns.'">'.$header.'</th>';

		$easy_event_text .= '<th><a class="easy-events-show-past pull-right">Show Past Events &#9658;</a></th></thead>';

		foreach ($events as $e => $values) {

			$easy_event_text .= '<tr class="'.$events[$e]->class.'">';

			if ($p == '*') {

				$slug_class = $events[$e]->slug ? $events[$e]->slug.'-text' : '';

				$easy_event_text .= '<td class="colour"><span class="fa fa-square '.$slug_class.' '.$events[$e]->class.'" title="'.$events[$e]->description.'"> </span></td><td class="event_programme"><span>'.$events[$e]->programme.'</span></td>';
			}

			$easy_event_text .= '<td>'.$events[$e]->event_date.'</td><td>'.$events[$e]->event_title.'</td><td>'.do_shortcode($events[$e]->event_place).'</td></tr>';
		}
		$easy_event_text .= '</tbody></table></div>';
	} else {
		$easy_event_text = __('No Events', 'easy-events');
	}

	return $easy_event_text;
}

add_shortcode('easy-events', 'easy_events_shortcode');


/* Add text domain */

load_plugin_textdomain('easy-events', false, basename(dirname(__FILE__)).'/languages');

?>