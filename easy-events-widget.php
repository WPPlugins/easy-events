<?php

class easy_events_widget extends WP_Widget {

	function __construct() {
		parent::__construct('easy_events_widget', __('Easy Events', 'easy-events'), array('classname' => 'easy-events-widget', 'description' => __('Lists the next few easy events.', 'easy-events')));
	}

	function widget($args, $instance) {
		global $wpdb;

		extract($args, EXTR_SKIP);

		$title = apply_filters('widget_title', empty($instance['title']) ? __('Upcoming Events', 'easy-events') : $instance['title'], $instance, $this->id_base);
		$number_of_events = empty($instance['number_of_events']) ? 1 : $instance['number_of_events'];
		$programme = !isset($instance['programme']) ? '*' : $instance['programme'];
		$full_list = empty($instance['full_list']) ? '' : $instance['full_list'];

		$today = getdate(current_time('timestamp'));

		$day = $today['mday'].'-'.$today['mon'];

		$programme == '*' ? $filter = '' : ($programme == '' ? $filter = " AND ts.slug IS NULL" : $filter = " AND ts.slug='".$programme."'");

		$events = $wpdb->get_results("SELECT p.post_title AS event_title, p.post_content AS event_place, DATE_FORMAT(p.post_date, '%d %b %Y') AS event_date, ts.name AS programme, ts.slug AS slug, ts.description AS description FROM ".$wpdb->prefix."posts p LEFT JOIN (SELECT tr.object_id, t.name, t.slug, tt.description FROM ".$wpdb->prefix."terms t LEFT JOIN ".$wpdb->prefix."term_taxonomy tt ON t.term_id = tt.term_id LEFT JOIN ".$wpdb->prefix."term_relationships tr ON tt.term_taxonomy_id = tr.term_taxonomy_id WHERE tt.taxonomy='programme') ts ON p.ID = ts.object_id WHERE p.post_type = 'easy_event' AND DATE(p.post_date) >= DATE(CURDATE()) ".$filter." ORDER BY DATE_FORMAT(p.post_date, '%Y %m %d') ASC, ts.slug ASC LIMIT ".$number_of_events);

		if (!empty($events)) {

			echo $before_widget;
			echo '<table class="table table-hover table-ndar">';
			echo '<thead><tr><th colspan="2">'.$title.'</th></tr></thead><tbody>';

			foreach ($events as $e => $values) {

				$slug_class = $events[$e]->slug ? $events[$e]->slug.'-text' : '';

				echo '<tr>';
				echo '<td class="easy-events-colour"><span class="fa fa-square '.$slug_class.'" title="'.$events[$e]->description.'"> </span></td>';
				echo '<td><div>'.$events[$e]->event_date.'</div><div>'.$events[$e]->event_title.'</div><div>'.do_shortcode($events[$e]->event_place).'</div></td>';
				echo '</tr>';
			}
			echo '</tbody><tfoot><tr><th colspan="2">';
			if ($full_list) {
				echo '<a href="'.$full_list.'">'.__('Full List', 'easy-events').'</a>';
			}
			echo '</th></tr></tfoot></table>';
			echo $after_widget;
		}
	}

	function update($new_instance, $old_instance) {

		$instance = $old_instance;

		$instance['title'] = trim(strip_tags($new_instance['title']));
		$instance['number_of_events'] = (int) $new_instance['number_of_events'];
		$instance['programme'] = $new_instance['programme'];
		$instance['full_list'] = $new_instance['full_list'];

		return $instance;
	}

	function form($instance) {

		$instance = wp_parse_args((array) $instance, array('title' => __('Upcoming Events', 'easy-events'), 'number_of_events' => 5, 'programme' => '*', 'full_list' => ''));

		$full_list = attribute_escape($instance['full_list']);

		?>
			<p>
				<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'easy-events'); ?></label>
				<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($instance['title']) ?>" />
			</p>
			<p>
				<label for="<?php echo $this->get_field_id('number_of_events'); ?>"><?php _e('Number of Events:', 'easy-events'); ?>
					<select id="<?php echo $this->get_field_id('number_of_events'); ?>" name="<?php echo $this->get_field_name('number_of_events'); ?>">
						<?php
							$option = '';
							for ($p = 3; $p < 16; $p++) {
								if ($p == $instance['number_of_events']) {
									$option .= '<option selected="selected" value="'.$p.'">'.$p.'</option>';
								} else {
									$option .= '<option value="'.$p.'">'.$p.'&nbsp;&nbsp;</option>';
								}
							}
							echo $option;
						?>
					</select>
				</label>
			</p>
			<p>
				<label for="<?php echo $this->get_field_id('programme'); ?>"><?php _e('Programme:', 'easy-events'); ?></label>
				<select class="widefat" id="<?php echo $this->get_field_id('programme'); ?>" name="<?php echo $this->get_field_name('programme'); ?>">
					<?php
						$programmes = get_terms('programme', 'hide_empty=1');

						if ($instance['programme'] == '*') {
							echo "<option class='theme-option' value='*' selected='selected'>".__('Combined Programme', 'easy-events')."</option>\n";
						} else {
							echo "<option class='theme-option' value='*'>".__('Combined Programme', 'easy-events')."</option>\n";
						}
						if (count($programmes) > 0) {
							foreach ($programmes as $programme) {
								if ($programme->slug == $instance['programme']) {
									echo "<option class='theme-option' value='" . $programme->slug . "' selected='selected'>" . $programme->name . "</option>\n";
								} else {
									echo "<option class='theme-option' value='" . $programme->slug . "'>" . $programme->name . "</option>\n";
								}
							}
						}
						if ($instance['programme'] == '') {
							echo "<option class='theme-option' value='' selected='selected'>".__('None', 'easy-events')."</option>\n";
						} else {
							echo "<option class='theme-option' value=''>".__('None', 'easy-events')."</option>\n";
						}
					?>
				</select>
			</p>
			<p>
				<label for="<?php echo $this->get_field_id('full_list'); ?>"> <?php _e('Link to Full List of Events:', 'easy-events') ?></label>
				<input id="<?php echo $this->get_field_id('full_list'); ?>" name="<?php echo $this->get_field_name('full_list'); ?>" type="text" placeholder="<?php _e('Start with / for a local link', 'easy-events'); ?>" value="<?php echo $full_list; ?>"/>
			</p>
		<?php
	}
}

add_action('widgets_init', create_function('', 'return register_widget("easy_events_widget");'));

?>