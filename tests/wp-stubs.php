<?php

/**
 * Minimal WordPress function stubs loaded before test suite runs.
 * These exist so plugin files can be required without a full WP install.
 * Brain\Monkey overrides them per-test via Patchwork.
 */

if (!function_exists('add_action')) {
	function add_action() {}
}
if (!function_exists('remove_action')) {
	function remove_action() {}
}
if (!function_exists('add_filter')) {
	function add_filter() {}
}
if (!function_exists('remove_filter')) {
	function remove_filter() {}
}
if (!function_exists('do_action')) {
	function do_action() {}
}
if (!function_exists('apply_filters')) {
	function apply_filters($tag, $value)
	{
		return $value;
	}
}
if (!function_exists('get_option')) {
	function get_option($option, $default = false)
	{
		return $default;
	}
}
if (!function_exists('update_option')) {
	function update_option()
	{
		return false;
	}
}
if (!function_exists('get_bloginfo')) {
	function get_bloginfo($show = '', $filter = 'raw')
	{
		return '';
	}
}
if (!function_exists('sanitize_text_field')) {
	function sanitize_text_field($str)
	{
		return $str;
	}
}
if (!function_exists('esc_url')) {
	function esc_url($url)
	{
		return $url;
	}
}
if (!function_exists('esc_url_raw')) {
	function esc_url_raw($url)
	{
		return $url;
	}
}
if (!function_exists('esc_attr')) {
	function esc_attr($text)
	{
		return $text;
	}
}
if (!function_exists('esc_html')) {
	function esc_html($text)
	{
		return $text;
	}
}
if (!function_exists('wp_kses_post')) {
	function wp_kses_post($data)
	{
		return $data;
	}
}
if (!function_exists('absint')) {
	function absint($maybeint)
	{
		return abs(intval($maybeint));
	}
}
if (!function_exists('sanitize_title')) {
	function sanitize_title($title)
	{
		return strtolower(str_replace(' ', '-', $title));
	}
}
if (!function_exists('add_query_arg')) {
	function add_query_arg($args, $url = '')
	{
		$sep = strpos($url, '?') === false ? '?' : '&';
		return $url . $sep . http_build_query($args);
	}
}
if (!function_exists('__')) {
	function __($text, $domain = 'default')
	{
		return $text;
	}
}
