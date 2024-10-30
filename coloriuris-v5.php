<?php
/*
Plugin Name: Coloriuris - Derechos de autor
Plugin URI: https://www.coloriuris.net/derechos-autor
Description: ColorIURIS, Derechos de autor. Registra tus artículos y crea automáticamente acuerdos de licencia.
Version: 1.0.6
Author: Coloriuris A.I.E.
Author URI: https://www.coloriuris.net
*/

if (!defined('COLORIURIS_V5_URL')) {
define('COLORIURIS_V5_URL', 'https://www.coloriuris.net/derechos-autor');
}
define('COLORIURIS_V5_LOGO', COLORIURIS_V5_URL . '/public/images/favicon.png');
define('COLORIURIS_V5_API_URL', COLORIURIS_V5_URL . '/usuarios/blog-apiv1');
define('COLORIURIS_V5_API_URL_TEST', COLORIURIS_V5_API_URL . '/test');
define('COLORIURIS_V5_API_URL_REGISTER', COLORIURIS_V5_API_URL . '/register-post');
if (!defined('COLORIURIS_V5_CODE_ACUERDO')) {
define('COLORIURIS_V5_CODE_ACUERDO', true);
}
if (!defined('COLORIURIS_V5_CODE_REGISTRO')) {
define('COLORIURIS_V5_CODE_REGISTRO', true);
}

function coloriuris_v5_test($api_key) {
	$response = wp_remote_get(COLORIURIS_V5_API_URL_TEST, array(
		'headers' => array(
			'x-coloriuris-v5-apikey' => $api_key
		)
	));
	if(! is_wp_error( $response ) && $response['response']['code'] == 200) {
		$res = json_decode($response['body'], true);
		return $res['id'];
	}
	return false;
}

function coloriuris_v5_register($api_key, $url, $holder_name, $holder_id, $author, $title, $description, $type) {
   	$code = null;
	$body = array(
		'url' => $url,
		'holderName' => $holder_name,
		'holderID' => $holder_id,
		'author' => $author,
		'title' => $title,
		'description' => $description,
		'type' => $type,
	);
	$response = wp_remote_post(COLORIURIS_V5_API_URL_REGISTER, array(
		'method' => 'POST',
		'timeout' => 45,
		'redirection' => 5,
		'httpversion' => '1.0',
		'blocking' => true,
		'headers' => array(
			'x-coloriuris-v5-apikey' => $api_key
		),
		'body' => $body,
		'cookies' => array()
	));
	if(! is_wp_error( $response ) && $response['response']['code'] == 200) {
		$res = json_decode($response['body'], true);
		return $res['uuid'];
	}
	return false;
}

function coloriuris_v5_init() {
	add_action('admin_menu', 'coloriuris_v5_admin_menu');	
}
add_action('init', 'coloriuris_v5_init');

function coloriuris_v5_admin_menu() {
	if (function_exists('add_options_page')) {
		add_options_page('ColorIURIS', '<img src="' . COLORIURIS_V5_LOGO . '" style="padding-right: 3px; position: relative; top: 2px;">ColorIURIS', 5, basename(__FILE__), 'coloriuris_v5_conf');
	}
}

function coloriuris_v5_user_update($user_id, $api_key) {
	add_user_meta($user_id, 'coloriuris_v5_apikey', $api_key, true) or 
	update_usermeta($user_id, 'coloriuris_v5_apikey', $api_key);
}
function coloriuris_v5_user_delete($user_id) {
	delete_user_meta($user_id, 'coloriuris_v5_apikey');
}

function coloriuris_wp_user_get($user_id) {
	return get_usermeta($user_id, 'coloriuris_v5_apikey');
}

function coloriuris_v5_post_update($post_id, $coloriuris_v5_id) {
	add_post_meta($post_id, 'coloriuris_v5_id', $coloriuris_v5_id);
}

function coloriuris_v5_post_get($post_id) {
	$coloriuris_v5_id = get_post_meta($post_id, 'coloriuris_v5_id', true);
	return ($coloriuris_v5_id != '') ? $coloriuris_v5_id : null;
}

function coloriuris_v5_publish_post($postID) {
	$post = get_post($postID);
	$coloriuris_v5_id = coloriuris_v5_post_get($postID);
	if ($coloriuris_v5_id != null) {
		return true;
	}
	$author_id = (int)$post->post_author;
	$api_key = coloriuris_wp_user_get($author_id);
	if ($api_key == null || $api_key == '') {
		return false;
	}
	//$url_post = $post->guid;
	$url_post = get_permalink($postID);
	$url_blog = get_option('siteurl');
	$wp_user = get_userdata($author_id);
	$holder_name = $author;
	$author = $wp_user->display_name;
	$holder_id = '';
	$title = $post->post_title;
	$description = $post->post_content;
	$type = 'individual';
		
	$coloriuris_v5_id = coloriuris_v5_register($api_key, $url_post, $holder_name, $holder_id, $author, $title, $description, $type);
	if ($coloriuris_v5_id != null) {
		coloriuris_v5_post_update($postID, $coloriuris_v5_id);
		return true;
	}
	return false;
}
add_action('publish_post', 'coloriuris_v5_publish_post');

function coloriuris_v5_post_generate_code($coloriuris_v5_id) {
	$html = '';
	if (COLORIURIS_V5_CODE_ACUERDO) {
		$acuerdo_link = 'https://www.coloriuris.net/derechos-autor/obra/' . $coloriuris_v5_id;
		$acuerdo_img = 'https://www.coloriuris.net/derechos-autor/obra-url/icono-acuerdo.png?url=' . esc_attr(get_permalink());
		$acuerdo_img_load = plugins_url('acuerdo.png', __FILE__);
		$html .= '<a href="' . $acuerdo_link . '">' .
			'<img class="coloriuris-v5" data-original="' . $acuerdo_img . '" src="' . $acuerdo_img_load . '" alt="Derechos de autor" style="width:24px;height:24px"/>' .
			'Acuerdo de licencia</a>';
	}
	if (COLORIURIS_V5_CODE_REGISTRO) {
		$registro_link = 'https://www.coloriuris.net/derechos-autor/obra/' . $coloriuris_v5_id . '/registro';
		$registro_img = 'https://www.coloriuris.net/derechos-autor/obra/' . $coloriuris_v5_id . '/icono-registro.png';
		$registro_img_load = plugins_url('registro.png', __FILE__);
		$html .= '<a href="' . $registro_link . '">' .
			'<img class="coloriuris-v5" data-original="' . $registro_img . '" src="' . $registro_img_load . '" alt="Derechos de autor" style="width:24px;height:24px"/>' .
			'Registro de la obra</a>';
	}
	return $html;
}

function coloriuris_v5_the_content($content) {
	global $post;
	$coloriuris_v5_id = coloriuris_v5_post_get($post->ID);
	if ($coloriuris_v5_id != null) {
		$content .= coloriuris_v5_post_generate_code($coloriuris_v5_id);
	}
	return $content;
}
add_filter('the_content', 'coloriuris_v5_the_content');





function coloriuris_v5_wp_enqueue_scripts() {
	wp_enqueue_script('jquery-lazyload', plugins_url('jquery.lazyload.min.js', __FILE__), array('jquery'));
	wp_enqueue_script('coloriuris-v5', plugins_url('coloriuris-v5.js', __FILE__), array('jquery-lazyload'));
}
add_action('wp_enqueue_scripts', 'coloriuris_v5_wp_enqueue_scripts');



require_once('coloriuris-v5-gui.inc.php');

?>
