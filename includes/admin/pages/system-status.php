<?php
/**
 * System Status Page
 *
 * @package SimpleCalendar/Admin
 */
namespace SimpleCalendar\Admin\Pages;

use SimpleCalendar\Abstracts\Admin_Page;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * System status.
 *
 * Generates reports on current installation, with information for debugging or support purposes.
 *
 * @since 3.0.0
 */
class System_Status extends Admin_Page {

	/**
	 * Constructor.
	 *
	 * @since 3.0.0
	 */
	public function __construct() {

		$this->id           = $tab = 'system-status';
		$this->option_group = $page = 'tools';
		$this->label        = __( 'System Report', 'google-calendar-events' );
		$this->description  = '';
		$this->sections     = $this->add_sections();
		$this->fields       = $this->add_fields();

		// Disable the submit button for this page.
		add_filter( 'simcal_admin_page_' . $page . '_' . $tab . '_submit', function() { return false; } );

		// Add html.
		add_action( 'simcal_admin_page_' . $page . '_' . $tab . '_end', array( $this, 'html' ) );
	}

	/**
	 * Output page markup.
	 *
	 * @since 3.0.0
	 */
	public function html() {

		?>
		<div id="simcal-system-status-report">
			<p><?php _e( 'Please copy and paste this information when contacting support:', 'google-calendar-events' ); ?> </p>
			<textarea readonly="readonly" onclick="this.select();"></textarea>
			<p><?php _e( 'You can also download your information as a text file to attach, or simply view it below.', 'google-calendar-events' ); ?></p>
			<p><a href="#" id="simcal-system-status-report-download" class="button button-primary"><?php _e( 'Download System Report', 'google-calendar-events' ); ?></a></p>
		</div>
		<hr>
		<?php

		global $wpdb;
		$wp_version = get_bloginfo( 'version' );

		$sections = array();
		$panels   = array(
			'wordpress' => array(
				'label'  => __( 'WordPress Installation', 'google-calendar-events' ),
				'export' => 'WordPress Installation',
			),
			'theme'     => array(
				'label'  => __( 'Active Theme', 'google-calendar-events' ),
				'export' => 'Active Theme',
			),
			'plugins'   => array(
				'label'  => __( 'Active Plugins', 'google-calendar-events' ),
				'export' => 'Active Plugins',
			),
			'server'    => array(
				'label'  => __( 'Server Environment', 'google-calendar-events' ),
				'export' => 'Server Environment',
			),
			'client'    => array(
				'label'  => __( 'Client Information', 'google-calendar-events' ),
				'export' => 'Client Information',
			)
		);

		/**
		 * Plugin Information
		 * ==================
		 */

		// @todo add report information section for current plugin

		/**
		 * WordPress Installation
		 * ======================
		 */

		$debug_mode = $script_debug = __( 'No', 'google-calendar-events' );
		if ( defined( 'WP_DEBUG' ) ) {
			$debug_mode = true === WP_DEBUG ? __( 'Yes', 'google-calendar-events' ) : $debug_mode;
		}
		if ( defined( 'SCRIPT_DEBUG' ) ) {
			$script_debug = true === SCRIPT_DEBUG ? __( 'Yes', 'google-calendar-events' ) : $script_debug;
		}

		$memory = $this->let_to_num( WP_MEMORY_LIMIT );
		$memory_export = size_format( $memory );
		if ( $memory < 67108864 ) {
			$memory = '<mark class="error">' . sprintf( __( '%1$s - It is recomendend to set memory to at least 64MB. See: <a href="%2$s" target="_blank">Increasing memory allocated to PHP</a>', 'google-calendar-events' ), $memory_export, 'http://codex.wordpress.org/Editing_wp-config.php#Increasing_memory_allocated_to_PHP' ) . '</mark>';
		} else {
			$memory = '<mark class="ok">' . $memory_export . '</mark>';
		}

		$permalinks = get_option( 'permalink_structure' );
		$permalinks = empty( $permalinks ) ? '/?' : $permalinks;

		$is_multisite = is_multisite();

		$sections['wordpress'] = array(
			'name'          => array(
				'label'  => __( 'Site Name', 'google-calendar-events' ),
				'label_export' => 'Site Name',
				'result' => get_bloginfo( 'name' ),
			),
			'home_url'      => array(
				'label'  => __( 'Home URL', 'google-calendar-events' ),
				'label_export' => 'Home URL',
				'result' => home_url(),
			),
			'site_url'      => array(
				'label'  => __( 'Site URL', 'google-calendar-events' ),
				'label_export' => 'Site URL',
				'result' => site_url(),
			),
			'version'       => array(
				'label'  => __( 'Version', 'google-calendar-events' ),
				'label_export' => 'Version',
				'result' => $wp_version,
			),
			'locale'        => array(
				'label'  => __( 'Locale', 'google-calendar-events' ),
				'label_export' => 'Locale',
				'result' => get_locale(),
			),
			'wp_timezone'   => array(
				'label'  => __( 'Timezone', 'google-calendar-events' ),
				'label_export' => 'Timezone',
				'result' => simcal_get_wp_timezone(),
			),
			'multisite'     => array(
				'label'  => __( 'Multisite', 'google-calendar-events' ),
				'label_export' => 'Multisite',
				'result' => $is_multisite ? __( 'Yes', 'google-calendar-events' ) : __( 'No', 'google-calendar-events' ),
				'result_export' => $is_multisite ? 'Yes' : 'No'
			),
			'permalink'     => array(
				'label'  => __( 'Permalinks', 'google-calendar-events' ),
				'label_export' => 'Permalinks',
				'result' => '<code>' . $permalinks . '</code>',
				'result_export' => $permalinks,
			),
			'memory_limit'  => array(
				'label'  => 'WP Memory Limit',
				'result' => $memory,
				'result_export' => $memory_export,
			),
			'debug_mode'    => array(
				'label'  => 'WP Debug Mode',
				'result' => $debug_mode,
			),
			'script_debug' => array(
				'label'  => 'Script Debug',
				'result' => $script_debug,
			),
		);

		/**
		 * Active Theme
		 * ============
		 */

		include_once ABSPATH . 'wp-admin/includes/theme-install.php';

		if ( version_compare( $wp_version, '3.4', '<' ) ) {
			$active_theme  = get_theme_data( get_stylesheet_directory() . '/style.css' );
			$theme_name    = '<a href="' . $active_theme['URI'] . '" target="_blank">' . $active_theme['Name'] . '</a>';
			$theme_version = $active_theme['Version'];
			$theme_author  = '<a href="' . $active_theme['AuthorURI'] . '" target="_blank">' . $active_theme['Author'] . '</a>';
			$theme_export  = $active_theme['Name'] . ' - ' . $theme_version;
		} else {
			$active_theme  = wp_get_theme();
			$theme_name    = '<a href="' . $active_theme->ThemeURI . '" target="_blank">' . $active_theme->Name . '</a>';
			$theme_version = $active_theme->Version;
			$theme_author  = $active_theme->Author;
			$theme_export  = $active_theme->Name . ' - ' . $theme_version;
		}

		$theme_update_version = $theme_version;

		$api = themes_api( 'theme_information', array(
			'slug'   => get_template(),
			'fields' => array( 'sections' => false, 'tags' => false ),
		) );
		if ( $api && ! is_wp_error( $api ) ) {
			$theme_update_version = $api->version;
		}

		if ( version_compare( $theme_version, $theme_update_version, '<' ) ) {
			$theme_version = '<mark class="error">' . $theme_version . ' (' . sprintf( __( '%s is available', 'google-calendar-events' ), esc_html( $theme_update_version ) ) . ')</mark>';
		} else {
			$theme_version = '<mark class="ok">' . $theme_version . '</mark>';
		}

		$theme  = '<dl>';
		$theme .= '<dt>' . __( 'Name', 'google-calendar-events' ) . '</dt>';
		$theme .= '<dd>' . $theme_name . '</dd>';
		$theme .= '<dt>' . __( 'Author', 'google-calendar-events' ) . '</dt>';
		$theme .= '<dd>' . $theme_author . '</dd>';
		$theme .= '<dt>' . __( 'Version', 'google-calendar-events' ) . '</dt>';
		$theme .= '<dd>' . $theme_version . '</dd>';
		$theme .= '</dl>';

		$is_child_theme = is_child_theme();
		$parent_theme = $parent_theme_export = '-';

		if ( $is_child_theme ) {
			if ( version_compare( $wp_version, '3.4', '<' ) ) {

				$parent_theme = $parent_theme_export = $active_theme['Template'];

			} else {

				$parent = wp_get_theme( $active_theme->Template );
				$parent_theme  = '<dl>';
				$parent_theme .= '<dt>' . __( 'Name', 'google-calendar-events' ) .    '</dt>';
				$parent_theme .= '<dd>' . $parent->Name .          '</dd>';
				$parent_theme .= '<dt>' . __( 'Author', 'google-calendar-events' ) .  '</dt>';
				$parent_theme .= '<dd>' . $parent->Author .        '</dd>';
				$parent_theme .= '<dt>' . __( 'Version', 'google-calendar-events' ) . '</dt>';
				$parent_theme .= '<dd>' . $parent->Version .       '</dd>';
				$parent_theme .= '</dl>';

				$parent_theme_export = strip_tags( $parent->Name ) . ' - ' .  $parent->Version;
			}
		}

		$sections['theme'] = array(
			'theme'    => array(
				'label'  => __( 'Theme Information', 'google-calendar-events' ),
				'label_export' => 'Theme',
				'result' => $theme,
				'result_export' => $theme_export,
			),
			'theme_child'   => array(
				'label'  => __( 'Child Theme', 'google-calendar-events' ),
				'label_export' => 'Child Theme',
				'result' => $is_child_theme ? __( 'Yes', 'google-calendar-events' ) : __( 'No', 'google-calendar-events' ),
				'result_export' => $is_child_theme ? 'Yes' : 'No',
			),
			'theme_parent'   => array(
				'label'  => __( 'Parent Theme', 'google-calendar-events' ),
				'label_export' => 'Parent Theme',
				'result' => $parent_theme,
				'result_export' => $parent_theme_export,
			),
		);

		/**
		 * Active Plugins
		 * ==============
		 */

		include_once ABSPATH . 'wp-admin/includes/plugin-install.php';

		$active_plugins = (array) get_option( 'active_plugins', array() );
		if ( is_multisite() ) {
			$active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
		}

		foreach ( $active_plugins as $plugin ) {

			$plugin_data = @get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin );

			if ( ! empty( $plugin_data['Name'] ) ) {

				$plugin_name    = $plugin_data['Title'];
				$plugin_author  = $plugin_data['Author'];
				$plugin_version = $plugin_update_version = $plugin_data['Version'];

				// Afraid that querying many plugins may risk a timeout.
				if ( count( $active_plugins ) <= 10 ) {
					$api = plugins_api( 'plugin_information', array(
						'slug'   => $plugin_data['Name'],
						'fields' => array(
							'version' => true,
						),
					) );
					if ( $api && ! is_wp_error( $api ) ) {
						if ( ! empty( $api->version ) ) {
							$plugin_update_version = $api->version;
							if ( version_compare( $plugin_version, $plugin_update_version, '<' ) ) {
								$plugin_version = '<mark class="error">' . $plugin_version . ' (' . sprintf( __( '%s is available', 'google-calendar-events' ), esc_html( $plugin_update_version ) ) . ')</mark>';
							} else {
								$plugin_version = '<mark class="ok">' . $plugin_version . '</mark>';
							}
						}
					}
				}

				$plugin  = '<dl>';
				$plugin .= '<dt>' . __( 'Author', 'google-calendar-events' ) .  '</dt>';
				$plugin .= '<dd>' . $plugin_author .         '</dd>';
				$plugin .= '<dt>' . __( 'Version', 'google-calendar-events' ) . '</dt>';
				$plugin .= '<dd>' . $plugin_version .        '</dd>';
				$plugin .= '</dl>';

				$sections['plugins'][ sanitize_key( strip_tags( $plugin_name ) ) ] = array(
					'label'  => $plugin_name,
					'label_export' => strip_tags( $plugin_data['Title'] ),
					'result' => $plugin,
					'result_export' => $plugin_data['Version'],
				);
			}
		}

