<?php

if(!class_exists('WP_List_Table')){ require_once(ABSPATH.'wp-admin/includes/class-wp-list-table.php'); }

class easy_events_list_table extends WP_List_Table {

	public $show_main_section = true;

	private $date_format;

	private $date_description;

	private $per_page;


	/* Construct */

	public function __construct(){
		global $status, $page;

		$options = get_option('easy_events_options');

		$this->date_format = $options['date_format'];

		$this->date_description = $this->easy_events_date();

		$this->per_page = $this->get_items_per_page('events_per_page', 10);

		parent::__construct( array(
			'singular' => 'event',
			'plural'   => 'events',
			'ajax'     => true
		));
	}


	/* Public Functions */

	public function column_default($item, $column_name){
		switch($column_name){
			case 'event_title':
				return $item->event_title;
			case 'event_place':
				return do_shortcode($item->event_place);
			case 'programme':
				return $item->programme === NULL ? '<span class="no-programme">'.__('- none -', 'easy_events').'</span>' : '<a href="admin.php?page=easy-events&programme='.$item->event_slug.'">'.$item->programme.'</a>';
			default:
				return print_r($item, true);
		}
	}

	public function column_event_date($item){

		$actions = array(
			'edit'   => sprintf('<a href="?page=%s&action=%s&id=%s">Edit</a>', $_REQUEST['page'], 'edit', $item->ID),
			'delete' => sprintf('<a href="?page=%s&action=%s&id=%s">Delete</a>', $_REQUEST['page'], 'delete', $item->ID),
		);

		return sprintf('%1$s %2$s', $item->event_date, $this->row_actions($actions));
	}

	public function column_cb($item){
		return sprintf('<input type="checkbox" name="%1$s[]" value="%2$s" />', $this->_args['singular'], $item->ID);
	}

	public function get_bulk_actions() {

		$actions = array(
			'bulk_delete' => 'Delete'
		);
		return $actions;
	}

	public function get_columns(){

		$columns = array(
			'cb'          => '<input type="checkbox" />',
			'event_date'  => 'Event Date',
			'event_title' => 'Event Title',
			'event_place' => 'Event Place',
			'programme'   => 'Programme'
		);
		return $columns;
	}

	public function get_hidden_columns(){
		$columns = (array) get_user_option('manage_easy_event-menucolumnshidden');
		print_r($columns);
		return $columns;
	}

	public function get_sortable_columns() {

		$sortable_columns = array(
			'event_date'  => array('event_date', false),
			'event_title' => array('event_title', false),
			'event_place' => array('event_place', false),
			'programme'   => array('programme', false)
		);
		return $sortable_columns;
	}

 	public function no_items() {
		_e('No events have been found.', 'easy-events');
	}

	public function prepare_items() {
		global $wpdb;

		$per_page = $this->per_page;

		$this->_column_headers = $this->get_column_info();

		$this->process_bulk_action();

		$programme = empty($_REQUEST['programme']) ? '' : " AND ts.slug='".$_REQUEST['programme']."'";

		$filter = (empty($_REQUEST['s'])) ? '' : "AND (p.post_title LIKE '%".like_escape($_REQUEST['s'])."%' OR p.post_date LIKE '%".like_escape($_REQUEST['s'])."%' OR p.post_content LIKE '%".like_escape($_REQUEST['s'])."%' OR ts.name LIKE '%".like_escape($_REQUEST['s'])."%') ";

		$_REQUEST['orderby'] = empty($_REQUEST['orderby']) ? 'event_date' : $_REQUEST['orderby'];

		switch ($_REQUEST['orderby']) {
			case 'event_title':
				$orderby = 'ORDER BY p.post_title ';
				break;
			case 'event_place':
				$orderby = 'ORDER BY p.post_content ';
				break;
			case 'event_date':
				$orderby = 'ORDER BY p.post_date ' ;
				break;
			case 'programme':
				$orderby = 'ORDER BY ts.name ';
				break;
			default:
				$orderby = 'ORDER BY p.post_date ';
		}

		$order = empty($_REQUEST['order']) ? 'ASC' : $_REQUEST['order'];

		$events = $wpdb->get_results("SELECT p.ID, p.post_title AS event_title, p.post_content AS event_place, DATE_FORMAT(p.post_date, '".$this->date_format."') AS event_date, ts.name AS programme, ts.slug AS event_slug FROM ".$wpdb->prefix."posts p LEFT JOIN (SELECT tr.object_id, t.name, t.slug FROM ".$wpdb->prefix."terms t LEFT JOIN ".$wpdb->prefix."term_taxonomy tt ON t.term_id = tt.term_id LEFT JOIN ".$wpdb->prefix."term_relationships tr ON tt.term_taxonomy_id = tr.term_taxonomy_id WHERE tt.taxonomy='programme') ts ON p.ID = ts.object_id WHERE p.post_type = 'easy_event' ".$programme.$filter.$orderby.$order);

		$current_page = $this->get_pagenum();

		$total_items = count($events);

		$events = array_slice($events, (($current_page - 1) * $per_page), $per_page);

		$this->items = $events;

		$this->set_pagination_args(array(
			'total_items' => $total_items,
			'per_page'    => $per_page,
			'total_pages' => ceil($total_items / $per_page)
		));

	}

