<?php
/**
 * Grouped Calendars Feed - Admin
 *
 * @package SimpleCalendar/Feeds
 */
namespace SimpleCalendar\Feeds\Admin;

use SimpleCalendar\Feeds\Grouped_Calendars;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Grouped calendars feed admin.
 *
 * @since 3.0.0
 */
class Grouped_Calendars_Admin {

	/**
	 * Grouped Calendars feed object.
	 *
	 * @access private
	 * @var Grouped_Calendars
	 */
	private $feed = null;

	/**
	 * Hook in tabs.
	 *
	 * @since 3.0.0
	 *
	 * @param Grouped_Calendars $feed
	 */
	public function __construct( Grouped_Calendars $feed ) {

		$this->feed = $feed;

		if ( 'calendar' == simcal_is_admin_screen() ) {
			add_filter( 'simcal_settings_meta_tabs_li', array( $this, 'add_settings_meta_tab_li' ), 10, 1 );
			add_action( 'simcal_settings_meta_panels', array( $this, 'add_settings_meta_panel' ), 10, 1 );
		}
		add_action( 'simcal_process_settings_meta', array( $this, 'process_meta' ), 10, 1 );
	}

	/**
	 * Add a tab to the settings meta box.
	 *
	 * @since  3.0.0
	 *
	 * @param  array $tabs
	 *
	 * @return array
	 */
	public function add_settings_meta_tab_li( $tabs ) {
		return array_merge( $tabs, array(
			'grouped-calendars' => array(
				'label'   => $this->feed->name,
				'target'  => 'grouped-calendars-settings-panel',
				'class'   => array(
					'simcal-feed-type',
					'simcal-feed-type-grouped-calendars',
				),
				'icon'    => 'simcal-icon-docs',
			),
		));
	}

	/**
	 * Add a panel to the settings meta box.
	 *
	 * @since 3.0.0
	 *
	 * @param int $post_id
	 */
	public function add_settings_meta_panel( $post_id ) {

		?>
		<div id="grouped-calendars-settings-panel" class="simcal-panel">
			<table>
				<thead>
				<tr><th colspan="2"><?php _e( 'Grouped Calendar settings', 'google-calendar-events' ); ?></th></tr>
				</thead>
				<tbody class="simcal-panel-section">
				<tr class="simcal-panel-field">
					<th><label for="_grouped_calendars_source"><?php _e( 'Get calendars from', 'google-calendar-events' ); ?></label></th>
					<td>
						<?php

						$source = esc_attr( get_post_meta( $post_id, '_grouped_calendars_source', true ) );
						$source = empty( $source ) ? 'ids' : $source;

						?>
						<select name="_grouped_calendars_source"
						        id="_grouped_calendars_source"
						        class="simcal-field simcal-field-select simcal-field-inline simcal-field-show-other"
						        data-show-field-on-choice="true">
							<option value="ids" data-show-field="_grouped_calendars_ids" <?php selected( 'ids', $source, true ); ?>><?php _e( 'Manual selection', 'google-calendar-events' ); ?></option>
							<option value="category" data-show-field="_grouped_calendars_category" <?php selected( 'category', $source, true ); ?>><?php _e( 'Category', 'google-calendar-events' ); ?></option>
						</select>
						<i class="simcal-icon-help simcal-help-tip" data-tip="<?php _e( 'Choose from which calendar feeds you want to get events from. Choose them individually or select all those belonging to calendar feed categories.', 'google-calendar-events' ); ?>"></i>
						<br><br>
						<?php

						$cals = simcal_get_calendars( $post_id );
						$meta = get_post_meta( $post_id, '_grouped_calendars_ids', true );
						$ids  = $meta && is_array( $meta ) ? implode( ',', array_map( 'absint', $meta ) ) : absint( $meta );

						simcal_print_field( array(
							'type'        => 'select',
							'multiselect' => 'multiselect',
							'name'        => '_grouped_calendars_ids',
							'id'          => '_grouped_calendars_ids',
							'value'       => $ids !== 0 ? $ids : '',
							'options'     => $cals,
							'enhanced'    => 'enhanced',
							'style'       => 'ids' == $source ? '' : array( 'display' => 'none' ),
							'attributes'  => array(
								'data-noresults' => __( 'No results found.', 'google-calendar-events' ),
							),
						));

						$meta = get_post_meta( $post_id, '_grouped_calendars_category', true );
						$category = $meta && is_array( $meta ) ? implode( ',', array_map( 'absint', $meta ) ): '';

						$terms = get_terms( 'calendar_category' );

						if ( ! empty( $terms ) ) {

							$categories = array();
							foreach ( $terms as $term ) {
								$categories[ $term->term_id ] = $term->name;
							}

							simcal_print_field( array(
								'type'        => 'select',
								'multiselect' => 'multiselect',
								'name'        => '_grouped_calendars_category',
								'id'          => '_grouped_calendars_category',
								'value'       => $category,
								'options'     => $categories,
								'enhanced'    => 'enhanced',
								'style'       => 'category' == $source ? '' : array( 'display' => 'none' ),
								'attributes'  => array(
									'data-noresults' => __( 'No results found.', 'google-calendar-events' ),
								),
							) );

						} else {

							$style = 'category' == $source ? '' : 'display: none;';
							$style .= ' width: 100%; max-width: 500px';
							echo '<input type="text" disabled="disabled" name="_grouped_calendars_category" id="_grouped_calendars_category" style="' . $style . '" placeholder="' . __( 'There are no calendar categories yet.', 'google-calendar-events' ) . '" />';

						}

						?>
					</td>
				</tr>
				</tbody>
			</table>
		</div>
		<?php

	}

	/**
	 * Process meta fields.
	 *
	 * @since 3.0.0
	 *
	 * @param int $post_id
	 */
	public function process_meta( $post_id ) {

		$source = isset( $_POST['_grouped_calendars_source'] ) ? sanitize_key( $_POST['_grouped_calendars_source'] ) : 'ids';
		update_post_meta( $post_id, '_grouped_calendars_source', $source );

		$ids = isset( $_POST['_grouped_calendars_ids'] ) ? array_map( 'absint', $_POST['_grouped_calendars_ids'] ) : '';
		update_post_meta( $post_id, '_grouped_calendars_ids', $ids );

		$category = isset( $_POST['_grouped_calendars_category'] ) ? array_map( 'absint', $_POST['_grouped_calendars_category'] ) : '';
		update_post_meta( $post_id, '_grouped_calendars_category', $category );

	}

}
