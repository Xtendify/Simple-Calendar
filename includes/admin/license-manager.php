<?php
/**
 * License Manager
 *
 * @package SimpleCalendar/Admin
 */
namespace SimpleCalendar\Admin;

if (!defined('ABSPATH')) {
	exit();
}

/**
 * Keeps stored add-on license statuses in sync with the store.
 *
 * The `simple-calendar_licenses_status` option is only written when a license is activated,
 * deactivated or reset from the Add-ons screen. A license that later expires, is revoked, or is
 * deactivated at the store therefore stays "valid" in the database indefinitely, and free-plugin
 * features gated on it - such as hiding the "Powered by Simple Calendar" credit - keep behaving as
 * if the site still had a Pro license.
 *
 * This class re-checks every stored license key against the store on a recurring (daily) cron run
 * and writes the reported status back, so expired and invalid licenses are detected without the
 * site owner touching the Add-ons screen.
 *
 * Checks are fail-safe: when the store cannot be reached, or replies with something unrecognised,
 * the stored status is left exactly as it was instead of being downgraded or cleared.
 *
 * @since 4.3.4
 */
class License_Manager
{
	/**
	 * Cron hook running the license check.
	 */
	const CRON_HOOK = 'simcal_check_addon_licenses';

	/**
	 * Option holding the add-on license keys.
	 */
	const KEYS_OPTION = 'simple-calendar_settings_licenses';

	/**
	 * Option holding the add-on license statuses.
	 */
	const STATUS_OPTION = 'simple-calendar_licenses_status';

	/**
	 * Option holding the timestamp of the last completed check.
	 */
	const LAST_CHECK_OPTION = 'simple-calendar_licenses_last_check';

	/**
	 * License statuses the store can report, and which are safe to store.
	 *
	 * Anything outside this list is treated as an unreadable answer and changes nothing.
	 *
	 * @access private
	 * @var array
	 */
	private static $statuses = [
		'valid',
		'invalid',
		'expired',
		'disabled',
		'revoked',
		'inactive',
		'site_inactive',
		'item_name_mismatch',
		'no_activations_left',
		'failed',
	];

	/**
	 * Hook in.
	 *
	 * @since 4.3.4
	 */
	public function __construct()
	{
		add_action('init', [$this, 'maybe_schedule'], 20);
		add_action(self::CRON_HOOK, [$this, 'check_licenses']);
		add_action('simcal_deactivated', [$this, 'unschedule']);
	}

	/**
	 * Make sure the recurring check is scheduled.
	 *
	 * Runs on every request rather than only on activation, so that existing installations pick the
	 * check up on update, and so a cleared cron array heals itself.
	 *
	 * @since 4.3.4
	 */
	public function maybe_schedule()
	{
		if (function_exists('wp_installing') && wp_installing()) {
			return;
		}

		$recurrence = $this->get_recurrence();
		$scheduled = wp_get_schedule(self::CRON_HOOK);

		if ($scheduled === $recurrence) {
			return;
		}

		if (false !== $scheduled) {
			// The recurrence changed: drop the old event before scheduling the new one.
			wp_clear_scheduled_hook(self::CRON_HOOK);
		}

		wp_schedule_event(time(), $recurrence, self::CRON_HOOK);
	}

	/**
	 * Remove the recurring check.
	 *
	 * @since 4.3.4
	 */
	public function unschedule()
	{
		wp_clear_scheduled_hook(self::CRON_HOOK);
	}

