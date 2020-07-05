<?php
/*

Plugin Name: Google reCAPTCHA
Version: 0.2
Description: Include Google reCAPTCHA on login page and/or comment form

*/

/*
    Use:
        - register w/ Google at https://www.google.com/recaptcha/admin#list; get recaptcha keys
        - install plugin and activate
        - copy recaptcha keys to Settings -> reCAPTCHA; save settings
        - select whether to include reCAPTCHA on comment form and/or login page

    Info: https://developers.google.com/recaptcha/intro

    Modified from: https://www.oueta.com/wordpress/add-google-recaptcha-to-wordpress-comments-without-plugin/
        - made a plugin
        - moved placement of enqueing recaptcha script from single.php to function here
        - created settings page to allow input/modification of recaptcha keys
        - moved site and secret keys to database recaptcha_site_key and recaptcha_private key
        - added recaptcha for login page
        - added options for selecting whether to include recaptcha on comment form and login page
        - minor styling of recaptcha box
*/

defined( 'ABSPATH' ) ? : die();

/*
        Comment form reCAPTCHA functions
*/

function use_comment_recaptcha() {
    // using recaptcha on comment form?
    if( get_option('recaptcha_comment') == 1 ) {

        // is user logged in and are we hiding recaptcha for logged in users
        if( is_user_logged_in() && get_option('recaptcha_hide_comment') == 1 ) {
            return false;
        }

        return true;
    }
    
    return  false;
}

// enqueue reCAPTCHA script on single.php page
function enqueue_comment_google_recaptcha() {
    if( is_single() && use_comment_recaptcha() ) {
        wp_enqueue_script('google-recaptcha', 'https://www.google.com/recaptcha/api.js');
    }
}    
add_action('wp_enqueue_scripts', 'enqueue_comment_google_recaptcha');

// add Google recaptcha box before the comment submit button
function add_comment_google_recaptcha($submit_field) {
    if( use_comment_recaptcha() ) {
        $submit_field['submit_field'] = '<div class="g-recaptcha" style="position: absolute" data-sitekey=' . get_option('recaptcha_site_key') . ' data-size="compact" ></div>' . $submit_field['submit_field'];

        return $submit_field;
    }
}
add_filter('comment_form_defaults','add_comment_google_recaptcha');

