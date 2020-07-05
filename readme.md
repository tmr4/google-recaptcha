Plugin Name: Google reCAPTCHA

Version: 0.2

Description: Include Google reCAPTCHA on login page and/or comment form

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