	/* Private Functions */

	private function date_check($date) {

		if (preg_match("/^(\d{4})-(\d{2})-(\d{2})$/", $date, $matches)) {
			if (checkdate($matches[2], $matches[3], $matches[1])) {
				return true;
			}
		}

		return false;
	}

	private function date_reorder($date) {

		switch ($this->date_format) {

			case '%m-%d-%Y':
				if (preg_match("/^(\d{2})-(\d{2})-(\d{4})$/", $date, $matches)) {
					return $matches[3].'-'.$matches[1].'-'.$matches[2];
				}
				break;

			case '%d-%m-%Y':
				if (preg_match("/^(\d{2})-(\d{2})-(\d{4})$/", $date, $matches)) {
					return $matches[3].'-'.$matches[2].'-'.$matches[1];
				}
				break;
		}

		return $date;
	}

	private function easy_events_date() {

		switch ($this->date_format) {

		case '%m-%d-%Y':
					$format = 'MM-DD-YYYY';
					break;

		case '%d-%m-%Y':
					$format = 'DD-MM-YYYY';
					break;

		default:
					$format = 'YYYY-MM-DD';
		}

		return $format;
	}

	private function easy_events_terms($id) {

		$terms = get_the_terms($id, 'programme');

		$term_list = '';

				if ($terms != '') {
					foreach ($terms as $term) {
						$term_list .= $term->name . ', ';
					}
				} else {
					$term_list = __('none', 'easy_events');
			}
			$term_list = trim($term_list, ', ');

		return $term_list;
	}

