# Ultimate MailChimp Plugin - v0.0.1
## WooCommerce MailChimp signups
### This plugin may break your site 💀 or even MailChimp account 💀💀💀. Please only use in development environments.

## What does this plugin actually do?

On almost all of the WordPress sites that [Atomic Smash](https://www.atomicsmash.co.uk) makes, we do some form of MailChimp syncing. **Most** existing plugins rely on the need to record information within WP to then sync with MailChimp at a later point.

We found this can become very cumbersome. List members may be able to interact with MailChimp from inside your site **OR** via the list communication preferences. This boils down to a member being able to unsubscribe without your WP site being involved. Of course, you can setup webhooks to notify your site when a change is made via MailChimp blah blah blah... **Keep it simple**, maintain MailChimp as the 'single source of truth' and forgot about needing to keep subscription preferences in line with the WP site.

#### Data cleanliness

Thanks to this separation, WordPress and MailChimp are completable replaceable in the future. 
- If you need to move to a new CMS, everything is inside MailChimp all ready
- If you need to move away from MailChimp, just export from MailChimp. There are no user artifacts on within Wordpress regarding their communication preferences.


#### When does this plugin talk to MailChimp

This plugin communicates with MailChimp when a user completes a WooCommerce transaction. Specifically this hook:

- `woocommerce_checkout_update_order_meta`:  This is fired after a successful order via WooCommerce.

**More points of interaction will be added in the future.**


### Subscription signup on WooCommerce purchase

Users will be presented with a description of MailChimp as a marketing platform and a WooCommerce checkbox to confirm subscription status.

All of this text in editable via the available via [these filters](https://github.com/AtomicSmash/ultimate-mailchimp-plugin/wiki/Filters). 😎


## Features

- GDPR compliant newsletter description built in
- Send custom merge fields to MailChimp

## Upcoming features

- Currently, this plugin doesn't handle syncing of transactional data. It **will** in the future.


## Complete site setup

### Step 1 - Turn on WooCommerce terms and conditions

We have bundled this notice with the native 'Terms and conditions' block due to the opt-in process being inherently linked. So for the MailChimp block to appear, there needs to be a 'Terms and conditions' page set inside WooCommerce.

Go to `your-site.com/wp-admin/admin.php?page=wc-settings&tab=checkout` and make sure a 'terms and conditions' page is set.

### Step 2 - Add configs

Add these config details to your `wp-config.php`

```
define('ULTIMATE_MAILCHIMP_LIST_ID', '');
define('ULTIMATE_MAILCHIMP_API_KEY', '');
```

`ULTIMATE_MAILCHIMP_LIST_ID` - This is the list you would sync with. This can be [found here](https://user-images.githubusercontent.com/1636310/43076416-18e63d42-8e7c-11e8-907d-03074ba6879a.gif).

`ULTIMATE_MAILCHIMP_API_KEY` - This is your key can be found in your account.

### Step 3 - Enable double opt-in (encouraged)

By default, when someone checks the "Sign me up to the MailChimp newsletter", the subscriber status is set to 'pending'. This will trigger a confirmation email from MailChimp.

To make sure this functionality works. Go to your list > Settings > 'List name and campaign' and check the "Enable double opt-in" checkbox and save. [Like this](https://user-images.githubusercontent.com/1636310/43076417-1901cf3a-8e7c-11e8-8a8f-c5f0e63a0ff7.gif).

If you would like to disable the double opt-in and force the user to be subscribed on sync, add this constant to your config and set to `false`.

```
define('ULTIMATE_MAILCHIMP_DOUBLE_OPTIN', false);
```

### Step 4 - GDPR fields

MailChimp have added a series of 'marketing permission' (GDPR) fields to it's API. To be able to interact with these, we need a few pieces of information.

- Field ID
- Field Name
- Field value (whether it should be checked or not)



### Other Config

Here are some extra config options. We would recommend only using these in a development or testing enviroment.

```
define('ULTIMATE_MAILCHIMP_LOGGING', true);
define('ULTIMATE_MAILCHIMP_DEBUG', true);
```

`ULTIMATE_MAILCHIMP_LOGGING` - This enables logging to the file: `wp-content/uploads/ultimate-mailchimp.log`

`ULTIMATE_MAILCHIMP_DEBUG` - When in debug mode, WooCommerce checkout will not complete (There will be a JSON error) yet MailChimp will be called. This allows you to run through the checkout process without having to create a new order each time.


## Merge fields

These merge fields are sent by default are:

```
**FNAME** - This is taken from the first name in the billing details.
**LNAME** - This is taken from the last name in the billing details.
```

To edit the merge fields sent to MailChimp, use the [merge field filter here](https://github.com/AtomicSmash/ultimate-mailchimp-plugin/wiki/Filters).

## Available filters

[View filters here](https://github.com/AtomicSmash/ultimate-mailchimp-plugin/wiki/Filters)

## Upcoming features

- [ ] Bulk sync users from WordPress to MailChimp
- [ ] Send transactional info with purchases
  - [ ] Sync products / store information
- [ ] Add shortcode and snippet for loading the signup form
- [ ] Seperate dev (monolog) requirements inside composer file
- [ ] Add `user_register` : This is a global hook that is fired when a user is created inside WordPress. This means it's fired from the frontend registration form, backend user addition screen, any plugin or custom script used to register users and almost anything in between.
