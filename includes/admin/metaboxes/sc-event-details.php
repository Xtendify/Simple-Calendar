<?php
/**
 * SC Event Details Meta Box
 *
 * @package SimpleCalendar/Admin
 */
namespace SimpleCalendar\Admin\Metaboxes;

use SimpleCalendar\Abstracts\Meta_Box;
use SimpleCalendar\Feeds\Sc_Event;

if (!defined('ABSPATH')) {
	exit();
}

/**
 * SC Event details.
 *
 * Meta box for location and start/end datetime on the SC Event edit screen.
 *
 * @since 4.2.0
 */
class Sc_Event_Details implements Meta_Box
{
	/**
	 * Transient key prefix for validation errors.
	 *
	 * @var string
	 */
	const VALIDATION_TRANSIENT = 'simcal_sc_event_validation_';

	/**
	 * Output the meta box markup.
	 *
	 * @since 4.2.0
	 *
	 * @param \WP_Post $post
	 */
	public static function html($post)
	{
		wp_nonce_field('simcal_save_data', 'simcal_meta_nonce');

		$timezone = Sc_Event::esc_timezone_string(simcal_get_wp_timezone());
		$location = sanitize_text_field((string) get_post_meta($post->ID, '_sc_event_location', true));
		$start = Sc_Event::format_datetime(absint(get_post_meta($post->ID, '_sc_event_start', true)), $timezone);
		$end = Sc_Event::format_datetime(absint(get_post_meta($post->ID, '_sc_event_end', true)), $timezone);
		?>
		<p class="description">
			<?php
   printf(
   	/* translators: %s: WordPress timezone */
   	esc_html__('Date and time values use the site timezone (%s).', 'google-calendar-events'),
   	esc_html($timezone),
   ); ?>
		</p>
		<div id="simcal-sc-event-details-errors" class="notice notice-error inline" style="display:none;" role="alert">
			<p></p>
		</div>
		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row">
						<label for="_sc_event_location"><?php esc_html_e('Location', 'google-calendar-events'); ?></label>
					</th>
					<td>
						<input
							type="text"
							class="large-text"
							name="_sc_event_location"
							id="_sc_event_location"
							value="<?php echo esc_attr($location); ?>"
						/>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="_sc_event_start">
							<?php esc_html_e('Start Date/Time', 'google-calendar-events'); ?>
							<span class="required">*</span>
						</label>
					</th>
					<td>
						<input
							type="datetime-local"
							name="_sc_event_start"
							id="_sc_event_start"
							value="<?php echo esc_attr($start); ?>"
							required
							aria-required="true"
						/>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="_sc_event_end">
							<?php esc_html_e('End Date/Time', 'google-calendar-events'); ?>
							<span class="required">*</span>
						</label>
					</th>
					<td>
						<input
							type="datetime-local"
							name="_sc_event_end"
							id="_sc_event_end"
							value="<?php echo esc_attr($end); ?>"
							required
							aria-required="true"
						/>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Validate and save the meta box fields.
	 *
	 * @since 4.2.0
	 *
	 * @param int      $post_id
	 * @param \WP_Post $post
	 */
	public static function save($post_id, $post)
	{
		$timezone = Sc_Event::esc_timezone_string(simcal_get_wp_timezone());

		$location = isset($_POST['_sc_event_location'])
			? sanitize_text_field(wp_unslash($_POST['_sc_event_location']))
			: '';
		$start_raw = isset($_POST['_sc_event_start']) ? sanitize_text_field(wp_unslash($_POST['_sc_event_start'])) : '';
		$end_raw = isset($_POST['_sc_event_end']) ? sanitize_text_field(wp_unslash($_POST['_sc_event_end'])) : '';

		$start = Sc_Event::parse_datetime($start_raw, $timezone);
		$end = Sc_Event::parse_datetime($end_raw, $timezone);

		$errors = [];

		if ($start <= 0) {
			$errors[] = __('Start date/time is required.', 'google-calendar-events');
		}

		if ($end <= 0) {
			$errors[] = __('End date/time is required.', 'google-calendar-events');
		}

		if ($start > 0 && $end > 0 && $end <= $start) {
			$errors[] = __('End date/time must be greater than the start date/time.', 'google-calendar-events');
		}

		if (!empty($errors)) {
			set_transient(self::VALIDATION_TRANSIENT . get_current_user_id(), $errors, 45);
			return;
		}

		delete_transient(self::VALIDATION_TRANSIENT . get_current_user_id());

		update_post_meta($post_id, '_sc_event_location', $location);
		update_post_meta($post_id, '_sc_event_start', $start);
		update_post_meta($post_id, '_sc_event_end', $end);
	}

	/**
	 * Show validation errors after a failed save.
	 *
	 * @since 4.2.0
	 */
	public static function admin_notices()
	{
		$screen = function_exists('get_current_screen') ? get_current_screen() : null;

		if (!$screen || !in_array($screen->id, ['sc-event', 'edit-sc-event'], true)) {
			return;
		}

		$key = self::VALIDATION_TRANSIENT . get_current_user_id();
		$errors = get_transient($key);

		if (empty($errors) || !is_array($errors)) {
			return;
		}

		delete_transient($key);

		echo '<div class="notice notice-error is-dismissible"><p><strong>';
		esc_html_e('Event details could not be saved:', 'google-calendar-events');
		echo '</strong></p><ul style="list-style:disc;margin-left:1.5em;">';

		foreach ($errors as $error) {
			echo '<li>' . esc_html($error) . '</li>';
		}

		echo '</ul></div>';
	}
}