		if ( isset( $sections['plugins'] ) ) {
			rsort( $sections['plugins'] );
		}

		/**
		 * Server Environment
		 * ==================
		 */

		if ( version_compare( PHP_VERSION, '7.0', '<' ) ) {
			$php = '<mark>' . PHP_VERSION . ' - ' .
			       __( 'WordPress.org recommends upgrading to PHP 7 or higher for better security.', 'google-calendar-events' ) .
			       ' <a href="https://wordpress.org/about/requirements/" target="_blank">' . __( 'Read more.', 'google-calendar-events' ) . '</a>' .
		           '</mark>';
		} else {
			$php = '<mark class="ok">' . PHP_VERSION . '</mark>';
		}

		if ( $wpdb->use_mysqli ) {
			$mysql = @mysqli_get_server_info( $wpdb->dbh );
		} else {
			$mysql = '<mark class="error">' . __( 'Cannot connect to MySQL database.', 'google-calendar-events' ) . '</mark>';
		}

		$host = $_SERVER['SERVER_SOFTWARE'];
		if ( defined( 'WPE_APIKEY' ) ) {
			$host .= ' (WP Engine)';
		} elseif ( defined( 'PAGELYBIN' ) ) {
			$host .= ' (Pagely)';
		}

		$default_timezone = $server_timezone_export = date_default_timezone_get();
		if ( 'UTC' !== $default_timezone ) {
			$server_timezone = '<mark class="error">' . sprintf( __( 'Server default timezone is %s - it should be UTC', 'google-calendar-events' ), $default_timezone ) . '</mark>';
		} else {
			$server_timezone = '<mark class="ok">UTC</mark>';
		}

