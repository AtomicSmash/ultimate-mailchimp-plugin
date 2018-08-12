<?php

// NONE OF THESE FUNCTIONS ARE CURRENTLY BEING USED!!!
// In fact, this file isn't even loaded.
// But they may in the future ;)

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


private function is_user_on_mailchimp_list( $user_email = "" ){

    $subscriber_hash = $this->MailChimp->subscriberHash( $user_email );

    $result = $this->MailChimp->get( "lists/" . ULTIMATE_MAILCHIMP_LIST_ID . "/members/" . $subscriber_hash );

    if($result['status'] == '404'){
        return false;
    }else{
        return true;
    }

}


public function sync_marketing_permission_fields( $args, $assoc_args ) {

    WP_CLI::line( "Connecting to MailChimp" );

    $this->connect_to_mailchimp();

    // Get the first member that exists, we only need one to find the marketing field information.
    $result = $this->MailChimp->get( "lists/" . ULTIMATE_MAILCHIMP_LIST_ID . "/members", [
       'count' => 1
    ]);


    if( $this->MailChimp->success() ) {

        if( count( $result['members'] ) > 0 ){

            if( count( $result['members'][0]['marketing_permissions'] ) > 0 ){

                WP_CLI::line( count( $result['members'][0]['marketing_permissions'] ) . " marketing permission fields found" );
                WP_CLI::line( "" );

                $fields = array();

                foreach( $result['members'][0]['marketing_permissions'] as $key => $field ){

                    WP_CLI::line( "Field " . ( $key + 1 ) );

                    WP_CLI::line( "  Marketing permission ID | marketing_permission_id = " . $field['marketing_permission_id'] );
                    WP_CLI::line( "  Marketing permission TEXT | text = " . $field['text'] );

                    $fields[$key]['marketing_permission_id'] = $field['marketing_permission_id'];
                    $fields[$key]['text'] = $field['text'];

                }

                WP_CLI::line( "" );

                WP_CLI::success( "Updated copy of permission fields" );

                update_option( 'um_communication_permission_fields', $fields, 0 );

            }else{
                WP_CLI::line( "NO marketing permission fields found :(" );
            }


        }else{
            WP_CLI::error( "NO marketing permission fields found :(" );
        }

    } else {
        WP_CLI::error( "There was an issue connecting to MailChimp" );
    }

}
