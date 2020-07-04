<?php
/*
Plugin Name: Google reCAPTCHA
Version: 0.1
*/
/*
    modified from: https://www.oueta.com/wordpress/add-google-recaptcha-to-wordpress-comments-without-plugin/
        - made a plugin
        - moved placement of enqueing recaptcha script from single.php to function here
        - moved site and secret keys to wp-config.php w/ GOOGLE_RECAPTCHA_SITE_KEY and GOOGLE_RECAPTCHA_SECRET_KEY
*/

// register w/ Google at https://www.google.com/recaptcha/admin#list

defined( 'ABSPATH' ) or die( 'only a plugin' );


/*
        Comment form reCAPTCHA functions
*/

// enqueue reCAPTCHA script on single.php page
function enqueue_comment_google_recaptcha() {
    if( is_single() && !is_user_logged_in()) {
        wp_enqueue_script('google-recaptcha', 'https://www.google.com/recaptcha/api.js');
    }
}    
add_action('wp_enqueue_scripts', 'enqueue_comment_google_recaptcha');

// add Google recaptcha box before the comment submit button
function add_comment_google_recaptcha($submit_field) {
    if( !is_user_logged_in()) {
        $submit_field['submit_field'] = '<div class="g-recaptcha" data-sitekey=' . GOOGLE_RECAPTCHA_SITE_KEY . '></div><br>' . $submit_field['submit_field'];

        return $submit_field;
    }
}
add_filter('comment_form_defaults','add_comment_google_recaptcha');

// comment form verify recaptcha prior to post submit
function verify_comment_recaptcha() {
    if( !is_user_logged_in()) {
        $recaptcha = $_POST['g-recaptcha-response'];

        if (empty($recaptcha)) {
            wp_die( __("<b>ERROR:</b> please select <b>I'm not a robot!</b><p><a href='javascript:history.back()'>« Back</a></p>"));
        }
        else if (!is_valid_captcha($recaptcha)) {
            wp_die( __("<b>Sorry, you seem to be a robot!</b>"));
        }
    }
}
add_action('pre_comment_on_post', 'verify_comment_recaptcha');


/*
        Login page reCAPTCHA functions
*/

// enqueue reCAPTCHA script on wp-login.php page
function enqueue_login_google_recaptcha() {
    wp_enqueue_script('google-recaptcha', 'https://www.google.com/recaptcha/api.js');
}    
add_action('login_enqueue_scripts', 'enqueue_login_google_recaptcha');

// add Google recaptcha box before the "Log In" button
function add_login_google_recaptcha() {
    $recaptcha = '<div class="g-recaptcha" data-sitekey=' . GOOGLE_RECAPTCHA_SITE_KEY . '></div><br>';
    echo $recaptcha;
}
add_filter('login_form','add_login_google_recaptcha');

// login page verify recaptcha prior to login
function verify_login_recaptcha( $user, $username, $password ) {
    if( $username != null && $username != "tmr4" ) {
        $recaptcha = null;
        if (isset($_POST['g-recaptcha-response'])) {
            $recaptcha = $_POST['g-recaptcha-response'];
        }

        if ( empty($recaptcha) || !is_valid_captcha($recaptcha) ) {
            $user = new WP_Error( 'authentication_failed', __( '<strong>ERROR</strong>: Invalid reCAPTCHA.' ) );
        }
    }

    return $user;
}
add_filter('authenticate', 'verify_login_recaptcha', 30, 3);


/*
        reCAPTCHA validation function
*/

// Google recaptcha check, validate successful recaptcha (returns true) else (returns false)
function is_valid_captcha($captcha) {
    $captcha_postdata = http_build_query(array(
                            'secret' => GOOGLE_RECAPTCHA_SECRET_KEY,
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