		// WP Remote POST test.
		$response = wp_safe_remote_post( 'https://www.paypal.com/cgi-bin/webscr', array(
			'timeout'    => 60,
			'body'       => array(
				'cmd'    => '_notify-validate',
			),
		) );
		if ( ! is_wp_error( $response ) && $response['response']['code'] >= 200 && $response['response']['code'] < 300 ) {
			$wp_post_export = 'Yes';
			$wp_post = '<mark class="ok">' . __( 'Yes', 'google-calendar-events' ) . '</mark>';
		} else {
			$wp_post_export = 'No';
			$wp_post = '<mark class="error">' . __( 'No', 'google-calendar-events' );
			if ( is_wp_error( $response ) ) {
				$error = ' (' . $response->get_error_message() . ')';
				$wp_post .= $error;
				$wp_post_export .= $error;
			} else {
				$error = ' (' . $response['response']['code'] . ')';
				$wp_post .= $error;
				$wp_post_export .= $error;
			}
			$wp_post .= '</mark>';
		}

		// WP Remote GET test.
		$response = wp_safe_remote_get( get_home_url( '/?p=1' ) );
		if ( ! is_wp_error( $response ) && $response['response']['code'] >= 200 && $response['response']['code'] < 300 ) {
			$wp_get_export = 'Yes';
			$wp_get = '<mark class="ok">' . __( 'Yes', 'google-calendar-events' ) . '</mark>';
		} else {
			$wp_get_export = 'No';
			$wp_get = '<mark class="error">' . __( 'No', 'google-calendar-events' );
			if ( is_wp_error( $response ) ) {
				$error = ' (' . $response->get_error_message() . ')';
				$wp_get .= $error;
				$wp_get_export .= $error;
			} else {
				$error = ' (' . $response['response']['code'] . ')';
				$wp_get .= $error;
				$wp_get_export .= $error;
			}
			$wp_get .= '</mark>';
		}

