<?php
/**
 * Newsletter Sign Up Meta Box
 *
 * @package SimpleCalendar/Admin
 */
namespace SimpleCalendar\Admin\Metaboxes;

use SimpleCalendar\Abstracts\Meta_Box;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sign up to Simple Calendar newsletter.
 *
 * @since 3.0.0
 */
class Newsletter implements Meta_Box {

	/**
	 * Output HTML.
	 *
	 * @since 3.0.0
	 *
	 * @param \WP_Post $post
	 */
	public static function html( $post ) {

		global $current_user;
		get_currentuserinfo();

		$name = $current_user->user_firstname ? $current_user->user_firstname : '';

		?>
		<div id="simcal-drip">
			<div class="signup">
				<p>
					<?php _e( 'Get notified by email of important updates and major releases.', 'google-calendar-events' ); ?>
				</p>
				<p>
					<label for="simcal-drip-field-email">
						<input type="email"
						       placeholder="<?php _e( 'Your Email', 'google-calendar-events' ); ?>"
						       id="simcal-drip-field-email"
						       name="fields[email]"
						       value="<?php echo $current_user->user_email; ?>" />
					</label>
				</p>
				<p>
					<label for="simcal-drip-field-first_name">
						<input type="text"
						       placeholder="<?php _e( 'First Name', 'google-calendar-events' ); ?>"
						       id="simcal-drip-field-first_name"
						       name="fields[first_name]"
						       value="<?php echo $name; ?>" />
					</label>
				</p>
				<a href="#"
				   id="simcal-drip-signup"
				   class="right button button-primary"><?php _e( 'Subscribe Now', 'google-calendar-events' ); ?></a>
			</div>
			<div class="thank-you" style="display: none;">
				<?php _e( 'Thank you!', 'google-calendar-events' ); ?>
			</div>
			<div class="clear">
			</div>
		</div>
		<?php

	}

	/**
	 * Save settings.
	 *
	 * @since 3.0.0
	 *
	 * @param int      $post_id
	 * @param \WP_Post $post
	 */
	public static function save( $post_id, $post ) {
		// This meta box has no persistent settings.
	}

}
