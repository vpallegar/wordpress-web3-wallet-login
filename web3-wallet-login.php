<?php
/**
 * Web3 Wallet Login
 *
 * Plugin Name: Web3 Wallet Login
 * Description: Allow users to login via their web3 wallet address.
 * Version:     1.1.1
 * Author:      iPal Media Inc.
 * Author URI:  https://ipalmedia.com
 * Text Domain: web3-wallet-login
 * Domain Path: /languages
 *
 * @package Web3 Wallet Login
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WEB3_WALLET_LOGIN' ) ) :


	// include Classes.
	$includes = [];

	foreach ( $includes as $inc ) {
		include plugin_dir_path( __FILE__ ) . "/includes/{$inc}.php";
	}


	/**
	 * Plugin Main Class
	 */
	class WEB3_WALLET_LOGIN {

		/**
		 * Plugin Version Number
		 *
		 * @var  string $version The plugin version number.
		 */
		public $version = '1.1.1';

		/**
		 * Database Cchema Version Number
		 *
		 * @var  string $version The database schema version number.
		 */
		const DB_VERSION = '1.0.0';

		/**
		 * Plugin Settings Array
		 *
		 * @var  array $settings The plugin settings array.
		 */
		public $settings = array();

		const POST_TYPE_NAME = 'web3-wallet-login';

		const TABLE_NAME_LOG = 'web3login_log';

		/**
		 * Initialize the plugin
		 *
		 * @return void
		 */
		public static function init() {
			$class = __CLASS__;
			$blt   = new $class();
			$blt->init_actions();
		}

		/**
		 * Constructor
		 *
		 * @return void
		 */
		public function __construct() {
			
			// Grab Plugin Settings.
			$this->settings = get_option( 'web3-wallet-login_options' ) ?? [];
		}

		/**
		 * Initialize the plugin features
		 *
		 * @return void
		 */
		public function init_actions() {

			$this->define( 'WEB3LBS_URL', plugin_dir_url( __FILE__ ) );
			$this->define( 'WEB3LBS_PATH', plugin_dir_path( __FILE__ ) );

			// Setup admin menu.
			add_action( 'admin_menu', array( $this, 'admin_menu_init' ) );

			// Update login page.
			$this->setupLoginpage();

			// setup settings page.
			add_action( 'admin_init', array( $this, 'register_settings' ) );

			// finally display any admin notices.
			add_action( 'admin_notices', array( $this, 'check_admin_notices' ) );

			// Add settings to user form.
			add_action( 'show_user_profile', array( $this, 'user_settings_setup' ) );

			// Save user settings.
			add_action( 'profile_update', array( $this, 'user_settings_save' ) );
			add_action( 'edit_user_profile_update', array( $this, 'user_settings_save' ) );

			// Load Ajax functions.
			add_action( 'wp_loaded', array( $this, 'add_wp_ajax_functions' ) );
		}

		/**
		 * Add admin menus
		 *
		 * @return void
		 */
		public function admin_menu_init() {

			add_options_page(
				__( 'Web3 Wallet Login', 'web3-wallet-login' ),
				esc_html__( 'Web3 Wallet Login', 'web3-wallet-login' ),
				'manage_options',
				'web3-wallet-login',
				array( $this, 'show_settings_page' )
			);
		}

		/**
		 * Show the Web3 Wallet Login button.
		 *
		 * @return void
		 */
		protected function setupLoginpage() {

			// Enable login form adjustments if web3-wallet-login is enabled.
			$settings = get_option('web3-wallet-login_options') ?? [];
			if (!empty($settings['activate'])) :

				// Load our style sheet on login page.
				add_action( 'login_enqueue_scripts', function() {

					// Add login styles
					wp_enqueue_style( 'web3-wallet-login-plugin-sitestyles', WEB3LBS_URL . 'public/css/web3-wallet-login-styles.css', false, filemtime( WEB3LBS_PATH . 'public/css/web3-wallet-login-styles.css' ), 'all' );

					// Add external script dependencies.
					wp_register_script( 'web3-wallet-login-plugin-library', WEB3LBS_URL . 'public/js/web3.min.js', false );
					wp_enqueue_script( 'web3-wallet-login-plugin-library' );
					wp_register_script( 'web3-wallet-login-plugin-librarybops', WEB3LBS_URL . 'public/js/bops.js', false );
					wp_enqueue_script( 'web3-wallet-login-plugin-librarybops' );

					// Load login button scripts.
					wp_register_script( 'web3-wallet-login-plugin-scripts', WEB3LBS_URL . 'public/js/web3-wallet-login-library.js', array( 'jquery' ), filemtime( WEB3LBS_PATH . 'public/js/web3-wallet-login-library.js' ), true );
					wp_enqueue_script( 'web3-wallet-login-plugin-scripts' );
					wp_localize_script( 'web3-wallet-login-plugin-scripts', 'loginvars', ['site' => site_url(), 'nonce' => wp_create_nonce('web3_wallet_login_nonce'), 'actionurl' => admin_url('admin-ajax.php'),] );
				});


				// Add our login button.
				add_action('login_form', function() {
					$buttonText = _('Login with web3 wallet', 'web3-wallet-login');
					$allowed_html = [
						'div' => [
								'class' => []
								], 
						'button' => [
								'class' => [],
								'id' => [],
								'type' => [],
								'name' => []
								]
						];
					echo wp_kses('<div class="web3-wallet-login-button-wrapper"><button type="button" id="web3loginConnect">' . $buttonText . '</button><div class="web3loginMsg"></div></div>', $allowed_html);
				});

			endif;
		}


		/**
		 * Check an authenication attempt.
		 */
		public static function checkLogin() {

			// Lets ensure web3-wallet-login is enabled.
			$settings = get_option('web3-wallet-login_options') ?? [];
			if (empty($settings['activate'])) wp_die();

			// Check correct variables were sent;
			$address = sanitize_text_field($_POST['address']) ?? '';
			$nonce = sanitize_text_field($_POST['signonce']) ?? '';
			$sig = sanitize_text_field($_POST['sig']) ?? '';

			if (empty($address)  || empty($nonce) || empty($sig)) {
				self::respondWithError('Some parameters missing.' );
			}

            check_ajax_referer('web3_wallet_login_nonce');

			// Load dependencies needed to check signature.
			require_once(WEB3LBS_PATH . 'vendor/autoload.php');

			// First lets find a user that matches this address.
			$users = get_users(array(
				'meta_key' => 'WEB3_WALLET_LOGIN_address',
				'meta_value' => trim(strtolower($address))
			));
			if ( empty( $users ) ) {
				self::respondWithError('No user matches the wallet address sent.' );
			}

			$user = reset($users);

			// Verify signature.
			$site_url = site_url();
			$message = 'Allow web3-wallet-login for ' . $site_url . ' at ' . $nonce;
			if ( ! self::verifySignature($message, $sig, $address) ) {
				self::log($user->ID, 0, $nonce);
				self::respondWithError('Invalid signature.' );
			}

			// Check nonce is valid (in future not already used)
			if (!self::checkNonceIsValid($user->ID, $nonce)) {
				self::log($user->ID, 0, $nonce);
				self::respondWithError('Access denied');
			}

			// Finalize login.
			self::log($user->ID, 1, $nonce);
			wp_set_current_user($user->ID, $user->user_login );
			wp_set_auth_cookie($user->ID);
			do_action('wp_login', $user->user_login, $user );

			// Now redirect to dashboard.
			self::respondWithSuccess('Login successful');

			wp_die();
		}


		/**
		 * 
		 */
		protected static function pubKeyToAddress($pubkey) {
			return "0x" . substr(\kornrunner\Keccak::hash(substr(hex2bin($pubkey->encode("hex")), 1), 256), 24);
		}

		/**
		 * Return error message to user.
		 */
		private static function respondWithError($message) {
			wp_send_json_error($message);
		}

		/**
		 * Return success message to user.
		 */
		private static function respondWithSuccess($message) {
			wp_send_json_success($message);
		}

		/**
		 * 
		 */
		private static function verifySignature($message, $signature, $address) {
			if (!(strlen($signature) % 2 == 0)) {
				return FALSE;
			}
			$msglen = strlen($message);
			$hash   = \kornrunner\Keccak::hash("\x19Ethereum Signed Message:\n{$msglen}{$message}", 256);
			$sign   = ["r" => substr($signature, 2, 64), 
					"s" => substr($signature, 66, 64)];
			$recid  = ord(hex2bin(substr($signature, 130, 2))) - 27; 
			if ($recid != ($recid & 1)) 
				return false;

			$ec = new \Elliptic\EC('secp256k1');
			$pubkey = $ec->recoverPubKey($hash, $sign, $recid);

			return strtolower($address) == strtolower(self::pubKeyToAddress($pubkey));
		}


		/**
		 * 
		 */
		private static function verifySignature_pass2($message, $signature, $address) {
			/* Pass 2 sign */
			$hash = \kornrunner\Keccak::hash($message, 256);
			$sign = ['r' => substr($signature, 2, 64),
			's' => substr($signature, 66, 64), ];
			$recid = ord(hex2bin(substr($signature, 130, 2))) - 27;
			if ($recid != ($recid & 1))
			{
			return false;
			}
			$ec = new \Elliptic\EC('secp256k1');
			$pubkey = $ec->recoverPubKey($hash, $sign, $recid);
			return strtolower($address) == self::pubKeyToAddress($pubkey);
		}

		/**
		 * Add Web3 Wallet Login fields to user registration form.
		 */
		public function user_settings_setup( $user ) {
			$settings = get_option('web3-wallet-login_options') ?? [];
			if (!empty($settings['activate'])) :
			?>
			  <h3><?php _e("Web3 Wallet Login Settings", "web3-wallet-login"); ?></h3>
			  <table class="form-table">
				<tr>
				  <th><label for="WEB3_WALLET_LOGIN_address"><?php _e("Wallet Address", "web3-wallet-login"); ?></label></th>
				  <td>
					<input type="text" name="WEB3_WALLET_LOGIN_address_value" id="WEB3_WALLET_LOGIN_address" class="regular-text" 
						value="<?php echo esc_attr( get_the_author_meta( 'WEB3_WALLET_LOGIN_address', $user->ID ) ); ?>" /><br />
					<span class="description"><?php _e("Enter an ethereum compatible wallet address only."); ?></span>
				</td>
				</tr>
			  </table>
			<?php	

			endif;

		}


		/**
		 *
		 * Save Web3 fields to user profile.
		 *
		 * @param   int   $user_id The User ID.
		 * @return  void
		 */
		public function user_settings_save( $user_id ) {
			$saved = false;
			$settings = get_option('web3-wallet-login_options') ?? [];
			if ( !empty($settings['activate']) && current_user_can( 'edit_user', $user_id ) ) {
			  update_user_meta( $user_id, 'WEB3_WALLET_LOGIN_address', trim(strtolower(sanitize_text_field($_POST['WEB3_WALLET_LOGIN_address_value'] ?? ""))) );
			  $saved = true;
			}
			return true;
		}

		/**
		 * Add Ajax callback handlers
		 */
		public static function add_wp_ajax_functions() {

			add_action( 'wp_ajax_WEB3_WALLET_LOGIN_authenticate', array( __CLASS__, 'checkLogin' ) );
			add_action( 'wp_ajax_nopriv_WEB3_WALLET_LOGIN_authenticate', array( __CLASS__, 'checkLogin' ) );

		}


		/**
		 *
		 * Defines a constant if doesnt already exist.
		 *
		 * @param   string $name The constant name.
		 * @param   mixed  $value The constant value.
		 * @return  void
		 */
		protected function define( $name, $value = true ) {
			if ( ! defined( $name ) ) {
				define( $name, $value );
			}
		}

		/**
		 *
		 * Returns the plugin path to a specified file.
		 *
		 * @param   string $filename The specified file.
		 * @return  string
		 */
		protected function get_path( $filename = '' ) {
			return WEB3LBS_PATH . ltrim( $filename, '/' );
		}


		/**
		 *  This function will load in a file from the 'admin/views' folder and allow variables to be passed through
		 *
		 *  @param  string $dir Directory to locate within.
		 *  @param  string $path Filename.
		 *  @param  array  $args Any arguments.
		 *  @return  void
		 */
		public function get_view( $dir = 'admin', $path = '', $args = array() ) {

			// allow view file name shortcut.
			if ( substr( $path, -4 ) !== '.php' ) {

				$path = $this->get_path( "{$dir}/views/{$path}.php" );

			}

			// include.
			if ( file_exists( $path ) ) {
				extract( $args );
				include $path;
			}

		}

		/**
		 * Check if any admin notices to report on init
		 *   - currently ensures acf is defined
		 *
		 * @return void
		 */
		public function check_admin_notices() {

			return;

			$message = null;
			// get the current screen.
			$screen             = get_current_screen();
			$screens_to_show_on = array( self::POST_TYPE_NAME, 'edit-' . self::POST_TYPE_NAME );

			// return if not this plugin.
			if ( ! in_array( $screen->id, $screens_to_show_on, true ) ) {
				return;
			}
			

			$settings = get_option('web3-wallet-login_options') ?? [];
			

			return;
		}

		/**
		 * Register settings
		 */
		public function register_settings() {

			register_setting(
				'web3-wallet-login_options',
				'web3-wallet-login_options',
				array( $this, 'callback_validate_options' )
			);

			add_settings_section(
				'web3-wallet-login_creds',
				esc_html__( 'Web3 Wallet Login settings', 'web3-wallet-login' ),
				array( $this, 'callback_admin_settings' ),
				'web3-wallet-login'
			);

			add_settings_field(
				'activate',
				esc_html__( 'Enable Web3 user Login', 'web3-wallet-login' ),
				array( $this, 'callback_field_checkbox' ),
				'web3-wallet-login',
				'web3-wallet-login_creds',
				array(
					'id'    => 'activate',
					'label' => esc_html__( 'Yes Enable Ethereum Wallet Support', 'web3-wallet-login' ),
				)
			);
		}


		/**
		 * Validate Settings form on update
		 *
		 * @param  string $input Input Value to clean.
		 */
		public function callback_validate_options_form( $input ) {

			return $input;

		}

		/**
		 * Validate settings on update
		 *
		 * @param  string $input Input Value to clean.
		 */
		public function callback_validate_options( $input ) {

			// web3-wallet-login activate.
			if ( isset( $input['activate'] ) ) {
				$input['activate'] = sanitize_text_field( $input['activate'] );
			}

			return $input;

		}

		/**
		 * Settings Main instruction text
		 */
		public function callback_admin_settings() {

			echo '<p>' . esc_html__( '', 'web3-wallet-login' ) . '</p>';
		}

		/**
		 * Settings Text field
		 *
		 * @param  array $args Parameters to pass.
		 */
		public function callback_field_text( $args ) {

			$options = get_option( 'web3-wallet-login_options', array( $this, 'settings_defaults' ) );

			$id    = isset( $args['id'] ) ? $args['id'] : '';
			$label = isset( $args['label'] ) ? $args['label'] : '';

			$value = isset( $options[ $id ] ) ? sanitize_text_field( $options[ $id ] ) : '';

			$output  = '<input id="web3-wallet-login_options_' . $id . '" name="web3-wallet-login_options[' . $id . ']" type="text" size="40" value="' . $value . '"><br />';
			$output .= '<label for="web3-wallet-login_options_' . $id . '">' . $label . '</label>';

			$allowed_html = [
				'br' => [],
				'input' => [
						'id' => [],
						'name' => [],
						'type' => [],
						'size' => [],
						'value' => []
						], 
				'label' => [
						'for' => []
						]
				];
			echo wp_kses($output, $allowed_html);
		}

		/**
		 * Settings Checkbox field
		 *
		 * @param  array $args Parameters to pass.
		 */
		public function callback_field_checkbox( $args ) {

			$options = get_option( 'web3-wallet-login_options', array( $this, 'settings_defaults' ) );

			$id    = isset( $args['id'] ) ? $args['id'] : '';
			$label = isset( $args['label'] ) ? $args['label'] : '';

			$value = isset( $options[ $id ] ) ? sanitize_text_field( $options[ $id ] ) : '';
			$checked = !empty($value) ? 'checked' : '';

			$output  = '<input id="web3-wallet-login_options_' . $id . '" name="web3-wallet-login_options[' . $id . ']" type="checkbox" size="40" value="1" '.$checked.'> ';
			$output .= '<label for="web3-wallet-login_options_' . $id . '">' . $label . '</label>';

			$allowed_html = [
				'br' => [],
				'input' => [
						'id' => [],
						'name' => [],
						'type' => [],
						'size' => [],
						'value' => [],
						'checked' => []
						], 
				'label' => [
						'for' => []
						]
				];
			echo wp_kses($output, $allowed_html);
		}

		/**
		 * Settings default values
		 */
		public function settings_default() {
			return array(
				'activate' => '',
			);
		}


		/**
		 * Displays settings page fields.
		 */
		public function show_settings_page($page = false) {
			// check if user is allowed access.
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			return $this->get_view( 'admin', 'html-admin-settings' );

		}

		/**
		 * Perform actions during plugin deactivation.
		 */
		public static function deactivate() {

		}

		/**
		 * Create tables for use with plugin on activation.
		 */
		public static function web3_install() : void {

			global $wpdb;

			$table_name = $wpdb->prefix . self::TABLE_NAME_LOG;
			
			$charset_collate = $wpdb->get_charset_collate();

			$sql = "CREATE TABLE $table_name ( 
					`id` INT NOT NULL AUTO_INCREMENT ,
					`uid` INT NOT NULL ,
					`status` TINYINT(1) NOT NULL ,
					`ipaddr` VARCHAR(50) NOT NULL ,
					`nonce` DATETIME NOT NULL ,
					`created` TIMESTAMP NOT NULL ,
					PRIMARY KEY (`id`),
					INDEX `user` (`uid`),
					INDEX `none` (`nonce`)
				) $charset_collate;";
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql );

			add_option( 'web3login_db_version', self::DB_VERSION );
		}


		/**
		 * Check if nonce provided for user is still valid.
		 */
		private static function checkNonceIsValid($user_id, int $nonce) : bool {

			global $wpdb;
			$table_name = $wpdb->prefix . self::TABLE_NAME_LOG;

			$results = $wpdb->get_results($wpdb->prepare("SELECT id FROM $table_name WHERE uid = %d and status=1 and nonce>=%s", $user_id, date("Y-m-d H:i:s", $nonce / 1000)));

			// If record exist then this nonce was used or a newer one was created.
			if (!empty($results) && count($results) > 0) {
				return FALSE;
			}

			return TRUE;
		}

		/**
		 * Logs an error message.
		 * 
		 * @param $user_id
		 * @param int $status
		 * @param int $nonce
		 */
		private static function log(int $user_id, int $status, int $nonce) : void {
		  
			global $wpdb;
			$table_name = $wpdb->prefix . self::TABLE_NAME_LOG;

			$data=array(
				'uid' => $user_id, 
				'status' => $status,
				'ipaddr' => sanitize_text_field($_SERVER['REMOTE_ADDR']), 
				'nonce' => date("Y-m-d H:i:s", $nonce / 1000),
				'created' => date("Y-m-d H:i:s") );		
		
			 $wpdb->insert( $table_name, $data);
		}
	}
	
	add_action( 'plugins_loaded', array( 'WEB3_WALLET_LOGIN', 'init' ) );
	register_activation_hook( __FILE__, array( 'WEB3_WALLET_LOGIN', 'web3_install') );
	register_deactivation_hook( __FILE__, array( 'WEB3_WALLET_LOGIN', 'deactivate' ) );

endif;
