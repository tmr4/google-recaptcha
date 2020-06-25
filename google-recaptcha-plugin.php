<?php
/*
Plugin Name: Google reCAPTCHA
Version: 0.1
*/

defined( 'ABSPATH' ) or die( 'only a plugin' );

// reCAPTCHA
/**
 * Google recaptcha add before the submit button
 * from: https://www.oueta.com/wordpress/add-google-recaptcha-to-wordpress-comments-without-plugin/
 * register w/ Google at https://www.google.com/recaptcha/admin#list
 * add site and secret keys below at "your_site_key" and 'secret_key'
 */
function add_google_recaptcha($submit_field) {
    $submit_field['submit_field'] = '<div class="g-recaptcha" data-sitekey=' . get_my_option("google_recaptcha_site_key") . '></div><br>' . $submit_field['submit_field'];
    return $submit_field;
}
add_filter('comment_form_defaults','add_google_recaptcha');

/**
 * Google recaptcha check, validate and catch the spammer
 */
function is_valid_captcha($captcha) {
$captcha_postdata = http_build_query(array(
                            'secret' => get_my_option('google_recaptcha_secret_key'),
                            'response' => $captcha,
                            'remoteip' => $_SERVER['REMOTE_ADDR']));
$captcha_opts = array('http' => array(
                      'method'  => 'POST',
                      'header'  => 'Content-type: application/x-www-form-urlencoded',
                      'content' => $captcha_postdata));
$captcha_context  = stream_context_create($captcha_opts);
$captcha_response = json_decode(file_get_contents("https://www.google.com/recaptcha/api/siteverify" , false , $captcha_context), true);
if ($captcha_response['success'])
    return true;
else
    return false;
}
 
function verify_google_recaptcha() {
$recaptcha = $_POST['g-recaptcha-response'];
if (empty($recaptcha))
    wp_die( __("<b>ERROR:</b> please select <b>I'm not a robot!</b><p><a href='javascript:history.back()'>« Back</a></p>"));
else if (!is_valid_captcha($recaptcha))
    wp_die( __("<b>Sorry, you seem to be a robot!</b>"));
}
add_action('pre_comment_on_post', 'verify_google_recaptcha');

function get_my_option($option = null)
{
    if ( !empty($option) ) 
    {
        return get_option($option);
    }
}
add_action('init', 'get_my_option');
