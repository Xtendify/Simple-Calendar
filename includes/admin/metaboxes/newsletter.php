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

		$name = join( ' ', array( $current_user->user_firstname, $current_user->user_lastname ) );
		if ( ! $name ) {
			$name = $current_user->display_name;
		}

		?>
		<div id="simcal-getdrip">
			<div class="signup">
				<p>
					<label for="simcal-getdrip-field-first_name">
						<?php _e( 'Name', 'google-calendar-events' ); ?>
						<input type="text"
						       id="simcal-getdrip-field-first_name"
						       name="fields[first_name]"
						       value="<?php echo $name; ?>" />
					</label>
				</p>
				<p>
					<label for="simcal-getdrip-field-email">
						<?php _e( 'Email', 'google-calendar-events' ); ?>
						<input type="email"
						       id="simcal-getdrip-field-email"
						       name="fields[email]"
						       value="<?php echo $current_user->user_email; ?>" />
					</label>
				</p>
				<a href="#"
				   id="simcal-getdrip-signup"
				   class="right button button-primary"><?php _e( 'Sign up', 'google-calendar-events' ); ?></a>
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
