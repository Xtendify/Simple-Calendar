<?php
/**
 * Welcome Page Class
 *
 * Adapted from analogue code found in WoCommerce, EDD and WordPress itself.
 *
 * @package SimpleCalendar/Admin
 */
namespace SimpleCalendar\Admin;

if (!defined('ABSPATH')) {
	exit();
}

/**
 * Welcome page.
 *
 * Shows a feature overview for the new version (major) and credits.
 *
 * @since 3.0.0
 */
class Welcome
{
	/**
	 * Install type.
	 *
	 * @access public
	 * @var array
	 */
	public $install = '';

	/**
	 * Hook in tabs.
	 *
	 * @since 3.0.0
	 */
	public function __construct()
	{
		$this->install = isset($_GET['simcal_install']) ? esc_attr($_GET['simcal_install']) : '';

		add_action('admin_menu', [$this, 'welcome_page_tabs']);
		add_action('admin_head', [$this, 'remove_submenu_pages']);
	}

	/**
	 * Add page screens.
	 *
	 * @since 3.0.0
	 */
	public function welcome_page_tabs()
	{
		$welcome_page_name = __('About Simple Calendar', 'google-calendar-events');
		$welcome_page_title = __('Welcome to Simple Calendar', 'google-calendar-events');

		$page = isset($_GET['page']) ? $_GET['page'] : 'simple-calendar_about';

		switch ($page) {
			case 'simple-calendar_about':
				$page = add_dashboard_page($welcome_page_title, $welcome_page_name, 'manage_options', 'simple-calendar_about', [
					$this,
					'about_screen',
				]);
				break;

			case 'simple-calendar_credits':
				$page = add_dashboard_page(
					$welcome_page_title,
					$welcome_page_name,
					'manage_options',
					'simple-calendar_credits',
					[$this, 'credits_screen']
				);
				break;

			case 'simple-calendar_translators':
				$page = add_dashboard_page(
					$welcome_page_title,
					$welcome_page_name,
					'manage_options',
					'simple-calendar_translators',
					[$this, 'translators_screen']
				);
				break;
		}
	}

	/**
	 * Remove dashboard page links.
	 *
	 * @since 3.0.0
	 */
	public function remove_submenu_pages()
	{
		remove_submenu_page('index.php', 'simple-calendar_about');
		remove_submenu_page('index.php', 'simple-calendar_credits');
		remove_submenu_page('index.php', 'simple-calendar_translators');
	}

