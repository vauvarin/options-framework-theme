<?php
/*
 *
 * Options Framework Theme - Options Backup
 *
 * Backup your "Theme Options" to a downloadable text file.
 *
 * @version 1.0.0
 * @author Gilles Vauvarin
 *
 * This code is a fork from the WooThemes Framework admin-backup.php file.
 *
 * -----------------------------------------------------------------------------------

 TABLE OF CONTENTS

 - var $admin_page
 - var $token
 
 - function OptionsFramework_Backup () 						// Constructor
 - function init () 										// Initialize the class.
 - function register_admin_screen () 						// Register the admin screen within WordPress.
 - function admin_screen () 								// Load the admin screen.
 - function admin_screen_help ()							// Add contextual help to the admin screen.
 - function admin_notices() 								// Display admin notices when performing backup/restore.
 - function admin_screen_logic ()							// The processing code to generate the backup or restore from a previous backup.	
 - function import ()										// Import settings from a backup file.
 - function export ()										// Export settings to a backup file.
 - function construct_database_query ()						// Constructs the database query based on the export type.

 - Create $woo_backup Object
-----------------------------------------------------------------------------------*/

class OptionsFramework_Backup {
	
	var $admin_page;
	var $token;
	
	function OptionsFramework_Backup () {
		$this->admin_page = '';
		$this->token = 'options-backup';
	} // End Constructor
	
	/**
	 * init()
	 *
	 * Initialize the class.
	 *
	 * @since 1.0.0
	 */
	
	function init () {
		if ( is_admin() && ( get_option( 'framework_woo_backupmenu_disable' ) != 'true' ) ) {
			// Register the admin screen.
			add_action( 'admin_menu', array( &$this, 'register_admin_screen' ), 20 );
		}
	} // End init()
	
	/**
	 * register_admin_screen()
	 *
	 * Register the admin screen within WordPress.
	 *
	 * @since 1.0.0
	 */
	
	function register_admin_screen () {
			
		$this->admin_page = add_submenu_page('options-framework', __( 'OptionsFramework Import / Export', 'OptionsFramework' ), __( 'Import / Export', 'OptionsFramework' ), 'manage_options', $this->token, array( &$this, 'admin_screen' ) );

		// Adds actions to hook in the required css and javascript
		add_action("admin_print_styles-$this->admin_page",'optionsframework_load_adminstyles');

		/* Loads the CSS */

		function optionsframework_load_adminstyles() {
			wp_enqueue_style('admin-style', OPTIONS_FRAMEWORK_DIRECTORY.'css/admin-style.css');
		}
			
		// Admin screen logic.
		add_action( 'load-' . $this->admin_page, array( &$this, 'admin_screen_logic' ) );
		
		// Add contextual help.
		add_action( 'contextual_help', array( &$this, 'admin_screen_help' ), 10, 3 );
				
		add_action( 'admin_notices', array( &$this, 'admin_notices' ), 10 );
	
	} // End register_admin_screen()
	
	/**
	 * admin_screen()
	 *
	 * Load the admin screen.
	 *
	 * @since 1.0.0
	 */
	
	function admin_screen () {
	
		$export_type = 'all';
		
		if ( isset( $_POST['export-type'] ) ) {
			$export_type = esc_attr( $_POST['export-type'] );
		}
?>
	<div class="wrap">
		<?php echo get_screen_icon( $screen = 'import-export' ); ?>	
		<h2><?php _e( 'Import / Export' ); ?></h2>
		
		<div class="import">
			<h3><?php _e( 'Import Settings' ); ?></h3>
			
			<p><?php _e( 'If you have settings in a backup file on your computer, the Import / Export system can import those into this site. To get started, upload your backup file to import from below.' ); ?></p>


				<form enctype="multipart/form-data" method="post" action="<?php echo admin_url( 'admin.php?page=' . $this->token ); ?>">
					<?php wp_nonce_field( 'OptionsFramework-backup-import' ); ?>
					<label for="OptionsFramework-import-file"><?php printf( __( 'Upload File: (Maximum Size: %s)' ), ini_get( 'post_max_size' ) ); ?></label>
					<input type="file" id="OptionsFramework-import-file" name="OptionsFramework-import-file" size="25" />
					<input type="hidden" name="OptionsFramework-backup-import" value="1" />
					<input type="submit" class="button" value="<?php _e( 'Upload File and Import' ); ?>" />
				</form>

		</div>
		
		<div class="export">
			<h3><?php _e( 'Export Settings' ); ?></h3>
			
			<p><?php _e( 'When you click the button below, the Import / Export system will create a text file for you to save to your computer.' ); ?></p>
			<p><?php echo sprintf( __( 'This text file can be used to restore your settings here on "%s", or to easily setup another website with the same settings".' ), get_bloginfo( 'name' ) ); ?></p>
				
			<form method="post" action="<?php echo admin_url( 'admin.php?page=' . $this->token ); ?>">
				<?php wp_nonce_field( 'OptionsFramework-backup-export' ); ?>
				<input type="hidden" name="OptionsFramework-backup-export" value="1" />
				<input type="submit" class="button" value="<?php _e( 'Download Export File', 'OptionsFramework' ); ?>" />
			</form>
		</div>
		
	</div><!--/.wrap-->
<?php
	
	} // End admin_screen()
	