		$php_memory_limit        = ini_get( 'memory_limit' );
		$php_max_upload_filesize = ini_get( 'upload_max_filesize' );
		$php_post_max_size       = ini_get( 'post_max_size' );
		$php_max_execution_time  = ini_get( 'max_execution_time' );
		$php_max_input_vars      = ini_get( 'max_input_vars' );

		$curl_info = '';

		if ( function_exists( 'curl_version' ) ) {
			$curl_info = curl_version();
		}

		$sections['server'] = array(
			'host'                => array(
				'label'  => __( 'Web Server', 'google-calendar-events' ),
				'label_export' => 'Web Server',
				'result' => $host,
			),
			'php_version'         => array(
				'label'  => __( 'PHP Version', 'google-calendar-events' ),
				'label_export' => 'PHP Version',
				'result' => $php,
				'result_export' => PHP_VERSION,
			),
			'mysql_version'       => array(
				'label'  => __( 'MySQL Version', 'google-calendar-events' ),
				'label_export' => 'MySQL Version',
				'result' => version_compare( $mysql, '5.5', '>' ) ? '<mark class="ok">' . $mysql . '</mark>' : $mysql,
				'result_export' => $mysql,
			),
			'server_timezone'     => array(
				'label'  => __( 'Server Timezone', 'google-calendar-events' ),
				'label_export' => 'Server Timezone',
				'result' => $server_timezone,
				'result_export' => $server_timezone_export,
			),
			'display_errors'      => array(
				'label'  => 'Display Errors',
				'result' => ( ini_get( 'display_errors' ) ) ? __( 'Yes', 'google-calendar-events' ) . ' (' . ini_get( 'display_errors' ) . ')' : '-',
				'result_export' => ( ini_get( 'display_errors' ) ) ? 'Yes' : 'No',
			),
			'php_memory_limit'    => array(
				'label'  => 'Memory Limit',
				'result' => $php_memory_limit ? $php_memory_limit : '-',
			),
			'upload_max_filesize' => array(
				'label'  => 'Upload Max Filesize',
				'result' => $php_max_upload_filesize ? $php_max_upload_filesize : '-',
			),
			'post_max_size'       => array(
				'label'  => 'Post Max Size',
				'result' => $php_post_max_size ? $php_post_max_size : '-',
			),
			'max_execution_time'  => array(
				'label'  => 'Max Execution Time',
				'result' => $php_max_execution_time ? $php_max_execution_time : '-',
			),
			'max_input_vars'      => array(
				'label'  => 'Max Input Vars',
				'result' => $php_max_input_vars ? $php_max_input_vars : '-',
			),
			'fsockopen'           => array(
				'label'  => 'fsockopen',
				'result' => function_exists( 'fsockopen' ) ? __( 'Yes', 'google-calendar-events' ) : __( 'No', 'google-calendar-events' ),
				'result_export' => function_exists( 'fsockopen' ) ? 'Yes' : 'No',
			),
			'curl_init'           => array(
				'label'         => 'cURL',
				'result'        => ! empty( $curl_info ) ? $curl_info['version'] . ', ' . $curl_info['ssl_version'] : __( 'No version found.', 'google-calendar-events' ),
				'result_export' => ! empty( $curl_info ) ? $curl_info['version'] . ', ' . $curl_info['ssl_version'] : 'No version found.',
			),
			'soap'                => array(
				'label'  => 'SOAP',
				'result' => class_exists( 'SoapClient' ) ? __( 'Yes', 'google-calendar-events' ) : __( 'No', 'google-calendar-events' ),
				'result_export' => class_exists( 'SoapClient' ) ? 'Yes' : 'No',
			),
			'suhosin'             => array(
				'label'  => 'SUHOSIN',
				'result' => extension_loaded( 'suhosin' ) ? __( 'Yes', 'google-calendar-events' ) : __( 'No', 'google-calendar-events' ),
				'result_export' => extension_loaded( 'suhosin' ) ? 'Yes' : 'No',
			),
			'wp_remote_post'      => array(
				'label'  => 'WP Remote POST',
				'result' => $wp_post,
				'result_export' => $wp_post_export,
			),
			'wp_remote_get'       => array(
				'label'  => 'WP Remote GET',
				'result' => $wp_get,
				'result_export' => $wp_get_export,
			),
		);