	/**
	 * Main nav links at top & bottom.
	 *
	 * @since 3.0.0
	 */
	public function main_nav_links()
	{
		?>
		<div class="simcal-font-poppins simcal-font-medium">
			<a href="<?php echo admin_url('edit.php?post_type=calendar'); ?>"
				class="button button-primary !simcal-bg-sc_purple !simcal-rounded-[100px] !simcal-m-[6px] !simcal-border-solid !simcal-border-white !simcal-border-b hover:!simcal-bg-white hover:!simcal-text-sc_green-200"><?php _e(
    	'Calendars',
    	'google-calendar-events'
    ); ?></a>
			<a href="<?php echo esc_url(add_query_arg('page', 'simple-calendar_settings', admin_url('admin.php'))); ?>"
				class="button button-primary !simcal-bg-sc_purple !simcal-rounded-[100px] !simcal-m-[6px] !simcal-border-solid !simcal-border-white !simcal-border-b hover:!simcal-bg-white hover:!simcal-text-sc_green-200 "><?php _e(
    	'Settings',
    	'google-calendar-events'
    ); ?></a>
			<a href="<?php echo simcal_ga_campaign_url(simcal_get_url('addons'), 'core-plugin', 'welcome-page'); ?>"
				class="docs button button-primary !simcal-bg-sc_purple !simcal-rounded-[100px] !simcal-m-[6px] !simcal-border-solid !simcal-border-white !simcal-border-b hover:!simcal-bg-white hover:!simcal-text-sc_green-200"
				target="_blank"><?php _e('Add-ons', 'google-calendar-events'); ?></a>
			<a href="<?php echo simcal_ga_campaign_url(simcal_get_url('docs'), 'core-plugin', 'welcome-page'); ?>"
				class="docs button button-primary !simcal-bg-sc_purple !simcal-rounded-[100px] !simcal-m-[6px] !simcal-border-solid !simcal-border-white !simcal-border-b hover:!simcal-bg-white hover:!simcal-text-sc_green-200"
				target="_blank"><?php _e('Documentation', 'google-calendar-events'); ?></a>
		</div>
		<?php
	}
	/**
	 * nav links at top .
	 *
	 * @since 3.0.0
	 */
	public function nav_links()
	{
		?>
		<h2 class="simcal-nav-tab-wrapper simcal-font-poppins">
			<a class="nav-tab simcal-border-0 simcal-bg-transparent simcal-font-medium simcal-text-sc_grey-100 hover:simcal-bg-transparent focus:simcal-bg-transparent <?php if (
   	$_GET['page'] == 'simple-calendar_about'
   ) {
   	echo 'nav-tab-active !simcal-text-sc_black-100 simcal-border-b-[3px] simcal-border-sc_green-200 focus:simcal-border-b-[3px] hover:simcal-border-b-[3px] active:simcal-border-b-[3px] focus:simcal-border-sc_green-200 hover:simcal-border-sc_green-200 active:simcal-border-sc_green-200 focus:simcal-shadow-none';
   } ?>"
				href="<?php echo esc_url(admin_url(add_query_arg(['page' => 'simple-calendar_about'], 'index.php'))); ?>"><?php _e(
	"What's New",
	'google-calendar-events'
); ?></a>
			<a class="nav-tab simcal-border-0 simcal-bg-transparent simcal-font-medium simcal-text-sc_grey-100 hover:simcal-bg-transparent focus:simcal-bg-transparent <?php if (
   	$_GET['page'] == 'simple-calendar_credits'
   ) {
   	echo 'nav-tab-active !simcal-text-sc_black-100 simcal-border-b-[3px] simcal-border-sc_green-200 focus:simcal-border-b-[3px] hover:simcal-border-b-[3px] active:simcal-border-b-[3px] focus:simcal-border-sc_green-200 hover:simcal-border-sc_green-200 active:simcal-border-sc_green-200 focus:simcal-shadow-none';
   } ?>"
				href="<?php echo esc_url(admin_url(add_query_arg(['page' => 'simple-calendar_credits'], 'index.php'))); ?>"><?php _e(
	'Credits',
	'google-calendar-events'
); ?></a>
			<a class="nav-tab simcal-border-0 simcal-bg-transparent simcal-font-medium simcal-text-sc_grey-100 hover:simcal-bg-transparent focus:simcal-bg-transparent <?php if (
   	$_GET['page'] == 'simple-calendar_translators'
   ) {
   	echo 'nav-tab-active !simcal-text-sc_black-100 simcal-border-b-[3px] simcal-border-sc_green-200 focus:simcal-border-b-[3px] hover:simcal-border-b-[3px] active:simcal-border-b-[3px] focus:simcal-border-sc_green-200 hover:simcal-border-sc_green-200 active:simcal-border-sc_green-200 focus:simcal-shadow-none';
   } ?>" href="<?php echo esc_url(
	admin_url(add_query_arg(['page' => 'simple-calendar_translators'], 'index.php'))
); ?>"><?php _e('Translators', 'google-calendar-events'); ?></a>
		</h2>
		<?php
	}
	/**
	 * intro section .
	 *
	 * @since 3.0.0
	 */
	public function sc_intro_section()
	{
		$welcome_image_about_path = SIMPLE_CALENDAR_ASSETS . '/images/pages/welcome'; ?>
		<div class="simcal-max-w-[100%]">
			<div
				class="simcal-mt-[100px] simcal-h-[408px] simcal-border-2 simcal-relative simcal-bg-[url('../images/pages/welcome/bg-banner-img.png')] simcal-rounded-[20px]">
				<div class="simcal-pl-[4%] simcal-pt-[61px] simcal-w-[51%] lg:simcal-w-[60%] simcal-text-white ">
					<?php $this->intro(); ?>
				</div>
				<div class="simcal-max-w-[100%]">
					<div
						class="simcal-absolute simcal-right-[-45px] simcal-top-[-30px] lg:simcal-w-[60%] lg:simcal-top-[73px] lg:simcal-right-[-27px] 2xl:simcal-w-[62%] 2xl:simcal-top-[0px] 2xl:simcal-right-[-39px] 3xl:simcal-w-[56%] 3xl:simcal-top-[-17] 3xl:simcal-right-[-40px]">
						<img src="<?php echo esc_url($welcome_image_about_path) . '/banner-right.png'; ?>" />
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Intro shown on every about page screen.
	 *
	 * @since 3.0.0
	 */
	private function intro()
	{
		?>
		<div class="simcal-font-poppins simcal-font-bold simcal-text-4xl 2xl:simcal-text-3xl">
			<?php printf(__('Welcome to Simple Calendar %s', 'google-calendar-events'), SIMPLE_CALENDAR_VERSION); ?>
		</div>
		<div
			class="about-text !simcal-m-[0px] !simcal-mt-[19px] !simcal-text-white simcal-font-poppins !simcal-text-lg 2xl:!simcal-text-base">
			<?php
   // Difference message if updating vs fresh install.
   if ('update' == $this->install) {
   	$message = __('Thanks for updating to the latest version!', 'google-calendar-events');
   } else {
   	$message = __('Thanks for installing!', 'google-calendar-events');
   }

   echo $message;

   /* translators: %s prints the current version of the plugin. */
   printf(
   	' ' . __('Simple Calendar %s has a few display options to configure. ', 'google-calendar-events'),
   	SIMPLE_CALENDAR_VERSION
   );
   ?>
			<a href="<?php echo simcal_ga_campaign_url(simcal_get_url('docs'), 'core-plugin', 'welcome-page'); ?>"
				target="_blank"><br>
				<?php _e('Check out our documentation', 'google-calendar-events'); ?>
			</a>
			<?php _e('to get started now.', 'google-calendar-events'); ?>
		</div>

		<!-- <div class="simcal-badge">&nbsp;</div> -->
		<div class="simcal-pt-[54px] max-3xl:simcal-pt-[25px]">
			<?php $this->main_nav_links(); ?>
		</div>
		<?php
	}

	/**
	 * Output the about screen.
	 *
	 * @since 3.0.0
	 */
	public function about_screen()
	{
		$welcome_image_about_path = SIMPLE_CALENDAR_ASSETS . '/images/pages/welcome';
		$image_about_path = SIMPLE_CALENDAR_ASSETS . '/images/pages/settings';
		?>
		<div id="simcal-welcome">
			<div
				class="wrap about-wrap whats-new-wrap simcal-max-w-[100%] simcal-font-poppins simcal-mr-[4%] simcal-ml-[4%] simcal-text-sm">
				<?php $this->nav_links(); ?>
				<?php $this->sc_intro_section(); ?>
				<div
					class="simcal-grid simcal-gap-x-10 lg:simcal-gap-x-7 simcal-grid-cols-3 simcal-mt-[170px] simcal-font-poppins">
					<div
						class="simcal-h-[436px] simcal-bg-sc_cream-100 simcal-text-white simcal-pt-[52px] simcal-rounded-[15px] ">
						<div class="simcal-pl-[5%] simcal-pr-[5%] simcal-leading-[31px]">
							<span class="simcal-text-[24px] max-3xl:simcal-text-[20px] simcal-font-bold">
								<?php _e('Configure event colors, number of events to display, grid or list style', 'google-calendar-events'); ?>
							</span>
							<span class="simcal-text-[24px] max-3xl:simcal-text-[20px]">
								<?php _e(' and more.', 'google-calendar-events'); ?>
							</span>
						</div>
						<div
							class="simcal-ml-[20px] simcal-mr-[20px] simcal-mt-[40px] max-xl:simcal-mt-[109px] max-2xl:simcal-mt-[88px] max-3xl:simcal-mt-[106px]">
							<img src="<?php echo esc_url($welcome_image_about_path) . '/cal-meeting.png'; ?>" />
						</div>
					</div>
					<div
						class="simcal-h-[436px] simcal-bg-sc_green-200 simcal-text-white simcal-pt-[52px] simcal-rounded-[15px] simcal-relative">
						<div class="simcal-pl-[5%] simcal-leading-[31px]">
							<span class="simcal-text-[24px] max-3xl:simcal-text-[20px] simcal-font-bold">
								<?php _e('Mobile responsive and ', 'google-calendar-events'); ?>
							</span><br>
							<span class="simcal-text-[24px] max-3xl:simcal-text-[20px] simcal-font-normal">
								<?php _e('widget ready.', 'google-calendar-events'); ?>
							</span>
						</div>
						<img class="simcal-absolute simcal-bottom-0 lg:simcal-w-[80%]"
							src="<?php echo esc_url($welcome_image_about_path) . '/jan-cal.png'; ?>" />
						<img class="simcal-absolute simcal-inset-y-20 simcal-right-0"
							src="<?php echo esc_url($welcome_image_about_path) . '/cof-house.png'; ?>" />
					</div>
					<div
						class="simcal-h-[436px] simcal-bg-sc_blue-300 simcal-text-white simcal-pt-[52px] simcal-rounded-[15px]">
						<div class="simcal-pl-[5%] simcal-pr-[5%] simcal-leading-[31px]">
							<span class="simcal-text-[24px] max-3xl:simcal-text-[20px] simcal-font-bold">
								<?php _e('Add even more display options with add-ons like', 'google-calendar-events'); ?>
							</span>
							<span class="simcal-text-[24px] max-3xl:simcal-text-[20px]">
								<?php _e('Full Calendar and Google Calendar Pro.', 'google-calendar-events'); ?>
							</span>
						</div>
						<div
							class="simcal-ml-[20px] simcal-mr-[20px] simcal-mt-[41px] max-2xl:simcal-mt-[84px] max-3xl:simcal-mt-[107px] max-xl:simcal-mt-[109px]">
							<img src="<?php echo esc_url($welcome_image_about_path) . '/mar-cal.png'; ?>" />
						</div>
					</div>
				</div>
				<div
					class="simcal-mt-[50px] simcal-p-[3%] simcal-max-w-[100%] simcal-flex simcal-bg-sc_green-100 simcal-font-poppins simcal-rounded">
					<div class="simcal-w-[36%]">
						<div class="simcal-flex">
							<div>
								<img src="<?php echo esc_url($image_about_path) . '/black-tick.png'; ?>" />
							</div>
							<div
								class="simcal-text-sc_green-200 simcal-m-auto simcal-ml-2.5 simcal-font-semibold simcal-text-xl">
								<span>
									<?php _e('Pro Version', 'google-calendar-events'); ?>
								</span>
							</div>
						</div>
						<div class="simcal-pt-[19px] simcal-text-sc_grey-100 simcal-text-base">
							<span>
								<?php _e(
        	'Calendars configured to use the',
        	'google-calendar-events'
        ); ?><b class="simcal-text-sc_black-100 hover:simcal-text-sc_green-200">
									<?php _e(' Google Calendar Pro add-on', 'google-calendar-events'); ?>
								</b>
								<?php _e('use a different method of authorization.', 'google-calendar-events'); ?>
							</span>
						</div>
						<div class="simcal-pt-[29px]">
							<a
								href="https://simplecalendar.io/addons/?utm_source=inside-plugin&utm_medium=link&utm_campaign=core-plugin&utm_content=welcome-link">
								<button type="button"
									class="simcal-flex simcal-justify-center simcal-items-center simcal-w-[100%] simcal-h-[40px] simcal-bg-sc_green-200 simcal-text-white simcal-text-base simcal-font-medium simcal-rounded-md simcal-font-poppins">
									<img class="simcal-p-[8px]"
										src="<?php echo esc_url($image_about_path) . '/crown.png'; ?>" />
									<?php _e('Get Pro Version', 'google-calendar-events'); ?>
								</button>
							</a>
						</div>
					</div>
					<div class="simcal-mx-auto simcal-mt-[4%]">
						<img src="<?php echo esc_url($image_about_path) . '/arrow.png'; ?>" />
					</div>
					<div class="simcal-bg-sc_green-100 simcal-rounded-r-[5px] simcal-pr-[1%] simcal-pt-[5px] ">
						<div class="">
							<div class="simcal-flex simcal-text-sc_grey-100 simcal-mt-[21px]">
								<div class="simcal-mt-[2px]">
									<img src="<?php echo esc_url($image_about_path) . '/green-tick.png'; ?>" />
								</div>
								<div class="simcal-ml-[9px] simcal-text-base simcal-text-sc_grey-100 simcal-font-normal">
									<span>
										<?php _e('Display events from both private and public Google Calendars.', 'google-calendar-events'); ?>
									</span>
								</div>
							</div>
							<div class="simcal-flex simcal-mt-[14px] ">
								<div class="simcal-mt-[2px]">
									<img src="<?php echo esc_url($image_about_path) . '/green-tick.png'; ?>" />
								</div>
								<div class="simcal-ml-[9px] simcal-text-base simcal-text-sc_grey-100 simcal-font-normal ">
									<span>
										<?php _e('Display a list of attachments with links to their original source.', 'google-calendar-events'); ?>
									</span>
								</div>
							</div>
							<div class="simcal-flex simcal-mt-[14px] ">
								<div class="simcal-mt-[2px]">
									<img src="<?php echo esc_url($image_about_path) . '/green-tick.png'; ?>" />
								</div>
								<div class="simcal-ml-[9px] simcal-text-base simcal-text-sc_grey-100 simcal-font-normal">
									<span>
										<?php _e('Many More...', 'google-calendar-events'); ?>
									</span>
								</div>
							</div>
						</div>
					</div>


				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Output the credits screen.
	 *
	 * @since 3.0.0
	 */
	public function credits_screen()
	{
		$image_about_path = SIMPLE_CALENDAR_ASSETS . '/images'; ?>
		<div id="simcal-welcome">
			<div
				class="wrap about-wrap credits-wrap simcal-max-w-[100%] simcal-font-poppins simcal-mr-[4%] simcal-ml-[4%] simcal-text-sm">
				<?php $this->nav_links(); ?>
				<?php $this->sc_intro_section(); ?>
				<div
					class="simcal-mt-[121px] simcal-w-[100%] simcal-h-[auto] simcal-bg-sc_green-100 simcal-rounded-[20px] simcal-pl-[5%]">
					<div class="simcal-pt-[62px] simcal-font-bold simcal-text-xl simcal-text-sc_black-200">
						<?php _e("Simple Calendar is created by a worldwide team of developers. If you'd ", 'google-calendar-events'); ?>
					</div>
					<div class="simcal-text-xl simcal-text-sc_black-200">
						<?php _e(
      	"like to contribute please visit our <a href='%s' class='simcal-text-sc_green-200' target='_blank'>GitHub repo. </a>",
      	'google-calendar-events'
      ); ?>
					</div>
					<div class="simcal-flex simcal-pt-[20px]">
						<div class="simcal-w-[60%]">
							<?php echo $this->contributors(); ?>
						</div>
						<div class="simcal-w-[40%]">
							<div
								class="simcal-mt-[20px] simcal-mr-[5%] simcal-w-[83%] simcal-h-[366px] simcal-rounded-[20px] simcal-bg-white ">
								<div class="simcal-pl-[42%] ">
									<img class="simcal-pt-[19px]"
										src="<?php echo esc_url($image_about_path) . '/rating.png'; ?>" />
								</div>
								<div class="simcal-mt-[15px] simcal-text-center simcal-font-semibold simcal-text-lg ">
									<Span>
										<?php _e('Please Rate Us !', 'google-calendar-events'); ?>
									</Span>
								</div>
								<div
									class="simcal-mt-[5px] simcal-text-center simcal-font-normal simcal-text-base simcal-text-gray-500">
									<Span>
										<?php _e('If you like Simple Calendar please Rate Us', 'google-calendar-events'); ?>
									</Span>
								</div>
								<div class="simcal-mt-[44px]">
									<?php // Rating function is used here

		sc_rating(); ?>
								</div>
								<a
									href="https://simplecalendar.io/go/leave-a-review--theme?utm_source=inside-plugin&utm_medium=link&utm_campaign=core-plugin&utm_content=welcome-link">
									<button type="button"
										class="simcal-mt-[20px] simcal-m-auto simcal-flex simcal-justify-center simcal-items-center simcal-w-[80%] simcal-h-[40px] simcal-bg-sc_green-200 simcal-text-white simcal-text-xl simcal-font-medium simcal-rounded-md">
										<?php _e('Rate Now', 'google-calendar-events'); ?>
									</button>
								</a>
								<div class="simcal-mt-[25px] simcal-text-center simcal-text-base simcal-text-sc_blue-200">
									<a class="hover:simcal-text-sc_green-200"
										href="https://simplecalendar.io/go/reviews--theme?utm_source=inside-plugin&utm_medium=link&utm_campaign=core-plugin&utm_content=welcome-link">
										<?php _e('See All Customers Reviews', 'google-calendar-events'); ?>
									</a>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Output the translators screen.
	 *
	 * @since 3.0.0
	 */
	public function translators_screen()
	{
		?>
		<div id="simcal-welcome">
			<div
				class="wrap about-wrap translators-wrap simcal-max-w-[100%] simcal-font-poppins simcal-mr-[4%] simcal-ml-[4%] simcal-text-sm">
				<?php $this->nav_links(); ?>
				<?php $this->sc_intro_section(); ?>
				<div class="simcal-font-bold simcal-text-xl simcal-mt-[100px] simcal-text-sc_black-200">
					<span>
						<?php _e('Simple Calendar has been kindly translated into several other ', 'google-calendar-events'); ?>
					</span>
				</div>
				<div class="simcal-text-xl simcal-text-sc_black-200">
					<span>
						<?php _e('languages by contributors from all over the world.', 'google-calendar-events'); ?>
					</span>
				</div>
				<div class="simcal-mt-[42px]">
					<a href="https://translate.wordpress.org/projects/wp-plugins/google-calendar-events" target="_blank">
						<button type="button"
							class="simcal-items-center simcal-w-[257px] simcal-h-[40px] simcal-bg-sc_green-200 simcal-text-white simcal-text-base simcal-rounded-[7px] simcal-font-poppins">
							<?php _e('Click here to help translate', 'google-calendar-events'); ?>
						</button>
					</a>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Contributors List.
	 *
	 * @since  3.0.0
	 *
	 * @return string $contributor_list HTML formatted list of contributors.
	 */
	public function contributors()
	{
		$contributors = $this->get_contributors();

		if (empty($contributors)) {
			return '';
		}

		$contributor_list = '<div class="simcal-flex simcal-flex-wrap simcal-gap-[1%] max-xl:simcal-gap-[15%]">';

		foreach ($contributors as $contributor) {
			// Skip contributor bots
			$contributor_bots = ['gitter-badger'];
			if (in_array($contributor->login, $contributor_bots)) {
				continue;
			}

			$contributor_list .=
				'<div class="wp-person !simcal-w-[23%] max-xl:!simcal-w-[34%] max-3xl:!simcal-w-[28%] simcal-mt-[25px] simcal-rounded-[15px] simcal-pt-[10px] simcal-pl-[8px] simcal-font-medium hover:simcal-bg-sc_green-200">';
			$contributor_list .= sprintf(
				'<a href="%s" title="%s" target="_blank">%s</a>',
				esc_url('https://github.com/' . $contributor->login),
				esc_html(sprintf(__('View %s', 'google-calendar-events'), $contributor->login)),
				sprintf(
					'<img src="%s" width="50" height="50" class="gravatar" alt="%s" />',
					esc_url($contributor->avatar_url),
					esc_html($contributor->login)
				)
			);
			$contributor_list .= sprintf(
				'<a class="web !simcal-text-sm !simcal-leading-[3] !simcal-font-medium" href="%s" target="_blank">%s</a>',
				esc_url('https://github.com/' . $contributor->login),
				esc_html($contributor->login)
			);
			$contributor_list .= '</div>';
		}

		$contributor_list .= '</div>';

		return $contributor_list;
	}

	/**
	 * Retrieve list of contributors from GitHub.
	 *
	 * @since  3.0.0
	 *
	 * @return mixed
	 */
	public function get_contributors()
	{
		$contributors = get_transient('_simple-calendar_contributors');
		if (false !== $contributors) {
			return $contributors;
		}

		$response = wp_safe_remote_get('https://api.github.com/repos/Xtendify/Simple-Calendar/contributors');
		if (is_wp_error($response) || 200 != wp_remote_retrieve_response_code($response)) {
			return [];
		}

		$contributors = json_decode(wp_remote_retrieve_body($response));
		if (!is_array($contributors)) {
			return [];
		}

		set_transient('_simple-calendar_contributors', $contributors, HOUR_IN_SECONDS);

		return $contributors;
	}
}