	/**
	 * admin_screen_help()
	 *
	 * Add contextual help to the admin screen.
	 *
	 * @since 1.0.0
	 */
	
	function admin_screen_help ( $contextual_help, $screen_id, $screen ) {
	
		// $contextual_help .= var_dump($screen); // use this to help determine $screen->id
		
		if ( $this->admin_page == $screen->id ) {
		
		$contextual_help =
		  '<h3>' . __( 'Welcome to the OptionsFramework Backup Manager.' ) . '</h3>' .
		  '<p>' . __( 'Here are a few notes on using this screen.' ) . '</p>' .
		  '<p>' . __( 'The backup manager allows you to backup or restore your "Theme Options" and other settings to or from a text file.' ) . '</p>' .
		  '<p>' . __( 'To create a backup, simply select the setting type you\'d like to backup (or "All Settings") and hit the "Download Export File" button.' ) . '</p>' .
		  '<p>' . __( 'To restore your settings from a backup, browse your computer for the file (under the "Import Settings" heading) and hit the "Upload File and Import" button. This will restore only the settings that have changed since the backup.' ) . '</p>' .
		  
		  '<p><strong>' . __( 'Please note that only valid backup files generated through the OptionsFramework Backup Manager should be imported.' ) . '</strong></p>' .

		  '<p><strong>' . __( 'Looking for assistance?' ) . '</strong></p>' .
		  '<p>' . sprintf( __( 'Please post your query on the %sOptionsFramework Support Forums%s where we will do our best to assist you further.' ), '<a href="http://www.OptionsFramework.com/support=forum/" target="_blank">', '</a>' ) . '</p>';
		
		} // End IF Statement
		
		return $contextual_help;
	
	} // End admin_screen_help()
	
	/**
	 * admin_notices()
	 *
	 * Display admin notices when performing backup/restore.
	 *
	 * @since 1.0.0
	 */
	
	function admin_notices () {
	
		if ( ! isset( $_GET['page'] ) || ( $_GET['page'] != $this->token ) ) { return; }
	
		echo '<div id="import-notice" class="updated"><p>' . sprintf( __( 'Please note that this backup manager backs up only your settings and not your content. To backup your content, please use the %sWordPress Export Tool%s.', 'OptionsFramework' ), '<a href="' . admin_url( 'export.php' ) . '">', '</a>' ) . '</p></div><!--/#import-notice .message-->' . "\n";
			
		if ( isset( $_GET['error'] ) && $_GET['error'] == 'true' ) {
			echo '<div id="message" class="error"><p>' . __( 'There was a problem importing your settings. Please Try again.' ) . '</p></div>';
		} else if ( isset( $_GET['error-export'] ) && $_GET['error-export'] == 'true' ) {  
			echo '<div id="message" class="error"><p>' . __( 'There was a problem exporting your settings. Please Try again.' ) . '</p></div>';
		} else if ( isset( $_GET['invalid'] ) && $_GET['invalid'] == 'true' ) {  
			echo '<div id="message" class="error"><p>' . __( 'The import file you\'ve provided is invalid. Please try again.' ) . '</p></div>';
		} else if ( isset( $_GET['imported'] ) && $_GET['imported'] == 'true' ) {  
			echo '<div id="message" class="updated"><p>' . sprintf( __( 'Settings successfully imported. | Return to %sTheme Options%s', 'OptionsFramework' ), '<a href="' . admin_url( 'admin.php?page=options-framework' ) . '">', '</a>' ) . '</p></div>';
		} // End IF Statement
		
	} // End admin_notices()
	