// comment form verify recaptcha prior to post submit
function verify_comment_recaptcha() {
    if( use_comment_recaptcha() ) {
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

function use_login_recaptcha() {
    return get_option('recaptcha_login') == 1;
}

// enqueue reCAPTCHA script on wp-login.php page
function enqueue_login_google_recaptcha() {
    if( use_login_recaptcha() ) {
        wp_enqueue_script('google-recaptcha', 'https://www.google.com/recaptcha/api.js');
    }    
}
add_action('login_enqueue_scripts', 'enqueue_login_google_recaptcha');

// add Google recaptcha box before the "Log In" button
function add_login_google_recaptcha() {
    if( use_login_recaptcha() ) {
        $recaptcha = '<div class="g-recaptcha" style="height: 85px" data-sitekey=' . get_option('recaptcha_site_key') . ' data-size="compact" ></div>';
        echo $recaptcha;
    }
}
add_filter('login_form','add_login_google_recaptcha');

// login page verify recaptcha prior to login
function verify_login_recaptcha( $user, $username, $password ) {
    if( use_login_recaptcha() ) {
        if( $username != null ) {
            $recaptcha = null;
            if (isset($_POST['g-recaptcha-response'])) {
                $recaptcha = $_POST['g-recaptcha-response'];
            }

            if ( empty($recaptcha) || !is_valid_captcha($recaptcha) ) {
                $user = new WP_Error( 'authentication_failed', __( '<strong>ERROR</strong>: Invalid reCAPTCHA.' ) );
            }
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
                            'secret' => get_option('recaptcha_private_key'),
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

/*
        reCAPTCHA settings
*/

function create_recaptcha_settings_page() {
    // Add the menu item and page
    $page_title = 'Google reCAPTCHA Settings Page';
    $menu_title = 'reCAPTCHA';
    $capability = 'manage_options';
    $slug = 'google_recaptcha_fields';
    $callback = 'recaptcha_settings_page_content';
    $position = 100;

    add_submenu_page( 'options-general.php', $page_title, $menu_title, $capability, $slug, $callback, $position );

    add_action( 'admin_init', 'recaptcha_page_setup' );
}
add_action( 'admin_menu', 'create_recaptcha_settings_page' );

function recaptcha_page_setup() {
    add_settings_section( 'recaptcha_keys', 'Google reCAPTCHA Keys:', false, 'google_recaptcha_fields' );
    add_settings_field( 'recaptcha_site_key', 'Site Key', 'recaptcha_site_key_callback', 'google_recaptcha_fields', 'recaptcha_keys' );
    add_settings_field( 'recaptcha_private_key', 'Private Key', 'recaptcha_private_key_callback', 'google_recaptcha_fields', 'recaptcha_keys' );
    register_setting( 'google_recaptcha_fields', 'recaptcha_site_key' );
    register_setting( 'google_recaptcha_fields', 'recaptcha_private_key' );

    add_settings_section( 'recaptcha_option', 'Use Google reCAPTCHA on:', false, 'google_recaptcha_fields' );
    add_settings_field( 'recaptcha_comment', 'Comments form', 'recaptcha_comment_callback', 'google_recaptcha_fields', 'recaptcha_option' );
    add_settings_field( 'recaptcha_login', 'Login page', 'recaptcha_login_callback', 'google_recaptcha_fields', 'recaptcha_option' );
    register_setting( 'google_recaptcha_fields', 'recaptcha_comment' );
    register_setting( 'google_recaptcha_fields', 'recaptcha_login' );

    add_settings_section( 'recaptcha_hide', 'Hide Google reCAPTCHA if:', false, 'google_recaptcha_fields' );
    add_settings_field( 'recaptcha_hide_comment', 'User is logged in', 'recaptcha_hide_comment_callback', 'google_recaptcha_fields', 'recaptcha_hide' );
//    add_settings_field( 'recaptcha_hide_login', 'User Logged in', 'recaptcha_hide_login_callback', 'google_recaptcha_fields', 'recaptcha_hide' );
    register_setting( 'google_recaptcha_fields', 'recaptcha_hide_comment' );
//    register_setting( 'google_recaptcha_fields', 'recaptcha_login' );
}

function recaptcha_settings_page_content() { ?>
    <div class="wrap">
        <h2>Google reCAPTCHA Settings Page</h2>
        <form method="post" action="options.php">
            <?php
                settings_fields( 'google_recaptcha_fields' );
                do_settings_sections( 'google_recaptcha_fields' );
                submit_button();
            ?>
        </form>
    </div> <?php
}

function recaptcha_site_key_callback( $arguments ) {
    echo '<input name="recaptcha_site_key" id="recaptcha_site_key" type="text" value="' . get_option( 'recaptcha_site_key' ) . '" />';
}

function recaptcha_private_key_callback( $arguments ) {
    echo '<input name="recaptcha_private_key" id="recaptcha_private_key" type="text" value="' . get_option( 'recaptcha_private_key' ) . '" />';
}

function recaptcha_comment_callback( $arguments ) {
    echo '<input name="recaptcha_comment" id="recaptcha_comment" type="checkbox" value="1"' . checked(1, get_option( 'recaptcha_comment' ), false ) . '" />' . '<label for="recaptcha_comment"></label>';
}

function recaptcha_login_callback( $arguments ) {
    echo '<input name="recaptcha_login" id="recaptcha_login" type="checkbox" value="1"' . checked(1, get_option( 'recaptcha_login' ), false ) . '" />' . '<label for="recaptcha_login"></label>';
}

function recaptcha_hide_comment_callback( $arguments ) {
    echo '<input name="recaptcha_hide_comment" id="recaptcha_hide_comment" type="checkbox" value="1"' . checked(1, get_option( 'recaptcha_hide_comment' ), false ) . '" />' . '<label for="recaptcha_login"></label>';
}