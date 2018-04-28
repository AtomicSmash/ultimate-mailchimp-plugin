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

use \DrewM\MailChimp\MailChimp;
use \DrewM\MailChimp\Batch;

//ASTODO there needs to be a check to make sure this fiel exists
require __DIR__ . '/vendor/autoload.php';

//ASTODO Need to add logging!
// use Monolog\Logger;
// use Monolog\Handler\StreamHandler;
//
// // create a log channel

//ASTODO There needs to be a plugin version number saved to the database when activated, this will be useful for future plugin updates

class UltimateMailChimpPlugin {

    function __construct() {

        // Setup CLI commands
        if ( defined( 'WP_CLI' ) && WP_CLI ) {
            if ( defined( 'ULTIMATE_MAILCHIMP_API_KEY' ) && defined( 'ULTIMATE_MAILCHIMP_LIST_ID' ) ) {
                WP_CLI::add_command( 'ultimate-mailchimp sync-users', array( $this, 'sync_users' ) );
                WP_CLI::add_command( 'ultimate-mailchimp show-batches', array( $this, 'get_batches' ) );
                WP_CLI::add_command( 'ultimate-mailchimp generate-webhook-url', array( $this, 'generate_webhook_url' ) );
            }else{
                WP_CLI::add_command( 'ultimate-mailchimp setup', array( $this, 'setup' ) );
            }
        }

        // Add custom fields to user profiles
        add_action( 'show_user_profile', array( $this, 'add_user_custom_fields' ) );
        add_action( 'edit_user_profile', array( $this, 'add_user_custom_fields' ) );

        // Save new user custom fields
        add_action( 'personal_options_update', array( $this, 'save_user_custom_fields' ) );
        add_action( 'edit_user_profile_update', array( $this, 'save_user_custom_fields' ) );

        //ASTODO there needs to be a check to make sure WooCommerce is available
        // Add mailchimp newsletter to checkout
        add_action( 'woocommerce_after_order_notes', array( $this, 'add_woocommerce_checkout_custom_fields' ) );
        add_action( 'woocommerce_checkout_update_user_meta', array( $this, 'save_woocommerce_checkout_user_fields' ) );


        // Setup webhook REST API ednpoint
        add_action( 'rest_api_init', function () {
            register_rest_route( 'ultimate-mailchimp/v1', '/webhook', array(
                'methods' => 'POST',
                'callback' => array( $this, 'webhook' )
            ));
        });

    }

    /**
     * Show a warning message for the fact the constants are not setup.
     *
     * @return void
     */
    public function setup() {

        WP_CLI::line( "Config constants missing 🙁. Visit https://github.com/AtomicSmash/ultimate-mailchimp-plugin for a setup guide" );

    }

    /**
     * Add the new MailChimp options to the user edit form.
     *
     * @param object $user_id current user object available from the edit page.
     *
     * @return void nothing returned, just echoed HTML.
     */
    public function add_user_custom_fields( $user ) {
        ?>
        <h3><?php _e("Mailchimp syncing", "blank"); ?></h3>
        <table class="form-table">
            <tr>
                <th><label for="address"><?php _e("Newsletter confirmation"); ?></label></th>
                <td><fieldset>
                    <label for="ultimate_mc_signup">
                        <input type="checkbox" name="ultimate_mc_signup" id="ultimate_mc_signup" <?php
                            if( get_the_author_meta( 'ultimate_mc_signup', $user->ID ) == true ){ echo "checked "; }
                        ?>>
                        <?php _e("If checked, the user has confirmed they would like to be added to your MailChimp list."); ?>
                    </label>
                    </fieldset>
                </td>
            </tr>
        </table>
        <?php

        //ASTODO add a user sync button here
    }


    /**
     * Save the 'Newsletter confirmation' meta field against the user
     *
     * @param object $user_id current user object available from the edit page
     *
     * @return void
     */
    public function save_user_custom_fields( $user_id ) {

        if ( !current_user_can( 'edit_user', $user_id ) ) {
            return false;
        }

        if( $_POST['ultimate_mc_signup'] == 'on' ){
            update_user_meta( $user_id, 'ultimate_mc_signup', true );
        }else{
            update_user_meta( $user_id, 'ultimate_mc_signup', false );
        }

    }


