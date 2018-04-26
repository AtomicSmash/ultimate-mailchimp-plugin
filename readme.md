# Ultimate MailChimp plugin - In Development
## v0.0.1

## Features

- Bulk sync


### Available filters

WooCommerce checkout
`ul_mc_checkout_title` - Modify the title in WooCommerce signup box
`ul_mc_checkout_checkbox_label` - Modify the checkbox label in WooCommerce signup box

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
