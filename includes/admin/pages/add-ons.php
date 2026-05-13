<?php
/**
 * Add-ons Page
 *
 * @package SimpleCalendar/Admin
 */
namespace SimpleCalendar\Admin\Pages;

use SimpleCalendar\Abstracts\Admin_Page;

if (!defined('ABSPATH')) {
	exit();
}

/**
 * Add-ons page.
 *
 * @since 3.0.0
 */
class Add_Ons extends Admin_Page
{
	/**
	 * Constructor.
	 *
	 * @since 3.0.0
	 */
	public function __construct()
	{
		$this->id = $tab = 'add-ons';
		$this->option_group = $page = 'add-ons';
		$this->label = __('Add-ons', 'google-calendar-events');
		$this->description = '';
		$this->sections = $this->add_sections();
		$this->fields = $this->add_fields();

		// Disable the submit button for this page.
		add_filter('simcal_admin_page_' . $page . '_' . $tab . '_submit', function () {
			return false;
		});

		// Add html.
		add_action('simcal_admin_page_' . $page . '_' . $tab . '_end', [__CLASS__, 'html']);
	}

	/**
	 * Output page markup.
	 *
	 * @since 3.0.0
	 */
	public static function html()
	{
		$assets_base = SIMPLE_CALENDAR_ASSETS . 'images/admin/';

		$connect_sidebar_scope = function_exists('simcal_prepare_connect_sidebar_scope')
			? simcal_prepare_connect_sidebar_scope()
			: [];
		if (is_array($connect_sidebar_scope)) {
			extract($connect_sidebar_scope, EXTR_OVERWRITE);
		}
		$current_step = 'add_ons';

		$installed_addons = apply_filters('simcal_installed_addons', []);
		$installed_addons = is_array($installed_addons) ? $installed_addons : [];

		/**
		 * Map a catalog display title to the `simcal_{id}` key used in settings.
		 *
		 * Add-ons register arbitrary display strings (not always identical to catalog titles),
		 * so we accept multiple candidate labels and optional explicit `simcal_*` keys.
		 *
		 * @param string|string[] $expected_titles One or more strings to match against registered add-on names (case-insensitive).
		 * @param string[]        $explicit_keys   Optional `simcal_*` keys that must exist in `$installed_addons` to count as a match.
		 * @return string Empty string when not found.
		 */
		$get_license_key = function ($expected_titles, array $explicit_keys = []) use ($installed_addons) {
			$titles = is_array($expected_titles) ? $expected_titles : [$expected_titles];
			$candidates = [];
			foreach ($titles as $t) {
				if (is_string($t) && $t !== '') {
					$candidates[] = $t;
				}
			}
			foreach ($explicit_keys as $ek) {
				$ek = is_string($ek) ? $ek : '';
				if ($ek !== '' && strpos($ek, 'simcal_') === 0 && array_key_exists($ek, $installed_addons)) {
					return $ek;
				}
			}
			foreach ($installed_addons as $k => $v) {
				if (!is_string($k) || $k === '' || strpos($k, 'simcal_') !== 0) {
					continue;
				}
				if (!is_string($v) || $v === '') {
					continue;
				}
				foreach ($candidates as $expected_title) {
					if (strcasecmp($v, $expected_title) === 0) {
						return $k;
					}
				}
			}
			return '';
		};

		if (!function_exists('is_plugin_active') && defined('ABSPATH')) {
			$plugin_file = trailingslashit(ABSPATH) . 'wp-admin/includes/plugin.php';
			if (is_readable($plugin_file)) {
				// phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingVariable
				require_once $plugin_file;
			}
		}

		$is_plugin_active = function ($plugin_basename) {
			$plugin_basename = is_string($plugin_basename) ? $plugin_basename : '';
			if ($plugin_basename === '') {
				return false;
			}

			if (function_exists('is_plugin_active') && is_plugin_active($plugin_basename)) {
				return true;
			}

			// Multisite: network-activated plugins live in active_sitewide_plugins (keys are plugin filenames).
			$sitewide = (array) get_site_option('active_sitewide_plugins', []);
			$sitewide_files = array_map('strval', array_keys($sitewide));
			return in_array($plugin_basename, $sitewide_files, true);
		};

		$google_pro_license_key = defined('SIMPLE_CALENDAR_GOOGLE_PRO_ID')
			? 'simcal_' . (string) SIMPLE_CALENDAR_GOOGLE_PRO_ID
			: $get_license_key('Google Calendar Pro');

		// FullCalendar add-on registers `simcal_installed_addons` with display name "FullCalendar"
		// (see Simple-Calendar-FullCalendar). Match that plus catalog/marketing titles so the
		// license card resolves when SIMPLE_CALENDAR_FULLCALENDAR_ID is absent.
		$fullcalendar_license_key = defined('SIMPLE_CALENDAR_FULLCALENDAR_ID')
			? 'simcal_' . (string) SIMPLE_CALENDAR_FULLCALENDAR_ID
			: $get_license_key(
				[
					__('FullCalendar Extended', 'google-calendar-events'),
					'FullCalendarExtended',
					'FullCalendar Extended',
					'FullCalendar',
				],
			);

		$catalog = [
			'fullcalendar' => [
				'title' => __('FullCalendar Extended', 'google-calendar-events'),
				'description' => __(
					'Adds month, week, day, and list views to your calendar with improved interactivity and display flexibility.',
					'google-calendar-events',
				),
				'learn_more_url' => 'https://simplecalendar.io/downloads/fullcalendar/',
				'buy_url' => simcal_get_url('addons'),
				'is_active' => function_exists('simcal_is_fullcalendar_addon_active')
					? simcal_is_fullcalendar_addon_active()
					: $is_plugin_active('simple-calendar-fullcalendar/simple-calendar-fullcalendar.php'),
				'icon' => 'fullcalendar-extended.svg',
				'license_key' => $fullcalendar_license_key,
			],
			'google_pro' => [
				'title' => __('Google Calendar Pro', 'google-calendar-events'),
				'description' => __(
					'Enjoy 1-click Google Calendar integration with real-time synchronization for both public and private calendars.',
					'google-calendar-events',
				),
				'learn_more_url' => 'https://simplecalendar.io/downloads/google-calendar-pro/',
				'buy_url' => simcal_get_url('addons'),
				'is_active' => function_exists('simcal_is_google_calendar_pro_active')
					? simcal_is_google_calendar_pro_active()
					: false,
				'icon' => 'google-calendar-pro.svg',
				'license_key' => $google_pro_license_key,
			],
			'appointment' => [
				'title' => __('Appointment Calendar', 'google-calendar-events'),
				'description' => __(
					'Enables visitors to book multiple appointments on Google Calendar directly through your WordPress site, as per the shown availability.',
					'google-calendar-events',
				),
				'learn_more_url' => 'https://simplecalendar.io/downloads/book-an-appointment/',
				'buy_url' => simcal_get_url('addons'),
				'is_active' => $is_plugin_active('simple-calendar-appointment/simple-calendar-appointment.php'),
				'icon' => 'appointment-calendar.svg',
				'license_key' => $get_license_key(
					[
						__('Appointment Calendar', 'google-calendar-events'),
						'AppointmentCalendar',
						'Book an Appointment',
					],
					defined('SIMPLE_CALENDAR_APPOINTMENT_ID')
						? ['simcal_' . (string) SIMPLE_CALENDAR_APPOINTMENT_ID]
						: [],
				),
			],
		];

		// Needed for existing license activation / reset AJAX.
		wp_nonce_field('simcal_license_manager', 'simcal_license_manager');
		?>
		<div class="sc_addons_layout">
			<div class="sc_addons_main">
				<div class="sc_setup_card">
							<h2 class="sc_h4 sc_addons_card_title">
								<?php esc_html_e('Sync More. Display Better. Accept Bookings.', 'google-calendar-events'); ?>
							</h2>
							<p class="sc_text--body_b2 sc_text--dark sc_addons_card_subtitle">
								<?php esc_html_e(
        	'Explore three focused addons. Each one extends Simple Calendar in a direction the free version doesn\'t go. 
',
        	'google-calendar-events',
        ); ?>
							</p>

							<div class="sc_addon_list">
								<?php foreach ($catalog as $addon_slug => $addon) { ?>
									<?php
         $addon_slug = (string) $addon_slug;
         $addon_title = isset($addon['title']) ? (string) $addon['title'] : '';
         $addon_desc = isset($addon['description']) ? (string) $addon['description'] : '';
         $learn_more = isset($addon['learn_more_url']) ? (string) $addon['learn_more_url'] : '';
         $buy_url = isset($addon['buy_url']) ? (string) $addon['buy_url'] : '';
         $is_active = !empty($addon['is_active']);
         $license_key = isset($addon['license_key']) ? (string) $addon['license_key'] : '';
         $icon = isset($addon['icon']) ? (string) $addon['icon'] : 'calendar.svg';
         $icon_src = $assets_base . $icon;

         if ($is_active && $license_key) {
         	$field = [
         		'type' => 'license',
         		'ui' => 'card',
         		'addon' => $license_key,
         		'title' => $addon_title,
         		'name' => 'simple-calendar_settings_licenses[keys][' . $license_key . ']',
         		'id' => 'simple-calendar-settings-licenses-keys-' . sanitize_key($license_key),
         		'value' => (string) \simcal_get_license_key($license_key),
         		'class' => ['regular-text', 'ltr', 'sc-btn-input'],
         		'icon_src' => $icon_src,
         		'card_description' => __(
         			'Activate your license to enable automatic updates and premium support for this add-on.',
         			'google-calendar-events',
         		),
         	];

         	\simcal_print_field($field);
         	continue;
         }
         ?>
									<div class="sc_setup_card sc_addon_item">
										<div class="sc_addon_item_header">
											<img src="<?php echo esc_url($icon_src); ?>" alt="" class="sc_addon_item_icon" />
											<h3 class="sc_h5 sc_addon_item_title"><?php echo esc_html($addon_title); ?></h3>
										</div>
										<p class="sc_text--body_b3 sc_addon_item_desc"><?php echo esc_html($addon_desc); ?></p>
										<div class="sc_addon_item_actions">
											<a
												class="sc_btn sc_btn--blue"
												target="_blank"
												href="<?php echo esc_url(simcal_ga_campaign_url($learn_more, 'core-plugin', 'add-ons-learn-more')); ?>"
											>
												<?php esc_html_e('Learn More', 'google-calendar-events'); ?>
											</a>
											<a
												class="sc_btn sc_btn--white"
												target="_blank"
												href="<?php echo esc_url(simcal_ga_campaign_url($buy_url, 'core-plugin', 'add-ons-buy-addon')); ?>"
											>
												<?php esc_html_e('Buy Addon', 'google-calendar-events'); ?>
											</a>
										</div>
									</div>
								<?php } ?>
							</div>

							<p class="sc_text--body_b3">
								<a
									href="#"
									id="simcal-reset-licenses"
									class="sc_link sc_link_muted"
									data-dialog="<?php echo esc_attr__(
         	'WARNING: Are you sure you want to start over and delete all license keys from the settings?',
         	'google-calendar-events',
         ); ?>"
								>
									<?php esc_html_e('Delete your license keys', 'google-calendar-events'); ?>
									<i class="simcal-icon-spinner simcal-icon-spin sc_addons_spinner is_hidden" aria-hidden="true"></i>
								</a>
							</p>
							<div id="sc_reset_licenses_modal" class="sc_connect_modal is_hidden" aria-hidden="true">
								<div class="sc_connect_modal__backdrop" data-sc-reset-licenses-modal-dismiss tabindex="-1"></div>
								<div
									class="sc_connect_modal__panel sc_setup_card"
									role="dialog"
									aria-modal="true"
									aria-labelledby="sc_reset_licenses_modal_title"
								>
									<p id="sc_reset_licenses_modal_title" class="sc_connect_modal__message sc_text--body_b1"></p>
									<div class="sc_connect_form_actions sc_connect_modal__actions">
										<button type="button" class="sc_btn sc_btn--blue" id="sc_reset_licenses_confirm">
											<?php esc_html_e('OK', 'google-calendar-events'); ?>
										</button>
										<button type="button" class="sc_btn sc_btn--white" data-sc-reset-licenses-modal-dismiss>
											<?php esc_html_e('Cancel', 'google-calendar-events'); ?>
										</button>
									</div>
								</div>
							</div>
			</div>
		</div>

		<div class="sc_addons_sidebar">
			<?php include SIMPLE_CALENDAR_PATH . 'includes/admin/pages/connect/sidebar.php'; ?>
		</div>
		<?php
	}

	/**
	 * Add sections.
	 *
	 * @since  3.0.0
	 *
	 * @return array
	 */
	public function add_sections()
	{
		return [];
	}

	/**
	 * Add fields.
	 *
	 * @since  3.0.0
	 *
	 * @return array
	 */
	public function add_fields()
	{
		return [];
	}
}
