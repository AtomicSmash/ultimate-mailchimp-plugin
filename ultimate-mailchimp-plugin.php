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


require __DIR__ . '/vendor/autoload.php';

//ASTODO Need to add logging!
// use Monolog\Logger;
// use Monolog\Handler\StreamHandler;
//
// // create a log channel
// $log = new Logger('name');
// $log->pushHandler(new StreamHandler('path/to/your.log', Logger::WARNING));
//
// // add records to the log
// $log->warning('Foo');
// $log->error('Bar');

class UltimateMailChimpPlugin {

    function __construct() {

        if ( defined( 'WP_CLI' ) && WP_CLI ) {

            WP_CLI::add_command( 'ultimate-mailchimp sync-users', array( $this, 'sync_users' ) );
            WP_CLI::add_command( 'ultimate-mailchimp get-batches', array( $this, 'get_batches' ) );
            WP_CLI::add_command( 'ultimate-mailchimp webhook-url', array( $this, 'generate_webhook_url' ) );

        };

        // Add custom fields to user profiles
        add_action( 'show_user_profile', array( $this, 'add_user_custom_fields' ) );
        add_action( 'edit_user_profile', array( $this, 'add_user_custom_fields' ) );

        // Save new user custom fields
        add_action( 'personal_options_update', array( $this, 'save_user_custom_fields' ) );
        add_action( 'edit_user_profile_update', array( $this, 'save_user_custom_fields' ) );

        // Setup webhook REST API ednpoint
        add_action( 'rest_api_init', function () {
            register_rest_route( 'ultimate-mailchimp/v1', '/webhook', array(
                'methods' => 'POST',
                'callback' => array( $this, 'webhook' )
            ));
        });

    }

    public function add_user_custom_fields( $user ){

        ?>
        <h3><?php _e("Mailchimp syncing", "blank"); ?></h3>

        <table class="form-table">
            <tr>
                <th><label for="address"><?php _e("Address"); ?></label></th>
                <td>
                    <input type="text" name="address" id="address" value="<?php echo esc_attr( get_the_author_meta( 'address', $user->ID ) ); ?>" class="regular-text" /><br />
                    <span class="description"><?php _e("Please enter your address."); ?></span>
                </td>
            </tr>
        </table>
        <?php

    }


    public function save_user_custom_fields( $user_id ) {

        if ( !current_user_can( 'edit_user', $user_id ) ) {
            return false;
        }

        // update_user_meta( $user_id, 'address', $_POST['address'] );
        // update_user_meta( $user_id, 'city', $_POST['city'] );
        // update_user_meta( $user_id, 'postalcode', $_POST['postalcode'] );

    }


    public function sync_users( $args, $assoc_args ){

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

        $users = get_users( $args );

        $this->send_batch_to_mailchimp( $users );

    }

    private function connect_to_mailchimp(){

        //ASTODO Add check to make sure constant is set ULTIMATE_MAILCHIMP_API_KEY
        //ASTODO Make sure user list constant exists ULTIMATE_MAILCHIMP_LIST_ID
        $this->MailChimp = new \DrewM\MailChimp\MailChimp( ULTIMATE_MAILCHIMP_API_KEY );

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

    private function send_batch_to_mailchimp( $users = array() ){

        $this->connect_to_mailchimp();

        $batch_process = $this->MailChimp->new_batch();

        foreach( $users as $key => $user ){

            $batch_process->post( "op" . $key , "lists/" . ULTIMATE_MAILCHIMP_LIST_ID . "/members", [
                'email_address' => $user->data->user_email,
                'status' => 'pending', // subscribed - unsubscribed - cleaned - pending
            ]);

        }

        $result = $batch_process->execute();

        echo WP_CLI::success( "Batch started | ID: " . $result['id'] );



        // $result = $batch_process->check_status();
        //
        // echo "--------------------------------------------";
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

        $result = $this->MailChimp->get( "batches" );

        //ASTODO need to order this output by date order
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

    // ASTODO migrate this to human_time_diff https://codex.wordpress.org/Function_Reference/human_time_diff
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