		/**
		 * Client Information
		 * ==================
		 */

		$user_client = new \SimpleCalendar\Browser();

		$browser  = '<dl>';
		$browser .= '<dt>' . __( 'Name:', 'google-calendar-events' ) .         '</dt>';
		$browser .= '<dd>' . $user_client->getBrowser() .   '</dd>';
		$browser .= '<dt>' . __( 'Version:', 'google-calendar-events' ) .      '</dt>';
		$browser .= '<dd>' . $user_client->getVersion() .   '</dd>';
		$browser .= '<dt>' . __( 'User Agent:', 'google-calendar-events' ) .   '</dt>';
		$browser .= '<dd>' . $user_client->getUserAgent() . '</dd>';
		$browser .= '<dt>' . __( 'Platform:', 'google-calendar-events' ) .     '</dt>';
		$browser .= '<dd>' . $user_client->getPlatform() .  '</dd>';
		$browser .= '</dl>';

		$browser_export = $user_client->getBrowser() . ' ' . $user_client->getVersion() . ' (' . $user_client->getPlatform() . ')';

		$sections['client'] = array(
			'user_ip' => array(
				'label'  => __( 'IP Address', 'google-calendar-events' ),
				'label_export' => 'IP Address',
				'result' => $_SERVER['SERVER_ADDR'],
			),
			'browser' => array(
				'label'  => __( 'Browser', 'google-calendar-events' ),
				'result' => $browser,
				'result_export' => $browser_export,
			)
		);

		/**
		 * Final Output
		 * ============
		 */

		$panels   = apply_filters( 'simcal_system_status_report_panels', $panels );
		$sections = apply_filters( 'simcal_system_status_report_sections', $sections );

		foreach ( $panels as $panel => $v ) :

			if ( isset( $sections[ $panel ] ) ) :

