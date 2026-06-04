<?php
/**
 * Settings Pages
 *
 * @package SimpleCalendar/Admin
 */
namespace SimpleCalendar\Admin;

use SimpleCalendar\Abstracts\Field;
use SimpleCalendar\Abstracts\Admin_Page;

if (!defined('ABSPATH')) {
	exit();
}

/**
 * Admin pages class.
 *
 * Handles settings pages and settings UI in admin dashboard.
 *
 * @since 3.0.0
 */
class Pages
{
	/**
	 * Current settings page.
	 *
	 * @access private
	 * @var string
	 */
	private $page = '';

	/**
	 * Default tab.
	 *
	 * @access private
	 * @var string
	 */
	private $tab = '';

	/**
	 * Settings pages.
	 *
	 * @access private
	 * @var array
	 */
	private $settings = [];

	/**
	 * Constructor.
	 *
	 * @since 3.0.0
	 *
	 * @param string $page
	 */
	public function __construct($page = 'settings')
	{
		$this->page = $page;
		$settings_pages = !is_null(\SimpleCalendar\plugin()->objects) ? simcal_get_admin_pages() : '';
		$settings_page_tabs = [];
		$tabs = isset($settings_pages[$page]) ? $settings_pages[$page] : false;

		if ($tabs && is_array($tabs)) {
			foreach ($tabs as $tab) {
				$settings_page = simcal_get_admin_page($tab);

				if ($settings_page instanceof Admin_Page) {
					$settings_page_tabs[$settings_page->id] = $settings_page;
				}
			}

			$this->settings = $settings_page_tabs;
		}

		// The first tab is the default tab when opening a page.
		$this->tab = isset($tabs[0]) ? $tabs[0] : '';

		do_action('simcal_admin_pages', $page);
	}

	/**
	 * Get settings pages.
	 *
	 * @since  3.0.0
	 * @access private
	 *
	 * @return array
	 */
	public function get_settings()
	{
		$settings = [];

		if (!empty($this->settings) && is_array($this->settings)) {
			foreach ($this->settings as $id => $object) {
				if ($object instanceof Admin_Page) {
					$settings_page = $object->get_settings();

					if (isset($settings_page[$id])) {
						$settings[$id] = $settings_page[$id];
					}
				}
			}
		}

		return $settings;
	}

	/**
	 * Register settings.
	 *
	 * Adds settings sections and fields to settings pages.
	 *
	 * @since 3.0.0
	 *
	 * @param array $settings
	 */
	public function register_settings($settings = [])
	{
		$settings = $settings ? $settings : $this->get_settings();

		if (!empty($settings) && is_array($settings)) {
			foreach ($settings as $tab_id => $settings_page) {
				if (isset($settings_page['sections'])) {
					$sections = $settings_page['sections'];

					if (!empty($sections) && is_array($sections)) {
						foreach ($sections as $section_id => $section) {
							add_settings_section(
								$section_id,
								isset($section['title']) ? $section['title'] : '',
								isset($section['callback']) ? $section['callback'] : '',
								'simple-calendar_' . $this->page . '_' . $tab_id,
							);

							if (isset($section['fields'])) {
								$fields = $section['fields'];

								if (!empty($fields) && is_array($fields)) {
									foreach ($fields as $field) {
										if (isset($field['id']) && isset($field['type'])) {
											$field_object = simcal_get_field($field, $field['type']);

											if ($field_object instanceof Field) {
												add_settings_field(
													$field['id'],
													isset($field['title']) ? $field['title'] : '',
													[$field_object, 'html'],
													'simple-calendar_' . $this->page . '_' . $tab_id,
													$section_id,
												);
											}
										}
									}
								}
							}

							$page = simcal_get_admin_page($tab_id);

							register_setting(
								'simple-calendar_' . $this->page . '_' . $tab_id,
								'simple-calendar_' . $this->page . '_' . $tab_id,
								$page instanceof Admin_Page ? [$page, 'validate'] : '',
							);
						}
					}
				}
			}
		}
	}

	/**
	 * Print Settings Pages.
	 *
	 * @since 3.0.0
	 */
	public function html()
	{
		global $current_tab;

		$current_tab = empty($_GET['tab']) ? $this->tab : sanitize_title($_GET['tab']);
		$this->tab = $current_tab;

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$current_page = isset($_GET['page']) ? sanitize_text_field((string) $_GET['page']) : '';

		$settings_pages = $this->get_settings();
		if (empty($settings_pages) || !is_array($settings_pages)) {
			return;
		}

		$page_slug = $this->page;
		$views_path = SIMPLE_CALENDAR_PATH . 'includes/admin/views/';

		if ('simple-calendar_add_ons' === $current_page && 'add-ons' === $this->page) {
			include $views_path . 'page-add-ons.php';
			return;
		}

		if ('simple-calendar_misc_settings' === $current_page) {
			include $views_path . 'page-misc-settings.php';
			return;
		}

		if ('simple-calendar_tools' === $current_page && 'tools' === $this->page) {
			include $views_path . 'page-tools.php';
		}
	}
}
