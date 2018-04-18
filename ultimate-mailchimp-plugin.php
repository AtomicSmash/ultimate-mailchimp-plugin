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

            WP_CLI::add_command( 'ultimate-mailchimp test', array( $this, 'create_bucket' ) );

        };

    }


    function test( $args, $assoc_args ){

        echo WP_CLI::success( "Hello!");

    }

}

$ultimate_mailchimp_plugin = new UltimateMailChimpPlugin;
