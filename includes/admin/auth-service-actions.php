<?php
/**
 * Oauth Ajax
 *
 * @package SimpleCalendar\Admin
 */
namespace SimpleCalendar\Admin;

if (!defined('ABSPATH')) {
	exit();
}




/**
 * Oauth Ajax.
 *
 * @since 3.0.0
 */
class Oauth_Ajax
{

	/*
	*Auth site URL
	*/
	public static  $url = SIMPLE_CALENDAR_AUTH_DOMAIN . 'wp-json/api-test/v1/';

	/**
	 * My site URL
	 */
	public static  $my_site_url = '';

	/**
	 * Set up ajax hooks.
	 *
	 * @since 3.0.0
	 */
	public function __construct()
	{
		// Set an option if the user rated the plugin.
		add_action('wp_ajax_oauth_deauthenticate_site', [$this, 'oauth_deauthenticate_site']);
		//add_action('wp_ajax_nopriv_oauth_deauthenticate_site', [$this, 'oauth_deauthenticate_site']);
		// Do toke check here.
		$post_type = get_post_type();
		if($post_type = 'calendar'){
			add_action('admin_init', [$this, 'oauth_check_iftoken_expired']);
			add_action('admin_init', [$this, 'auth_get_calendarlist']);
		}

		self::$my_site_url = site_url();
	}

	/**
	 * DeAuthenticate.
	 *
	 */
	public function oauth_deauthenticate_site()
	{
		$nonce = isset($_POST['nonce']) ? esc_attr($_POST['nonce']) : '';
		if (!wp_verify_nonce($nonce, 'oauth_action_deauthentication') && !current_user_can('edit_posts')) {
			return;
		}
		$send_data = array(
			'site_url' => self::$my_site_url,
		);
		$request = wp_remote_post(self::$url.'de_authenticate_site', array(
			'method' => 'POST',
			'body' => $send_data,
			'cookies' => array()
		   ));

    if ( is_wp_error( $request ) || wp_remote_retrieve_response_code( $request ) != 200 ) {
        error_log( print_r( $request, true ) );
    }

    $response = wp_remote_retrieve_body( $request );

		$error_msg = array();
	if($response  == true || $response >= 1){
		delete_option('auth_service_status');
		$message = __('DeAuthenticate Successfully.', 'google-calendar-events');
		$send_msg = array('message' => $message);
		wp_send_json_success($send_msg);
	   }else{
		if(isset($message['message']) && !empty($message['message'])){
			$message = $message['message'];
		}else{
			$message = __('DeAuthentication Faild.', 'google-calendar-events');
		}

		$error_msg = array('message' => $message);
		wp_send_json_error($error_msg);
	   }
		die();

    }

	/*
	* Check if token expire
	*/
	public function oauth_check_iftoken_expired(){
		$send_data = array(
			'site_url' => self::$my_site_url,
		);
		$request = wp_remote_post(self::$url.'check_iftoken_expired', array(
			'method' => 'POST',
			'body' => $send_data,
			'cookies' => array()
		   ));

		$response = wp_remote_retrieve_body( $request );

		if($response  == true || $response >= 1){

			return 'valid';

		}else{
			delete_option('auth_service_status');
			return 'invalid';
		}

	}

	/*
	* Get calendar list
	*/
	public function auth_get_calendarlist(){
		$send_data = array(
			'site_url' => self::$my_site_url,
			'auth_code' => '',
		);
		$request = wp_remote_post(self::$url.'auth_get_calendarlist', array(
			'method' => 'POST',
			'body' => $send_data,
			'cookies' => array()
		   ));
		
		$response = wp_remote_retrieve_body( $request );
		
		$response_decoded = json_decode($response, true);
			
		return $response_decoded;
		
	}
}//class End

new Oauth_Ajax();
