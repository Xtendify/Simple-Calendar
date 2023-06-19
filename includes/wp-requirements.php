<?php
/**
 * WP Requirements
 *
 * Utility to check current PHP version, WordPress version and PHP extensions.
 *
 * @package WP_Requirements
 * @version 1.4.0
 * @author  Fulvio Notarstefano <fulvio.notarstefano@gmail.com>
 * @link    https://github.com/nekojira/wp-requirements
 * @license GPL2+
 */

if (!class_exists('SimCal_WP_Requirements')) {
	class SimCal_WP_Requirements
	{
		/**
		 * Plugin name.
		 *
		 * @access private
		 * @var string
		 */
		private $name = '';

		/**
		 * Plugin main file.
		 *
		 * plugin_basename( __FILE__ )
		 *
		 * @access private
		 * @var string
		 */
		private $plugin = '';

		/**
		 * WordPress.
		 *
		 * @access private
		 * @var bool
		 */
		private $wp = true;

		/**
		 * PHP.
		 *
		 * @access private
		 * @var bool
		 */
		private $php = true;

		/**
		 * PHP Extensions.
		 *
		 * @access private
		 * @var bool
		 */
		private $extensions = true;

		/**
		 * Requirements to check.
		 *
		 * @access private
		 * @var array
		 */
		private $requirements = [];

		/**
		 * Results failures.
		 *
		 * Associative array with requirements results.
		 *
		 * @access private
		 * @var array
		 */
		private $failures = [];

		/**
		 * Admin notice.
		 *
		 * @access private
		 * @var string
		 */
		private $notice = '';

		/**
		 * Run checks.
		 *
		 * @param string $name         The plugin name.
		 * @param string $plugin       Output of `plugin_basename( __FILE__ )`.
		 * @param array  $requirements Associative array with requirements.
		 */
		public function __construct($name, $plugin, $requirements)
		{
			$this->name = htmlspecialchars(strip_tags($name));
			$this->plugin = $plugin;
			$this->requirements = $requirements;

			if (!empty($requirements) && is_array($requirements)) {
				$failures = $extensions = [];

				$requirements = array_merge(
					[
						'WordPress' => '',
						'PHP' => '',
						'Extensions' => '',
					],
					$requirements
				);

				// Check for WordPress version.
				if ($requirements['WordPress'] && is_string($requirements['WordPress'])) {
					if (function_exists('get_bloginfo')) {
						$wp_version = get_bloginfo('version');
						if (version_compare($wp_version, $requirements['WordPress']) === -1) {
							$failures['WordPress'] = $wp_version;
							$this->wp = false;
						}
					}
				}

				// Check fo PHP version.
				if ($requirements['PHP'] && is_string($requirements['PHP'])) {
					if (version_compare(PHP_VERSION, $requirements['PHP']) === -1) {
						$failures['PHP'] = PHP_VERSION;
						$this->php = false;
					}
				}

				// Check fo PHP Extensions.
				if ($requirements['Extensions'] && is_array($requirements['Extensions'])) {
					foreach ($requirements['Extensions'] as $extension) {
						if ($extension && is_string($extension)) {
							$extensions[$extension] = extension_loaded($extension);
						}
					}
					if (in_array(false, $extensions)) {
						foreach ($extensions as $extension_name => $found) {
							if ($found === false) {
								$failures['Extensions'][$extension_name] = $extension_name;
							}
						}
						$this->extensions = false;
					}
				}

				$this->failures = $failures;
			} else {
				trigger_error('WP Requirements: the requirements are invalid.', E_USER_ERROR);
			}
		}

		/**
		 * Get requirements results.
		 *
		 * @return array
		 */
		public function failures()
		{
			return $this->failures;
		}

		/**
		 * Check if versions check pass.
		 *
		 * @return bool
		 */
		public function pass()
		{
			if (in_array(false, [$this->wp, $this->php, $this->extensions])) {
				return false;
			}
			return true;
		}

		/**
		 * Notice message.
		 *
		 * @param  string $message An additional message.
		 *
		 * @return string
		 */
		public function get_notice($message = '')
		{
			$notice = '';
			$name = $this->name;
			$failures = $this->failures;

			if (!empty($failures) && is_array($failures)) {
				$notice = '<div class="error">' . "\n";
				$notice .= "\t" . '<p>' . "\n";
				$notice .= '<strong>' . sprintf('%s could not be activated.', $name) . '</strong><br>';

				foreach ($failures as $requirement => $found) {
					$required = $this->requirements[$requirement];

					if ('Extensions' == $requirement) {
						if (is_array($found)) {
							$notice .= sprintf('Required PHP Extension(s) not found: %s.', join(', ', $found)) . '<br>';
						}
					} else {
						$notice .=
							sprintf('Required %1$s version: %2$s - Version found: %3$s', $requirement, $required, $found) . '<br>';
					}
				}

				$notice .= '<em>' . sprintf('Please update to meet %s requirements.', $name) . '</em>' . "\n";
				$notice .= "\t" . '</p>' . "\n";
				if ($message) {
					$notice .= $message;
				}
				$notice .= '</div>';
			}

			return $notice;
		}

		/**
		 * Print notice.
		 */
		public function print_notice()
		{
			echo $this->notice;
		}

		/**
		 * Deactivate plugin.
		 */
		public function deactivate_plugin()
		{
			if (function_exists('deactivate_plugins') && function_exists('plugin_basename')) {
				deactivate_plugins($this->plugin);
			}
		}

		/**
		 * Deactivate plugin and display admin notice.
		 *
		 * @param string $message An additional message in notice.
		 */
		public function halt($message = '')
		{
			$this->notice = $this->get_notice($message);

			if ($this->notice && function_exists('add_action')) {
				add_action('admin_notices', [$this, 'print_notice']);
				add_action('admin_init', [$this, 'deactivate_plugin']);

				if (isset($_GET['activate'])) {
					unset($_GET['activate']);
				}
			}
		}
	}
}
