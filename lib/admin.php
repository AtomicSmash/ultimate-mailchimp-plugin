<?php

// NONE OF THESE FUNCTIONS ARE CURRENTLY BEING USED!!!
// In fact, this file isn't even loaded.
// But they may in the future ;)


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
