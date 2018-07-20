# Ultimate MailChimp plugin - In Development
## v0.0.1 - This plugin may break your site 💀. Please only use in development environments.

## Principles

These are the simple principles of how the plugin functions.

### Single source of truth

No record of the whether the user is subscribed to MailChimp is actually stored inside WordPress. When a user completes a transaction.

We usually find if the user's MailChimp subscription status in stored somewhere that isn't MailChimp itself, then a method of syncing needs to be created a maintained. Keep it simple and remove this requirement.

If your site does need to know a user's subscription status then use the API and query MailChimp directly when required.

### Subscription signup on purchase

Users will be presented with a description of MailChimp as a marketing platform and a WooCommerce checkbox to confirm subscription status.

![woocommerce-signup](https://user-images.githubusercontent.com/1636310/42940662-3559c458-8b52-11e8-8c8a-c036d31d4cd1.png)

All of this text in editable via the available filters below. 😎

### Transactional data

Currently this plugin doesn't handle syncing of transactional data. It will do in the future.

## Features

- Bulk sync users form WordPress to MailChimp
- GDPR compliant newsletter description built in

## Merge fields

## When are user details get sent to MailChimp?

The plugin hooks into these action to update MailChimp:

- `user_register` : This is a global hook that is fired when a user is created inside WordPress. This means it's fired from the frontend registration form, backend user addition screen, any plugin or custom script used to register users and almost anything in between.

- `woo`

## Setup

`ULTIMATE_MAILCHIMP_LIST_ID ` - The list id



```
define('ULTIMATE_MAILCHIMP_LIST_ID', '');
define('ULTIMATE_MAILCHIMP_API_KEY', '');
```


## Options - php constants

define ('ULTIMATE_MAILCHIMP_LOGGING', true);


## Available filters

WooCommerce checkout

`ul_mc_custom_merge_fields` - Modify the merge fields sent to MailChimp.

`ul_mc_checkout_title` - Modify the title in WooCommerce signup box.

`ul_mc_checkout_checkbox_label` - Modify the checkbox label in WooCommerce signup box.

`ul_mc_checkout_paragraph_one` -

`ul_mc_checkout_paragraph_two` -

## Example use of filters

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

```
function my_custom_title( $example ) {
    return 'My custom title';
}
add_filter( 'ul_mc_checkout_title', 'my_custom_title' );
```

```
function my_custom_label( $example ) {
    return 'My custom label!';
}
add_filter( 'ul_mc_checkout_checkbox_label', 'my_custom_label' );

```

## Upcoming features

- Get webhooks working
- Send transactional info with purchases