    public function sync_users( $args, $assoc_args ) {

        $args = array(

        	// 'role'         => '',
        	// 'role__in'     => array(),
        	// 'role__not_in' => array(),
        	// 'meta_key'     => '',
        	// 'meta_value'   => '',
        	// 'meta_compare' => '',
        	// 'meta_query'   => array(),
        	// 'date_query'   => array(),
        	// 'include'      => array(),
        	// 'exclude'      => array(),
        	// 'orderby'      => 'login',
        	// 'order'        => 'ASC',
        	// 'offset'       => '',
        	// 'search'       => '',
        	'number'       => -1,
        	// 'count_total'  => false,
        	// 'fields'       => 'all',
        	// 'who'          => '',
        );

        //ASTODO add a filter to change the user args

        $users = get_users( $args );

        $this->send_batch_to_mailchimp( $users );

    }

    private function connect_to_mailchimp(){
        if ( defined( 'ULTIMATE_MAILCHIMP_API_KEY' ) && defined( 'ULTIMATE_MAILCHIMP_LIST_ID' ) ) {
            $this->MailChimp = new \DrewM\MailChimp\MailChimp( ULTIMATE_MAILCHIMP_API_KEY );
        }else{
            WP_CLI::error( "Constants are not defined" );
        }
    }

    private function is_user_on_mailchimp_list( $user_email = "" ){

        $subscriber_hash = $this->MailChimp->subscriberHash( $user_email );

        $result = $this->MailChimp->get( "lists/" . ULTIMATE_MAILCHIMP_LIST_ID . "/members/" . $subscriber_hash );

        if($result['status'] == '404'){
            return false;
        }else{
            return true;
        }

    }

    private function should_user_be_synced( $user_email = "" ){

        //ASTODO Work on a conditional here to per user checking

    }

    private function send_batch_to_mailchimp( $users = array() ){

        $this->connect_to_mailchimp();

        $batch_process = $this->MailChimp->new_batch();

        foreach( $users as $key => $user ){

            // Generate an MD5 of the users email address
            $subscriber_hash = $this->MailChimp->subscriberHash( $user->data->user_email );

            $first_name = get_user_meta( $user->ID, 'first_name', true );
            $last_name = get_user_meta( $user->ID, 'last_name', true );

            $merge_fields = array(
                'FNAME' => $first_name,
                'LNAME' => $last_name
            );

            $merge_fields = apply_filters( 'ul_mc_custom_merge_fields', $merge_fields, $user );


            // Use PUT to insert or update a record, put requires a hashed email address and
            // a 'status_if_new' property for members who are new to the list
            $batch_process->put( "op" . $key , "lists/" . ULTIMATE_MAILCHIMP_LIST_ID . "/members/" . $subscriber_hash , [
                'email_address' => $user->data->user_email,
                'status' => 'subscribed', // subscribed - unsubscribed - cleaned - pending
                'status_if_new' => "subscribed", // subscribed - unsubscribed - cleaned - pending
                'merge_fields' => $merge_fields
            ] );

        }


        $result = $batch_process->execute();

        echo WP_CLI::success( "Batch started | ID: " . $result['id'] );

        // sleep(10);
        //
        // $result = $batch_process->check_status();
        //
        // echo "<pre>";
        // print_r($result);
        // echo "</pre>";




        //return true;


        // if ( $user_on_list ) {

            // User has meta key so they are updating their email
            // $subscriber_hash = $this->MailChimp->subscriberHash( $previous_email );
            //
            // // Update the merge fields with the new email
            // $mailchimp_merge_fields['EMAIL'] = $userDetails->data->user_email;
            //
            // // Update the existing user, using PATCH
            // $result = $this->MailChimp->patch( "lists/" . ULTIMATE_MAILCHIMP_LIST_ID . "/members/$subscriber_hash", [
            //     'merge_fields' => $mailchimp_merge_fields,
            //     'status' => $user_status
            // ]);

        // } else {

            // $subscriber_hash = $this->MailChimp->subscriberHash( $userDetails->data->user_email );
            //
            // // Use PUT to insert or update a record
            // $result = $this->MailChimp->put( "lists/" . ULTIMATE_MAILCHIMP_LIST_ID . "/members/$subscriber_hash", [
            //    'email_address' => $userDetails->data->user_email,
            //    'merge_fields' => $mailchimp_merge_fields,
            //    'status' => $user_status,
            //    'timestamp_opt' => $user->data->user_registered
            // ]);

        // }


        // MailChimp->success()) {
        // 	print_r($result);
        // } else {
        // 	echo $MailChimp->getLastError();
        // }


    }