				?>
				<table class="widefat simcal-system-status-report-panel">
					<thead class="<?php echo $panel; ?>">
						<tr>
							<th colspan="3" data-export="<?php echo $v['export']; ?>"><?php echo $v['label']; ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $sections[ $panel ] as $row => $cell ) : ?>
							<tr>
								<?php
								$label_export  = isset( $cell['label_export']  ) ? $cell['label_export']  : $cell['label'];
								$result_export = isset( $cell['result_export'] ) ? $cell['result_export'] : $cell['result'];
								?>
								<td class="tooltip"><?php echo isset( $cell['tooltip'] ) ? ' <i class="simcal-icon-help simcal-help-tip" data-tip="' . $cell['tooltip'] . '"></i> ' : ''; ?></td>
								<td class="label" data-export="<?php echo trim( $label_export ); ?>"><?php echo $cell['label']; ?></td>
								<td class="result" data-export="<?php echo trim( $result_export ); ?>"><?php echo $cell['result']; ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<?php

			endif;

		endforeach;

		$this->inline_scripts();

	}

	/**
	 * Print inline scripts.
	 *
	 * @since  3.0.0
	 * @access private
	 *
	 * @return void
	 */
	private function inline_scripts() {

		?>
		<script type="text/javascript">

			var report = '';

			jQuery( '.simcal-system-status-report-panel thead, .simcal-system-status-report-panel tbody' ).each( function() {

				if ( jQuery( this ).is( 'thead' ) ) {

					var label = jQuery( this ).find( 'th' ).data( 'export' );
					report = report + '\n### ' + jQuery.trim( label ) + ' ###\n\n';

				} else {

					jQuery( 'tr', jQuery( this ) ).each( function() {

						var label       = jQuery( this ).find( 'td:eq(1)' ).data( 'export' );
						var the_name    = jQuery.trim( label ).replace( /(<([^>]+)>)/ig, '' ); // Remove HTML
						var image       = jQuery( this ).find( 'td:eq(2)' ).find( 'img' ); // Get WP 4.2 emojis
						var prefix      = ( undefined === image.attr( 'alt' ) ) ? '' : image.attr( 'alt' ) + ' '; // Remove WP 4.2 emojis
						var the_value   = jQuery.trim( prefix + jQuery( this ).find( 'td:eq(2)' ).data( 'export' ) );
						var value_array = the_value.split( ', ' );
						if ( value_array.length > 1 ) {
							var temp_line ='';
							jQuery.each( value_array, function( key, line ) {
								temp_line = temp_line + line + '\n';
							});
							the_value = temp_line;
						}

						report = report + '' + the_name.trim() + ': ' + the_value.trim() + '\n';
					});

				}

			});

			try {
				jQuery( '#simcal-system-status-report textarea' ).val( report ).focus().select();
			} catch( e ){
				console.log( e );
			}

			function downloadReport( text, name, type ) {
				var a = document.getElementById( 'simcal-system-status-report-download' );
				var file = new Blob( [text], { type: type } );
				a.href = URL.createObjectURL(file);
				a.download = name;
			}

			jQuery( '#simcal-system-status-report-download' ).on( 'click', function() {
				var file = new Blob( [ report ], { type: 'text/plain' } );
				jQuery( this ).attr( 'href', URL.createObjectURL( file ) );
				jQuery( this ).attr( 'download', '<?php echo sanitize_title( str_replace( array( 'http://', 'https://' ), '', get_bloginfo( 'url' ) ) . '-system-report-' . date( 'Y-m-d', time() ) ); ?>' );
			} );

		</script>
		<?php

	}

	/**
	 * PHP sizes conversions.
	 *
	 * This function transforms the php.ini notation for numbers (like '2M') to an integer.
	 *
	 * @since  3.0.0
	 * @access private
	 *
	 * @param  string $size
	 *
	 * @return int|double|string
	 */
	private function let_to_num( $size ) {

		$l   = substr( $size, -1 );
		$ret = substr( $size, 0, -1 );

		// Note: do not insert break or default in this switch loop.
		switch ( strtoupper( $l ) ) {
			case 'P':
				$ret *= 1024;
				// no break
			case 'T':
				$ret *= 1024;
				// no break
			case 'G':
				$ret *= 1024;
				// no break
			case 'M':
				$ret *= 1024;
				// no break
			case 'K':
				$ret *= 1024;;
				// no break
		}

		return $ret;
	}

	/**
	 * Add sections.
	 *
	 * @since  3.0.0
	 *
	 * @return array
	 */
	public function add_sections() {
		return array();
	}

	/**
	 * Add fields.
	 *
	 * @since  3.0.0
	 *
	 * @return array
	 */
	public function add_fields() {
		return array();
	}

}
