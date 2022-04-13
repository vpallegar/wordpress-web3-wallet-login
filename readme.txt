=== Web3 Wallet Login ===
Contributors: vpallegar
Tags: wordpress, web3, wallet, login, ethereum, polygon
Requires at least: 4.7
Tested up to: 5.9
Stable tag: 1.1.1
Requires PHP: 7.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

This module allows for users to login to their wordpress account via their web3 wallet.

== Description ==

Administrators can provide the ability to enable Web3 Wallet Login and set public addresses for any wordpress user account.

Once enabled, a user will see a button on the login page that will launch their web3 ethereum compatible wallet (ie metamask) and allow them to sign a message.  Signing a message ensures the user is the owner of the address they are logging in with and does not compromise any account information or cost any gas fees.

Features:
```
- Web3 wallet login
- Require message signature for verification
- Ethereum network support
```
Settings page:
`/wp-admin/options-general.php?page=web3-wallet-login`

To report issues or submit PR's please visit https://github.com/vpallegar/wordpress-web3-wallet-login.

== Frequently Asked Questions ==

== Screenshots ==
1. Plugin settings page.
2. Entering a wallet address for a specific user profile.
3. Logging into wordpress via the Web3 Wallet Login button.

== Changelog ==

= 1.1.1 =
* Fixed bug for logging in without $user object sent.

= 1.1.0 =
* Added check_ajax_referer to login requests.

= 1.0.0 =
* Initial commit.