    public function get_batches( $users = array() ){

        $this->connect_to_mailchimp();

        $result = $this->MailChimp->get( "batches?count=100" );


        // Sort batch results by internal timestamp
        usort( $result['batches'], function($a, $b){
            return strtotime( $b['submitted_at'] ) - strtotime( $a['submitted_at'] );
        });


        if( count( $result['batches'] ) > 0 ){
            foreach( $result['batches'] as $batch ){

                //ASTODO convert to proper cli line output
                echo $batch['id'] . " | ";
                echo $batch['status'] . " | ";
                echo $this->time_ago( $batch['submitted_at'] );


                echo "\n";

                // [id] => 02457dc1c8
                // [status] => finished
                // [total_operations] => 1
                // [finished_operations] => 1
                // [errored_operations] => 1
                // [submitted_at] => 2018-04-19T19:33:01+00:00
                // [completed_at] => 20
                // [response_body_url] =>

            }
        }
    }




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

    /**
     *
     * WooCommerce integration
     *
     */


    public function add_woocommerce_checkout_custom_fields( $checkout ) {

        //ASTODO add logic to detect if the user is current signed up to the newsletter

        // Apply filters for modifying the signup box
        $newsletter_title = apply_filters( 'ul_mc_checkout_title', 'Newsletter Signup' );
        $checkbox_label = apply_filters( 'ul_mc_checkout_checkbox_label', 'Sign me up to the newsletter' );

        echo '<div id="ultimate_mc_wc_signup"><h2>' . __( $newsletter_title ) . '</h2>';

            woocommerce_form_field( 'ultimate_mc_wc_checkbox', array(
                'type'          => 'checkbox',
                'class'         => array( 'input-checkbox' ),
                'label'         => __( $checkbox_label ),
                'required'  => false,
            ), 0);

        echo '</div>';

    }


    function save_woocommerce_checkout_user_fields( $order_id ) {

        //ASTODO think about guest checkout :/

        $user = wp_get_current_user();
        $user_id = $user->ID;

        if ( $user_id != 0 ) {
            if ( ! empty( $_POST['ultimate_mc_wc_checkbox'] ) ) {
                update_user_meta( $user_id, 'ultimate_mc_signup', true );
            }else{
                update_user_meta( $user_id, 'ultimate_mc_signup', false );
            }
        };

    }


    //ASTODO migrate this to human_time_diff https://codex.wordpress.org/Function_Reference/human_time_diff
    private function time_ago( $datetime, $full = false ){

        $now = new DateTime;
        $ago = new DateTime($datetime);
        $diff = $now->diff($ago);

        $diff->w = floor($diff->d / 7);
        $diff->d -= $diff->w * 7;

        $string = array(
            'y' => 'year',
            'm' => 'month',
            'w' => 'week',
            'd' => 'day',
            'h' => 'hour',
            'i' => 'minute',
            's' => 'second',
        );
        foreach ($string as $k => &$v) {
            if ($diff->$k) {
                $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
            } else {
                unset($string[$k]);
            }
        }

        if (!$full) $string = array_slice($string, 0, 1);
        return $string ? implode(', ', $string) . ' ago' : 'just now';

    }

}

$ultimate_mailchimp_plugin = new UltimateMailChimpPlugin;
