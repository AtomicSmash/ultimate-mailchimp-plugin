<?php

class UltimateMailChimpPluginCLI extends UltimateMailChimpPlugin{

    // Present to overide parent __construct and not re-add cli commands
    function __construct() {

        // Setup CLI commands
        if ( defined( 'WP_CLI' ) && WP_CLI ) {
            if ( defined( 'ULTIMATE_MAILCHIMP_API_KEY' ) && defined( 'ULTIMATE_MAILCHIMP_LIST_ID' ) ) {
                // WP_CLI::add_command( 'ultimate-mailchimp sync-marketing-permissions-fields', array( $ultimate_mailchimp_cli, 'sync_marketing_permission_fields' ) );
                WP_CLI::add_command( 'ultimate-mailchimp sync-users', array( $this, 'sync_users_with_mailchimp' ) );
                WP_CLI::add_command( 'ultimate-mailchimp show-batches', array( $this, 'get_batches' ) );
            }else{
                WP_CLI::add_command( 'ultimate-mailchimp please-setup-plugin', array( $this, 'setup_warning' ) );
            }
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


    public function sync_users_with_mailchimp( $args, $assoc_args ) {

        $users = $this->get_wp_users();

        $this->send_batch_to_mailchimp( $users );

    }

    private function get_wp_users(){

        //ASTODO add number overriding
        $args = array(
            'number' => -1,
        );

        //ASTODO add a filter to change the user args
        $users = get_users( $args );

        return $users;

    }

    private function send_batch_to_mailchimp( $users = array() ){

        $this->connect_to_mailchimp();

        $batch_process = $this->MailChimp->new_batch();

        foreach( $users as $key => $user ){
            WP_CLI::line( "User:" . $user->ID );

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

        // $result = $batch_process->execute();
        // echo WP_CLI::success( "Batch started | ID: " . $result['id'] );

    }


    public function get_batches( $cli_args = array() ){

        $this->connect_to_mailchimp();

        if( ! isset( $cli_args[0] ) ) {


            $result = $this->MailChimp->get( "batches?count=20" );

            if( $result != null && count( $result['batches'] ) > 0 ){
                // Sort batch results by internal timestamp

                usort( $result['batches'], function($a, $b){
                    return strtotime( $b['submitted_at'] ) - strtotime( $a['submitted_at'] );
                });

                // Loop through all the returned batches and display details
                foreach( $result['batches'] as $batch ){
                    WP_CLI::line( $batch['id'] . " | " . $batch['status'] . " | " . $this->time_ago( $batch['submitted_at'] ) );
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

    // private function is_user_on_mailchimp_list( $user_email = "" ){
    //
    //     $subscriber_hash = $this->MailChimp->subscriberHash( $user_email );
    //
    //     $result = $this->MailChimp->get( "lists/" . ULTIMATE_MAILCHIMP_LIST_ID . "/members/" . $subscriber_hash );
    //
    //     if($result['status'] == '404'){
    //         return false;
    //     }else{
    //         return true;
    //     }
    //
    // }


    // public function sync_marketing_permission_fields( $args, $assoc_args ) {
    //
    //     WP_CLI::line( "Connecting to MailChimp" );
    //
    //     $this->connect_to_mailchimp();
    //
    //     // Get the first member that exists, we only need one to find the marketing field information.
    //     $result = $this->MailChimp->get( "lists/" . ULTIMATE_MAILCHIMP_LIST_ID . "/members", [
    //        'count' => 1
    //     ]);
    //
    //
    //     if( $this->MailChimp->success() ) {
    //
    //         if( count( $result['members'] ) > 0 ){
    //
    //             if( count( $result['members'][0]['marketing_permissions'] ) > 0 ){
    //
    //                 WP_CLI::line( count( $result['members'][0]['marketing_permissions'] ) . " marketing permission fields found" );
    //                 WP_CLI::line( "" );
    //
    //                 $fields = array();
    //
    //                 foreach( $result['members'][0]['marketing_permissions'] as $key => $field ){
    //
    //                     WP_CLI::line( "Field " . ( $key + 1 ) );
    //
    //                     WP_CLI::line( "  Marketing permission ID | marketing_permission_id = " . $field['marketing_permission_id'] );
    //                     WP_CLI::line( "  Marketing permission TEXT | text = " . $field['text'] );
    //
    //                     $fields[$key]['marketing_permission_id'] = $field['marketing_permission_id'];
    //                     $fields[$key]['text'] = $field['text'];
    //
    //                 }
    //
    //                 WP_CLI::line( "" );
    //
    //                 WP_CLI::success( "Updated copy of permission fields" );
    //
    //                 update_option( 'um_communication_permission_fields', $fields, 0 );
    //
    //             }else{
    //                 WP_CLI::line( "NO marketing permission fields found :(" );
    //             }
    //
    //
    //         }else{
    //             WP_CLI::error( "NO marketing permission fields found :(" );
    //         }
    //
    //     } else {
    //         WP_CLI::error( "There was an issue connecting to MailChimp" );
    //     }
    //
    // }

}
