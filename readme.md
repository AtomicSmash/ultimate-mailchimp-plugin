# Ultimate MailChimp plugin - In Development
## v0.0.1
### This plugin may break your site 💀 or MailChimp account 💀💀💀. Please only use in development environments.

## Plugin principles and functionality

These are the simple principles of how the plugin functions.

### Single source of truth

No record of the whether the user is subscribed to MailChimp is actually stored inside WordPress. When a user completes a transaction an API call is fired to MailChimp to send this information.

We usually find if the user's MailChimp subscription status is stored somewhere that isn't MailChimp itself, then a method of syncing needs to be created a maintained. Keep it simple and remove this requirement.

If your site does need to know a user's subscription status then use the API and query MailChimp directly when required.

### Subscription signup on purchase

Users will be presented with a description of MailChimp as a marketing platform and a WooCommerce checkbox to confirm subscription status.

![woocommerce-signup](https://user-images.githubusercontent.com/1636310/42940662-3559c458-8b52-11e8-8c8a-c036d31d4cd1.png)

All of this text in editable via the available filters below. 😎

### Transactional data

Currently, this plugin doesn't handle syncing of transactional data. It will do in the future.

## Features

- Bulk sync users from WordPress to MailChimp
- GDPR compliant newsletter description built in

## Merge fields

These merge fields are sent by default.

## When are user details get sent to MailChimp?

The plugin hooks into these actions to update MailChimp:

- `user_register` : This is a global hook that is fired when a user is created inside WordPress. This means it's fired from the frontend registration form, backend user addition screen, any plugin or custom script used to register users and almost anything in between.

- `woo`

## Setup

### Step 1 - Turn on WooCommerce terms and conditions

We have bundled this notice with the native 'Terms and conditions' block due to the opt-in process being inherently linked. So for the MailChimp block to appear, there needs to be a 'Terms and conditions' page set inside WooCommerce.

Go to `your-site.com/wp-admin/admin.php?page=wc-settings&tab=checkout` and make sure a 'terms and conditions' page is set.

### Step 2 - Add configs

Add these config details to your `wp-config.php`

```
define('ULTIMATE_MAILCHIMP_LIST_ID', '');
define('ULTIMATE_MAILCHIMP_API_KEY', '');
define('ULTIMATE_MAILCHIMP_DOUBLE_OPTIN', true);
```

`ULTIMATE_MAILCHIMP_LIST_ID` can be found inside the MailChimp account by going to your list > Settings > List name and campaign.

![mailchimp_get_list_id](https://user-images.githubusercontent.com/1636310/43076416-18e63d42-8e7c-11e8-907d-03074ba6879a.gif)

`ULTIMATE_MAILCHIMP_API_KEY` your key can be found in your account.

`ULTIMATE_MAILCHIMP_DOUBLE_OPTIN`

#### Bonus Config

Here are some extra config options. We would recommend only using these in a development or testing enviroment.

`define('ULTIMATE_MAILCHIMP_LOGGING', true);` - This enables logging to the file: `wp-content/uploads/ultimate-mailchimp.log`

`define('ULTIMATE_MAILCHIMP_DEBUG', true);` - When in debug mode, WooCommerce checkout will not complete (There will be a JSON error) yet MailChimp will be called. This allows you to run through the checkout process without having to create a new order each time.


### Step 3 - Configure opt-in (encouraged)

By default, when someone checks the "Sign me up to the MailChimp newsletter", the opt-in date is passed to MailChimp. If you would to configure the double opt-in process, go to your list > Settings > List name and campaign and check the "Enable double opt-in" checkbox and save.

![mailchimp_double_opt_in](https://user-images.githubusercontent.com/1636310/43076417-1901cf3a-8e7c-11e8-8a8f-c5f0e63a0ff7.gif)

Users will then be emailed when they are added to the list to confirm their status.

## Available filters

WooCommerce checkout filters, these all let you edit different parts of the checkout process.

`ul_mc_custom_merge_fields` - An array of merge fields sent to MailChimp.

`ul_mc_checkout_title` - The title in WooCommerce signup box.

`ul_mc_checkout_checkbox_label` - The checkbox label in WooCommerce signup box.

`ul_mc_checkout_paragraph_one` - This is for the paragraph **before** the opt-in checkbox

`ul_mc_checkout_paragraph_two` - This is for the paragraph **after** the opt-in checkbox

## Example use of filters

Setting up merge fields that will be sent to MailChimp

```
function my_custom_merge_fields( $merge_fields ) {

    // You use the field name
    // $merge_fields['CUSTOM'] = 'Custom data processing here';

    // Or use the field key
    $merge_fields['MERGE5'] = 'Custom data processing here';

    return $merge_fields;

}
add_filter( 'ul_mc_custom_merge_fields', 'my_custom_merge_fields' );

```

Tweaking any of the text based filters above.

```
function my_custom_title( $example ) {
    return 'My custom title';
}
add_filter( 'ul_mc_checkout_title', 'my_custom_title' );
```


## Upcoming features

- Send transactional info with purchases
 - Sync products / store information
- Add shortcode and snippet for loading the signup form
- Seperate dev (monolog) requirements inside composer file