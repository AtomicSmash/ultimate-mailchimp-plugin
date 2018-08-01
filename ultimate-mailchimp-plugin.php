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


// If autoload exists... autoload it baby... (this is for plugin developemnt and sites not using composer to pull plugins)
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
    require __DIR__ . '/vendor/autoload.php';
}

use \DrewM\MailChimp\MailChimp;
use \DrewM\MailChimp\Batch;
use \Monolog\Logger;
use \Monolog\Handler\StreamHandler;

//ASTODO There needs to be a plugin version number saved to the database when activated, this will be useful for future plugin updates

class UltimateMailChimpPlugin {

    function __construct() {

        // Setup CLI commands
        if ( defined( 'WP_CLI' ) && WP_CLI ) {
            if ( defined( 'ULTIMATE_MAILCHIMP_API_KEY' ) && defined( 'ULTIMATE_MAILCHIMP_LIST_ID' ) ) {
                // WP_CLI::add_command( 'ultimate-mailchimp sync-users', array( $this, 'sync_users' ) );
                // WP_CLI::add_command( 'ultimate-mailchimp show-batches', array( $this, 'get_batches' ) );
                // WP_CLI::add_command( 'ultimate-mailchimp generate-webhook-url', array( $this, 'generate_webhook_url' ) );
            }else{
                WP_CLI::add_command( 'ultimate-mailchimp please-setup-plugin', array( $this, 'setup_warning' ) );
            }
        }

        // Add custom fields to user profiles
        // add_action( 'show_user_profile', array( $this, 'add_user_custom_fields' ) );
        // add_action( 'edit_user_profile', array( $this, 'add_user_custom_fields' ) );

        // Save new user custom fields
        // add_action( 'personal_options_update', array( $this, 'save_user_custom_fields' ) );
        // add_action( 'edit_user_profile_update', array( $this, 'save_user_custom_fields' ) );

        if ( defined( 'ULTIMATE_MAILCHIMP_API_KEY' ) && defined( 'ULTIMATE_MAILCHIMP_LIST_ID' ) ) {

            // add_action( 'user_register', array( $this, 'new_user_created' ), 10, 1 );

            //ASTODO there needs to be a check to make sure WooCommerce is available
            add_action( 'woocommerce_checkout_after_terms_and_conditions', array( $this, 'add_woocommerce_checkout_custom_fields' ) );
            add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'update_user_after_order' ), 10, 2 );

        }

    }

    /**
     * Show a warning message for the fact the constants are not setup.
     *
     * @return void
     */
    public function setup_warning() {

        WP_CLI::line( "Config constants missing 🙁. Visit https://github.com/AtomicSmash/ultimate-mailchimp-plugin for a setup guide" );

    }

    public function new_user_created( $user_id ) {

        // Sync the new user
        $this->update_single_user( $user_id );

    }

    /**
     * Add the new MailChimp options to the user edit form in the WordPress backend.
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
                        <input type="checkbox" name="ultimate_mc_signup" id="ultimate_mc_signup"
                            <?php if( get_the_author_meta( 'ultimate_mc_signup', $user->ID ) == true ){ echo "checked "; } ?> >
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

        //ASTODO add number overriding

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

    private function get_merge_fields( $user_id ){

        $merge_fields = array();

        if( isset( $_POST['billing_first_name'] ) ){
            $merge_fields['FNAME'] = sanitize_text_field( $_POST['billing_first_name'] );
        }else{
            $merge_fields['FNAME'] = "";
        }

        if( isset( $_POST['billing_last_name'] ) ){
            $merge_fields['LNAME'] = sanitize_text_field( $_POST['billing_last_name'] );
        }else{
            $merge_fields['LNAME'] = "";
        }


        // See if there are
        // if( count( $existing_merge_fields ) > 0 ){
        //
        // }else{
            // $first_name = get_user_meta( $user_id, 'first_name', true );
            // $last_name = get_user_meta( $user_id, 'last_name', true );
            //
            // $merge_fields = array(
            //     'FNAME' => $first_name,
            //     'LNAME' => $last_name
            // );
        // }


        $merge_fields = apply_filters( 'ul_mc_custom_merge_fields', $merge_fields, $user_id );

        return $merge_fields;

    }

    private function send_batch_to_mailchimp( $users = array() ){

        $this->connect_to_mailchimp();

        $batch_process = $this->MailChimp->new_batch();

        foreach( $users as $key => $user ){

            // Generate an MD5 of the users email address
            $subscriber_hash = $this->MailChimp->subscriberHash( $user->data->user_email );

            $merge_fields = $this->get_merge_fields( $user );

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

    }


    public function get_batches( $cli_args = array() ){

        $this->connect_to_mailchimp();

        if( ! isset( $cli_args[0] ) ) {

            // ASTODO reduce number of batches displayed, maybe just show the last ten
            $result = $this->MailChimp->get( "batches?count=100" );

            if( $result != null ){
                // Sort batch results by internal timestamp
                usort( $result['batches'], function($a, $b){
                    return strtotime( $b['submitted_at'] ) - strtotime( $a['submitted_at'] );
                });

                // Loop through all the returned batches and display details
                if( count( $result['batches'] ) > 0 ){
                    foreach( $result['batches'] as $batch ){
                        WP_CLI::line( $batch['id'] . " | " . $batch['status'] . " | " . $this->time_ago( $batch['submitted_at'] ) );
                    }
                }
            }else{

                WP_CLI::line( "No batches found" );

            }


        }else{

            if( strlen( $cli_args[0] ) != 10 ) {
                WP_CLI::error( "Supplied batch ref doesn't look right 🤔" );
            }

            $result = $this->MailChimp->get( "batches/" . $cli_args[0] );

            WP_CLI::line( "Status: " . $result[ 'status' ] );
            WP_CLI::line( "Total operations: " . $result[ 'total_operations' ] );
            WP_CLI::line( "Finished operations: " . $result[ 'finished_operations' ] );
            WP_CLI::line( "Errored operations: " . $result[ 'errored_operations' ] );
            WP_CLI::line( "Submitted at: " . $result[ 'submitted_at' ] );
            WP_CLI::line( "Response download: " . $result[ 'response_body_url' ] );

        }

    }


    private function update_single_user( $order_id = 0, $user_status = 'subscribed' ){

        $order = wc_get_order( $order_id );

        // Get the custumer ID
        $user_id = get_current_user_id();

        $date = new DateTime();

        $this->connect_to_mailchimp();

        // $user = get_userdata( $user_id );

        if( isset( $_POST['billing_email'] ) ){
            $billing_email = sanitize_email( $_POST['billing_email'] );
        }else{
            return false;
        }

        $merge_fields = $this->get_merge_fields( $user_id );

        //ASTODO This should be in it's own method $this->log();
        if ( defined('ULTIMATE_MAILCHIMP_LOGGING') ) {
            // Create the logger
            $logger = new Logger( 'ultimate_mailchimp' );
            // ASTODO hash the filename by date
            $uploads_directory = wp_upload_dir();
            $logger->pushHandler(new StreamHandler( $uploads_directory['basedir'] .'/ultimate-mailchimp.log', Logger::DEBUG));
            $logger->info( '-------- Order placed --------' );
            $logger->info( 'Order ID: ' . $order_id );
            $logger->info( 'Merge fields: ', $merge_fields );
            $logger->info( 'Timestamp: '. $date->getTimestamp() );

        }

        $subscriber_hash = $this->MailChimp->subscriberHash( $billing_email );



        $marketing_preferences = array(
            [
                'marketing_permission_id' => 'e4a360d5ae',
                'text' => 'Email',
                'enabled' => true
            ]
        );


        // Use PUT to insert or update a record
        $result = $this->MailChimp->put( "lists/" . ULTIMATE_MAILCHIMP_LIST_ID . "/members/$subscriber_hash", [
           'email_address' => $billing_email,
           'merge_fields' => $merge_fields,
           'marketing_permissions' => $marketing_preferences,
           'status' => $user_status,
           'timestamp_opt' => (string)$date->getTimestamp()
        ]);


        //ASTODO get this into a $this->log file
        if ( defined('ULTIMATE_MAILCHIMP_LOGGING') ) {

            $logger->info( 'Updating this Email address: ' . $billing_email );
            $logger->info( 'Updating to these Merge fields', $merge_fields );
            $logger->info( 'Updating to this status: ' . $user_status );

            if( $this->MailChimp->success() ) {
                $logger->info( 'Mailchimp sync SUCCESS' );
                $logger->info( 'Mailchimp response - status: '. $result['status'] );
                $logger->info( 'Mailchimp response - merge fields', $result['merge_fields'] );
            } else {
                $logger->info( 'Mailchimp error: ' . $this->MailChimp->getLastError() );
                $logger->info( 'Mailchimp response: ' , $this->MailChimp->getLastResponse() );
            }
        }


        if ( defined('ULTIMATE_MAILCHIMP_DEBUG') ) {
            die( 'YOU ARE IN DEBUG MODE! Mailchimp has been updated' );
        };

    }

    /**
     *
     * WooCommerce integration
     *
     */


    public function add_woocommerce_checkout_custom_fields( $checkout ) {

        //ASTODO add logic to detect if the user is current signed up to the newsletter

        $newsletter_title = apply_filters( 'ul_mc_checkout_title', 'Marketing Permissions' );

        $checkbox_label = apply_filters( 'ul_mc_checkout_checkbox_label', 'Sign me up to the MailChimp newsletter' );

        $paragraph_one = apply_filters( 'ul_mc_checkout_paragraph_one', 'We use MailChimp as our marketing automation platform. By clicking below to submit this form, you acknowledge that the information you provide will be transferred to MailChimp for processing in accordance with their Privacy Policy and Terms. We will use the information you provide on this form to be in touch with you and to provide updates and marketing. Please let us know all the ways you would like to hear from us:' );

        $paragraph_two = apply_filters( 'ul_mc_checkout_paragraph_two', 'You can change your mind at any time by clicking the unsubscribe link in the footer of any email you receive from us, or by contacting us at EMAIL. We will treat your information with respect. For more information about our privacy practices please visit our website. By clicking below, you agree that we may process your information in accordance with these terms.' );

        echo '<div id="ultimate_mc_wc_signup"><h2>' . __( $newsletter_title ) . '</h2>';

            echo "<p>$paragraph_one</p>";

            woocommerce_form_field( 'ultimate_mc_wc_checkbox', array(
                'type'          => 'checkbox',
                'class'         => array( 'input-checkbox' ),
                'label'         => __( $checkbox_label ),
                'required'  => false,
            ), 0);

            echo "<p>$paragraph_two</p>";

        echo '</div>';

    }


    function update_user_after_order( $order_id, $data ) {
        // die($order_id);





        //ASTODO think about guest checkout :/

        // $user = wp_get_current_user();
        // $user_id = $user->ID;

        // if ( $user_id != 0 ) {
        //     if ( ! empty( $_POST['ultimate_mc_wc_checkbox'] ) ) {
        //         update_user_meta( $user_id, 'ultimate_mc_signup', true );
        //     }else{
        //         update_user_meta( $user_id, 'ultimate_mc_signup', false );
        //     }
        // };
        // <pre>Array
        // (
        //     [terms] => 1
        //     [createaccount] => 0
        //     [payment_method] => cheque
        //     [shipping_method] =>
        //     [ship_to_different_address] =>
        //     [billing_first_name] => David
        //     [billing_last_name] => Darke
        //     [billing_company] => Atomic Smash
        //     [billing_email] => david@atomicsmash.co.uk
        //     [billing_phone] => 2321312
        //     [billing_country] => GB
        //     [billing_address_1] => Flat 4,
        //     [billing_address_2] => 4 Saville Place,
        //     [billing_city] => Bristol
        //     [billing_state] => Avon
        //     [billing_postcode] => BS8 4EJ
        //     [order_comments] =>
        // )
        // </pre>


        if ( ! empty( $_POST['ultimate_mc_wc_checkbox'] ) ) {

            if ( defined('ULTIMATE_MAILCHIMP_DOUBLE_OPTIN') && ULTIMATE_MAILCHIMP_DOUBLE_OPTIN == false ) {
                $user_status = 'subscribed';
            }else{
                $user_status = 'pending';
            }

            $this->update_single_user( $order_id, $user_status ); // options: subscribed - unsubscribed - cleaned - pending
        }else{
            // $status = 'unsubscribed' // options: subscribed - unsubscribed - cleaned - pending
        }



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
