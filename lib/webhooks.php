<?php

//placeholder for future webhook work
// WP_CLI::add_command( 'ultimate-mailchimp generate-webhook-url', array( $ultimate_mailchimp_cli, 'generate_webhook_url' ) );
public function webhook() {

    //ASTODO need to check if there is a key set
    //ASTODO need to complete the webhook functionality
    return "You posted to the webhook!";

}

public function generate_webhook_url() {

    $webhook_key = get_option( 'webhook_url_key' );

    if( $webhook_key == "" ){
        WP_CLI::confirm( "No webhook key is currently saved. Would you like to generate one?" );
    }else{
        WP_CLI::line( "Your current webhook url is: " . get_bloginfo('url') . "/wp-json/ultimate-mailchimp/v1/?key=" . $webhook_key );

        WP_CLI::confirm( "Would you like to regenerate it?" );
    }

    // The key is only regenerated is a 'Y' is supplied to a previous question
    $new_key = uniqid();

    // Update the option, but don't autoload it
    update_option( 'webhook_url_key', $new_key, 0 );

    //ASTODO get the webhook url into a global var
    WP_CLI::line( "Your NEW webhook url is: " . WP_CLI::colorize( "%G" . get_bloginfo('url') . "/wp-json/ultimate-mailchimp/v1/?key=" . $new_key . "%n" ));

}

// Setup webhook REST API ednpoint
// add_action( 'rest_api_init', function () {
//     register_rest_route( 'ultimate-mailchimp/v1', '/webhook', array(
//         'methods' => 'POST',
//         'callback' => array( $this, 'webhook' )
//     ));
// });
