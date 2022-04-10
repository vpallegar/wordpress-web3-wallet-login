<?php 

/**
*  html-admin-settings
*
*  View to output settings
*/

if( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

?>
<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
	<form action="options.php" method="post">
		
		<?php
		
		// // output security fields
		settings_fields( 'web3-wallet-login_options' );
		
		// // output setting sections
		do_settings_sections( 'web3-wallet-login' );
		
		// // submit button
		submit_button();
		
		?>

		<p><?php _e("Once enabled, a Web3 wallet login button will appear on the login form.", "web3-wallet-login"); ?></p>
		
	</form>
</div>