	/**
	 * admin_screen_logic()
	 *
	 * The processing code to generate the backup or restore from a previous backup.
	 *
	 * @since 1.0.0
	 */
	
	function admin_screen_logic () {
		
		if ( ! isset( $_POST['OptionsFramework-backup-export'] ) && isset( $_POST['OptionsFramework-backup-import'] ) && ( $_POST['OptionsFramework-backup-import'] == true ) ) {
			$this->import();
		}
		
		if ( ! isset( $_POST['OptionsFramework-backup-import'] ) && isset( $_POST['OptionsFramework-backup-export'] ) && ( $_POST['OptionsFramework-backup-export'] == true ) ) {
			$this->export();
		}

	} // End admin_screen_logic()
	

	
	/**
	 * import()
	 *
	 * Import settings from a backup file.
	 *
	 * @since 1.0.0
	 */



	function import() {
		check_admin_referer( 'OptionsFramework-backup-import' ); // Security check.
		
		if ( ! isset( $_FILES['OptionsFramework-import-file'] ) ) { return; } // We can't import the settings without a settings file.
		
		// Extract file contents
		$upload = file_get_contents( $_FILES['OptionsFramework-import-file']['tmp_name'] );
		
		// Decode the JSON from the uploaded file
		$datafile = json_decode( $upload, true );
		
		// Check for errors
		if ( ! $datafile || $_FILES['OptionsFramework-import-file']['error'] ) {
			wp_redirect( admin_url( 'admin.php?page=' . $this->token . '&error=true' ) );
			exit;
		}
		
		// Make sure this is a valid backup file.
		if ( ! isset( $datafile['OptionsFramework-backup-validator'] ) ) {
			wp_redirect( admin_url( 'admin.php?page=' . $this->token . '&invalid=true' ) );
			exit;
		} else {
			unset( $datafile['OptionsFramework-backup-validator'] ); // Now that we've checked it, we don't need the field anymore.
		}

		
		// Get the theme name from the database.
		$optionsframework_data = get_option('optionsframework');
		$optionsframework_name = $optionsframework_data['id'];
		//$optionsframework_name = get_option( $optionsframework_name );
		
		// Update the settings in the database
		if ( update_option( $optionsframework_name, $datafile ) ) {
		
		// Redirect, add success flag to the URI
			wp_redirect( admin_url( 'admin.php?page=' . $this->token . '&imported=true' ) );
			exit;
		} else {
		// Errors: update fail
			var_dump($optionsframework_name);
			wp_redirect( admin_url( 'admin.php?page=' . $this->token . '&error=true' ) );
			exit;
		}
		
	} // End import()



	
	/**
	 * export()
	 *
	 * Export settings to a backup file.
	 *
	 * @since 1.0.0
	 * @uses global $wpdb
	 */
	 
	function export() {
		global $wpdb;
		check_admin_referer( 'OptionsFramework-backup-export' ); // Security check.
		
		$optionsframework_settings = get_option('optionsframework');
		$database_options = get_option( $optionsframework_settings['id'] );
		
		// Error trapping for the export.
		if ( $database_options == '' ) {
			wp_redirect( admin_url( 'admin.php?page=' . $this->token . '&error-export=true' ) );
			return;
		}
		
		if ( ! $database_options ) { return; }
	
		// Add our custom marker, to ensure only valid files are imported successfully.
		$database_options['OptionsFramework-backup-validator'] = date( 'Y-m-d h:i:s' );
	
		// Generate the export file.
	    $output = json_encode( (array)$database_options );
	
	    header( 'Content-Description: File Transfer' );
	    header( 'Cache-Control: public, must-revalidate' );
	    header( 'Pragma: hack' );
	    header( 'Content-Type: text/plain' );
	    header( 'Content-Disposition: attachment; filename="' . $this->token . '-' . date( 'Ymd-His' ) . '.json"' );
	    header( 'Content-Length: ' . strlen( $output ) );
	    echo $output;
	    exit;
			
	} // End export()

} // End Class

/**
 * Create $woo_backup Object.
 *
 * @since 1.0.0
 * @uses OptionsFramework_Backup
 */

$of_backup = new OptionsFramework_Backup();
$of_backup->init();
?>