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
 * deactivated or reset from the Add-ons screen. A license that later expires or is revoked at the
 * store therefore stays "valid" in the database indefinitely, and free-plugin features gated on it -
 * such as hiding the "Powered by Simple Calendar" credit - keep behaving as if the site still had a
 * Pro license.
 *
 * This class re-checks every stored license key against the store on a recurring (daily) cron run,
 * so an expired or revoked license is detected without the site owner touching the Add-ons screen.
 *
 * It is deliberately biased against taking Pro behavior away from a paying customer. A stored status
 * is only ever overwritten by an answer that conclusively settles entitlement; anything else - an
 * unreachable store, an unreadable answer, or an answer that only says "this request could not be
 * matched to an activation" - leaves the stored status exactly as it was.
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
	 * Option holding a short report about the last completed check.
	 */
	const LAST_CHECK_OPTION = 'simple-calendar_licenses_last_check';

	/**
	 * Transient guarding against two checks running at once.
	 */
	const LOCK_TRANSIENT = 'simple-calendar_licenses_checking';

	/**
	 * License statuses the store can report.
	 *
	 * Anything outside this list is treated as an unreadable answer.
	 *
	 * @access private
	 * @var array
	 */
	private static $known = [
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
	 * Statuses that conclusively settle whether this site is entitled, and may be stored.
	 *
	 * The statuses deliberately left out say nothing about entitlement, only that the store could
	 * not match this request to an activation: `site_inactive` and `inactive` (the key is fine, this
	 * URL is not among its activations - which is what a move to https, to www, or to a new domain
	 * looks like), `no_activations_left` (paid, slots full), and `item_name_mismatch` / `failed` (the
	 * key does not match the item id we sent). Storing any of those would silently strip Pro
	 * behavior from a customer whose license is paid and unexpired, with nothing in the UI to
	 * explain it, so they are reported instead and change nothing.
	 *
	 * @access private
	 * @var array
	 */
	private static $conclusive = ['valid', 'invalid', 'expired', 'disabled', 'revoked'];

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

		// Keep the due time we already had, so a recurrence that resolves differently between
		// requests cannot turn this into "clear and re-run" on every page load.
		$next = wp_next_scheduled(self::CRON_HOOK);

		if (false !== $scheduled) {
			wp_clear_scheduled_hook(self::CRON_HOOK);
		}

		if (empty($next)) {
			// Spread first runs out, so an update rolling out across many sites does not make them
			// all call the store at the same moment.
			$next = time() + wp_rand(0, 6 * HOUR_IN_SECONDS);
		}

		wp_schedule_event($next, $recurrence, self::CRON_HOOK);
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
	 * Re-check every stored license key and save the statuses that settle entitlement.
	 *
	 * @since 4.3.4
	 */
	public function check_licenses()
	{
		if (get_transient(self::LOCK_TRANSIENT)) {
			// A previous run is still waiting on the store.
			return;
		}

		set_transient(self::LOCK_TRANSIENT, 1, 5 * MINUTE_IN_SECONDS);

		$keys = $this->get_license_keys();
		$settled = [];
		$unresolved = [];

		foreach ($keys as $addon => $key) {
			$reported = $this->get_remote_status($addon, $key);

			if (is_null($reported)) {
				// Store unreachable, or the answer could not be read.
				$unresolved[$addon] = 'no_answer';
				continue;
			}

			if (!in_array($reported, self::$conclusive, true)) {
				// A real answer, but not one that settles entitlement. See self::$conclusive.
				$unresolved[$addon] = $reported;
				continue;
			}

			$settled[$addon] = $reported;
		}

		// Re-read now that the (possibly slow) store calls are done: a license may have been
		// activated, deactivated or reset from the Add-ons screen while we were waiting, and that is
		// fresher than anything we hold.
		$stored = get_option(self::STATUS_OPTION, []);
		$stored = is_array($stored) ? $stored : [];
		$current_keys = $this->get_license_keys();
		$statuses = $stored;

		foreach ($settled as $addon => $status) {
			if (!isset($current_keys[$addon]) || $current_keys[$addon] !== $keys[$addon]) {
				// The key changed under us, so this answer is about a key the site no longer uses.
				continue;
			}

			$statuses[$addon] = $status;
		}

		if ($statuses !== $stored) {
			update_option(self::STATUS_OPTION, $statuses);
		}

		// Statuses are never removed here. A key disappearing from the settings is not evidence that
		// entitlement ended, and deactivating or resetting a license already clears the status
		// synchronously, so pruning would only add a destructive edge with nothing to gain.

		update_option(
			self::LAST_CHECK_OPTION,
			[
				'time' => time(),
				'settled' => count($settled),
				'unresolved' => $unresolved,
			],
			false,
		);

		delete_transient(self::LOCK_TRANSIENT);

		/**
		 * Fires after add-on license statuses have been re-checked.
		 *
		 * @since 4.3.4
		 *
		 * @param array $statuses   Statuses after the check.
		 * @param array $stored     Statuses before the check.
		 * @param array $unresolved Add-ons whose entitlement could not be settled, mapped to the
		 *                          status reported for them, or `no_answer`.
		 */
		do_action('simcal_licenses_checked', $statuses, $stored, $unresolved);
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
			// Shorter than the activation call: several add-ons are checked in one cron run, and the
			// whole run should stay well inside WP_CRON_LOCK_TIMEOUT.
			'timeout' => 10,
			// Matches the activation call in Admin\Ajax::manage_add_on_license(), so that a host able
			// to activate a license is also able to re-check it.
			'sslverify' => false,
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

		return in_array($status, self::$known, true) ? $status : null;
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
	 * Same resolution as the activation call: the override constant if an add-on defines one,
	 * otherwise the plugin homepage, which is where the store's API lives.
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
