# Ultimate MailChimp plugin - In Development
## v0.0.1

## Features

- Bulk sync users form WordPress to MailChimp


## Merge fields

## When are user details automatically sent to MailChimp?

The plugin hooks into these action to update MailChimp:

- `user_register` : This is a global hook that is fired when a user is created inside WordPress. This means it's fired from the frontend registration form, backend user addition screen, any plugin or custom script used to register users and almost anything in between.

- `woo`

## Available filters

WooCommerce checkout

`ul_mc_custom_merge_fields` - Modify the merge fields sent to MailChimp.

`ul_mc_checkout_title` - Modify the title in WooCommerce signup box.

`ul_mc_checkout_checkbox_label` - Modify the checkbox label in WooCommerce signup box.

define ('ULTIMATE_MAILCHIMP_LOGGING', true);

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