	/**
	 * Re-check every stored license key and save the reported statuses.
	 *
	 * @since 4.3.4
	 */
	public function check_licenses()
	{
		$keys = $this->get_license_keys();
		$checked = [];

		foreach ($keys as $addon => $key) {
			$status = $this->get_remote_status($addon, $key);

			if (is_null($status)) {
				// Store unreachable or answer unreadable: keep whatever we already have.
				continue;
			}

			$checked[$addon] = $status;
		}

		// Re-read now that the (possibly slow) store calls are done: a license may have been
		// activated, deactivated or reset from the Add-ons screen while we were waiting, and that
		// is fresher than anything we hold.
		$stored = get_option(self::STATUS_OPTION, []);
		$stored = is_array($stored) ? $stored : [];
		$current_keys = $this->get_license_keys();
		$statuses = $stored;

		foreach ($checked as $addon => $status) {
			if (!isset($current_keys[$addon]) || $current_keys[$addon] !== $keys[$addon]) {
				// The key changed under us, so this answer is about a key the site no longer uses.
				continue;
			}

			$statuses[$addon] = $status;
		}

		// A status without a license key can no longer be verified, so it must not keep gates open.
		foreach (array_keys($statuses) as $addon) {
			if (!isset($current_keys[$addon])) {
				unset($statuses[$addon]);
			}
		}

		if ($statuses !== $stored) {
			update_option(self::STATUS_OPTION, $statuses);
		}

		update_option(self::LAST_CHECK_OPTION, time(), false);

		/**
		 * Fires after add-on license statuses have been re-checked.
		 *
		 * @since 4.3.4
		 *
		 * @param array $statuses Statuses after the check.
		 * @param array $stored   Statuses before the check.
		 */
		do_action('simcal_licenses_checked', $statuses, $stored);
	}

	/**
	 * Cron recurrence to use for the check.
	 *
	 * @since 4.3.4
	 *
	 * @return string
	 */
	private function get_recurrence()
	{
		/**
		 * Filter how often add-on license statuses are re-checked.
		 *
		 * Must be a registered cron schedule, otherwise the default is used.
		 *
		 * @since 4.3.4
		 *
		 * @param string $recurrence Cron schedule name.
		 */
		$recurrence = (string) apply_filters('simcal_license_check_recurrence', 'daily');
		$schedules = wp_get_schedules();

		return isset($schedules[$recurrence]) ? $recurrence : 'daily';
	}

	/**
	 * Get the stored, non empty license keys, indexed by add-on id.
	 *
	 * @since 4.3.4
	 *
	 * @return array
	 */
	private function get_license_keys()
	{
		$licenses = get_option(self::KEYS_OPTION, []);
		$keys = isset($licenses['keys']) && is_array($licenses['keys']) ? $licenses['keys'] : [];
		$found = [];

		foreach ($keys as $addon => $key) {
			$addon = (string) $addon;
			$key = is_scalar($key) ? trim((string) $key) : '';

			if ('' === $addon || '' === $key) {
				continue;
			}

			$found[$addon] = $key;
		}

		return $found;
	}

	/**
	 * Ask the store for the current status of a license key.
	 *
	 * @since 4.3.4
	 *
	 * @param  string $addon Add-on id, e.g. `simcal_123`.
	 * @param  string $key   License key.
	 *
	 * @return null|string Reported status, or null when it could not be read.
	 */
	private function get_remote_status($addon, $key)
	{
		$item_id = $this->get_item_id($addon);

		if (empty($item_id)) {
			return null;
		}

		$response = wp_remote_post($this->get_store_url(), [
			'timeout' => 15,
			'body' => [
				'edd_action' => 'check_license',
				'license' => $key,
				'item_id' => $item_id,
				'url' => home_url(),
			],
		]);

		if (is_wp_error($response) || 200 !== (int) wp_remote_retrieve_response_code($response)) {
			return null;
		}

		$data = json_decode(wp_remote_retrieve_body($response));

		if (!is_object($data) || !isset($data->license) || !is_string($data->license)) {
			return null;
		}

		$status = strtolower(trim($data->license));

		return in_array($status, self::$statuses, true) ? $status : null;
	}

	/**
	 * Get the store item id for an add-on.
	 *
	 * Add-on ids are stored as `simcal_{item_id}`.
	 *
	 * @since 4.3.4
	 *
	 * @param  string $addon Add-on id.
	 *
	 * @return int Item id, or 0 when the add-on id is not in the expected format.
	 */
	private function get_item_id($addon)
	{
		if (0 !== strpos($addon, 'simcal_')) {
			return 0;
		}

		return absint(substr($addon, 7));
	}

	/**
	 * Get the store URL licenses are checked against.
	 *
	 * @since 4.3.4
	 *
	 * @return string
	 */
	private function get_store_url()
	{
		return defined('SIMPLE_CALENDAR_STORE_URL') ? SIMPLE_CALENDAR_STORE_URL : simcal_get_url('home');
	}
}
