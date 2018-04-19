<?php
/**
 * Plugin Name:     Ultimate MailChimp Plugin
 * Plugin URI:      atomicsmash.co.uk
 * Description:     Sync to MailChimp like a pro
 * Author:          atomicsmash.co.uk
 * Author URI:      atomicsmash.co.uk
 * Text Domain:     testing
 * Domain Path:     /languages
 * Version:         0.0.1
 *
 * @package         Testing
 */

if (!defined('ABSPATH')) exit; //Exit if accessed directly

require __DIR__ . '/vendor/autoload.php';


class UltimateMailChimpPlugin {

    function __construct() {

        if ( defined( 'WP_CLI' ) && WP_CLI ) {

            WP_CLI::add_command( 'ultimate-mailchimp sync-users', array( $this, 'sync_users' ) );
            // WP_CLI::add_command( 'ultimate-mailchimp get-webhook-url', array( $this, 'sync_users' ) );

        };

    }


    function sync_users( $args, $assoc_args ){


        $args = array(

        	// 'role'         => '',
        	// 'role__in'     => array(),
        	// 'role__not_in' => array(),
        	'meta_key'     => '',
        	'meta_value'   => '',
        	'meta_compare' => '',
        	'meta_query'   => array(),
        	'date_query'   => array(),
        	'include'      => array(),
        	'exclude'      => array(),
        	'orderby'      => 'login',
        	'order'        => 'ASC',
        	'offset'       => '',
        	'search'       => '',
        	'number'       => -1,
        	'count_total'  => false,
        	'fields'       => 'all',
        	'who'          => '',
        );

        $users = get_users( $args );

        //
        // echo "<pre>";
        // print_r($user);
        // echo "</pre>";


        foreach( $users as $user ){
            echo $user->data->user_email . "\n";
            $this->send_user_to_mailchimp( $user );
        }

        echo WP_CLI::success( "Hello!");


    }


    private function connect_to_mailchimp(){

        //ASTODO Add check to make sure constant is set
        $this->MailChimp = new \DrewM\MailChimp\MailChimp( ULTIMATE_MAILCHIMP_API_KEY );

    }


    private function send_user_to_mailchimp( $user = object ){

        $this->connect_to_mailchimp();


        if (  ) {


            // User has meta key so they are updating their email
            $subscriber_hash = $this->MailChimp->subscriberHash( $previous_email );

            // Update the merge fields with the new email
            $mailchimp_merge_fields['EMAIL'] = $userDetails->data->user_email;

            // Update the existing user, using PATCH
            $result = $this->MailChimp->patch("lists/".MAILCHIMP_LIST_ID."/members/$subscriber_hash", [
                'merge_fields' => $mailchimp_merge_fields,
                'status' => $user_status
            ]);

        } else {


            $subscriber_hash = $this->MailChimp->subscriberHash( $userDetails->data->user_email );

            // Use PUT to insert or update a record
            $result = $this->MailChimp->put("lists/".MAILCHIMP_LIST_ID."/members/$subscriber_hash", [
               'email_address' => $userDetails->data->user_email,
               'merge_fields' => $mailchimp_merge_fields,
               'status' => $user_status,
               'timestamp_opt' => $user->data->user_registered
            ]);

        }


    }

}

$ultimate_mailchimp_plugin = new UltimateMailChimpPlugin;
