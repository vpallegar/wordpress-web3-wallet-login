=== Plugin Name ===
Contributors: vpallegar
Tags: wordpress, web3, wallet, login, ethereum, ploygon
Requires at least: 4.7
Tested up to: 5.9
Stable tag: 4.3
Requires PHP: 7.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

This module allows for users to login via their web3 wallet.

== Description ==

Administrators can provide the ability to enable web3 login and set public addresses for any wordpress user account.

Once enabled, a user will see a button on the login page that will launch their web3 ethereum compatible wallet (ie metamask) and allow them to sign a message.  Signing a message ensure the user is the owner of the address they are logging in with and does not compromise any account information or cost any gas fees.

Features:
```
- Web3 wallet login
- Require message signature for verification
- Ethereum network support
```
Settings page:
`/wp-admin/options-general.php?page=web3-login`

== Frequently Asked Questions ==

== Screenshots ==
1. Plugin settings page.
2. Entering a wallet address for a specific user profile.
3. Logging into wordpress via the web3 login button.

== Changelog ==

= 1.0 =
* Initial commit