	private function process_bulk_action() {
		global $wpdb;

		$this->show_main_section = true;

		switch($this->current_action()){

			case 'add':
				check_admin_referer('easy_events');

				$event_date = $this->date_reorder($_POST['event_date_v']);
				$event_title = stripslashes($_POST['event_title_v']);
				$event_place = stripslashes($_POST['event_place_v']);
				$programme = (array) $_POST['event_programme_v'];

				$error = $this->validate_event($event_date, $event_title);

				if ($error) {
					wp_die ($error, 'Error', array("back_link" => true));
				} else {

					$post = array(
						'comment_status' => 'closed',
						'ping_status'    => 'closed',
						'post_status'    => 'publish',
						'post_title'     => $event_title,
						'post_content'   => $event_place,
						'post_date'      => $event_date,
						'post_type'      => 'easy_event',
						'tax_input'      => $programme == '' ? '' : array('programme' => $programme)
					);
					$result = wp_insert_post($post);
				}

			break;

			case 'edit':
				$id = (int) $_GET['id'];

				$event = $wpdb->get_row("SELECT ID, post_title AS event_title, post_content AS event_place, DATE_FORMAT(post_date, '".$this->date_format."') AS event_date FROM ".$wpdb->prefix."posts WHERE ID=".$id);

				$programme = $this->easy_events_terms($id);

				?>
					<div id="easy-events" class="wrap">
						<h2><?php _e('Easy Events', 'easy_events'); ?></h2>
						<div id="ajax-response"></div>
						<div class="form-wrap">
							<h3><?php _e('Edit Event', 'easy_events'); ?></h3>
							<form id="editevent" method="post" class="validate edit-event">
								<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
								<input type="hidden" name="action" value="update" />
								<input type="hidden" name="id" value="<?php echo $id; ?>" />
								<?php wp_nonce_field('easy_events_edit'); ?>
								<div class="form-field form-required">
									<label for="event_date"><?php _e('Date', 'easy_events'); ?></label>
									<input type="date" name="event_date_v" id="event_date_v" value="<?php echo $event->event_date; ?>" required="required" />
									<p><?php printf(__('The date the event occured (enter date in %s format).', 'easy_events'), $this->date_description); ?></p>
								</div>
								<div class="form-field form-required">
									<label for="event_title_v"><?php _e('Title', 'easy_events'); ?></label>
									<?php wp_editor($event->event_title, 'event_title_v', array('media_buttons' => false, 'textarea_rows' => 3)); ?>
									<p><?php _e('The name of the event.', 'easy_events'); ?></p>
								</div>
								<div class="form-field">
									<label for="event_place_v"><?php _e('Place', 'easy_events'); ?></label>
									<input type="text" id="event_place_v" name="event_place_v" value="<?php echo esc_html($event->event_place); ?>" />
									<p><?php _e('The location of the event (optional).', 'easy_events'); ?></p>
								</div>
								<div class="form-field">
									<label for="event_programme_v"><?php _e('Programme', 'easy_events'); ?></label>
									<select name="event_programme_v" id="event_programme_v">
										<?php

										$event_terms = get_terms('programme', 'hide_empty=0');

										echo "<option class='theme-option' value=''>".__('none', 'easy_events')."</option>\n";

										if (count($event_terms) > 0) {
											foreach ($event_terms as $event_term) {
												if ($event_term->name == $programme) {
													echo "<option class='theme-option' value='" . $event_term->slug . "' selected='selected'>" . $event_term->name . "</option>\n";
												} else {
													echo "<option class='theme-option' value='" . $event_term->slug . "'>" . $event_term->name . "</option>\n";
												}
											}
										}
										?>
									</select>
									<p><?php _e('The programme to which this event belongs.', 'easy_events'); ?></p>
								</div>
								<p class="submit">
									<input type="submit" name="submit" class="button button-primary" value="<?php _e('Save Changes', 'easy_events'); ?>" />
								</p>
							</form>
						</div>
					</div>

				<?php

				$this->show_main_section = false;

			break;

			case 'update':
				check_admin_referer('easy_events_edit');

				$id = (int) $_POST['id'];
				$event_date = $this->date_reorder($_POST['event_date_v']);
				$event_title = stripslashes($_POST['event_title_v']);
				$event_place = stripslashes($_POST['event_place_v']);
				$programme = (array) $_POST['event_programme_v'];

				$error = $this->validate_event($event_date, $event_title);

				if ($error) {
					wp_die ($error, 'Error', array("back_link" => true));
				} else {

					$post = array(
						'ID' => $id,
						'post_title' => $event_title,
						'post_content' => $event_place,
						'post_date' => $event_date,
						'tax_input' => $programme == '' ? '' : array('programme' => $programme)
					);
					$result = wp_update_post($post);

				}
			break;

			case 'delete':
				$id = (int) $_GET['id'];
				$result = wp_delete_post($id, true);
			break;

			case 'bulk_delete':
				check_admin_referer('bulk-events');
				$ids = (array) $_POST['event'];

				foreach ($ids as $i => $value) {
					$result = wp_delete_post($ids[$i], true);
				}
			break;

			default:
			// nowt
			break;
		}
	}

	private function validate_event($event_date, $event_title) {

		$error = false;

		if (empty($event_date)) {
			$error = '<h3>'. __('Missing Event Date', 'easy_events') .'</h3><p>'.  __('You must enter a date for the event.', 'easy_events') .'</p>';
		} else if (empty($event_title)) {
			$error = '<h3>'. __('Missing Event Title', 'easy_events') .'</h3><p>'. __('You must enter a name for the event.', 'easy_events') .'</p>';
		} else if (!$this->date_check($event_date)) {
			$error = '<h3>'. __('Invalid Event Date', 'easy_events') .'</h3><p>'. $event_date.sprintf(__('Please enter dates in the format %s.', 'easy_events'), $this->date_description) .'</p>';
		}

		return $error;
	}

	/* End Class */
}

?>