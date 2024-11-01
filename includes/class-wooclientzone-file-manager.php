<?php

/**
 * The file manager of the plugin.
 *
 * @link	   http://blendscapes.com
 * @since	  1.0.0
 *
 * @package	Wooclientzone
 * @subpackage Wooclientzone/includes
 */

/**
 * The file manager of the plugin.
 *
 * This class is responsible for managing upload and download of files,
 * as well as generating list of files as objects to be sent json-encoded
 *
 * @package	Wooclientzone
 * @subpackage Wooclientzone/includes
 * @author	 Enrico Sandoli <enrico.sandoli@blendscapes.com>
 */

class Wooclientzone_File_Manager {

	/**
	 * The ID of this plugin.
	 *
	 * @since	1.0.0
	 * @access   private
	 * @var	  string	$plugin_name	The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since	1.0.0
	 * @access   private
	 * @var	  string	$version	The current version of this plugin.
	 */
	private $version;

	/**
	 * The name of the file containing the client's last access.
	 *
	 * @since	1.0.0
	 * @access   private
	 * @var	  string	$client_lastaccess_file
	 */
	private $client_lastaccess_file;

	/**
	 * The name of the file containing the admin's last access.
	 *
	 * @since	1.0.0
	 * @access   private
	 * @var	  string	$admin_lastaccess_file
	 */
	private $admin_lastaccess_file;

	/**
	 * The name of the file containing the client's permissions.
	 *
	 * @since	1.0.0
	 * @access   private
	 * @var	  string	$status_file
	 */
	private $status_file;

	/**
	 * The format for the dates under the communications bubbles.
	 *
	 * @since	1.0.0
	 * @access   private
	 * @var	  string	$date_format
	 */
	private $date_format;

	/**
	 * A property signalling the file manager is busy.
	 *
	 * @since	1.0.0
	 * @access   private
	 * @var	  bool	$is_busy	The tools class instance of this plugin.
	 */
	private $is_busy;

	/**
	 * The tools private property.
	 *
	 * @since	1.0.0
	 * @access   private
	 * @var		  object		$tools		An object with utility methods.
	 */
	private $tools;

	/**
	 * The url address of the ajax endpoint.
	 *
	 * @since	1.0.0
	 * @var	  string	$ajaxurl
	 */
	public $ajaxurl;

	/**
	 * The name of the file containing PDF icon.
	 *
	 * @since	1.0.0
	 * @var	  string	$pdf_icon
	 */
	public $pdf_icon;

	/**
	 * The name of the file containing a generic file icon.
	 *
	 * @since	1.0.0
	 * @var	  string	$pdf_icon
	 */
	public $file_icon;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since	1.0.0
	 * @param	  string	$plugin_name	   The name of this plugin.
	 * @param	  string	$version	The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->is_busy = false;
		$this->ajaxurl = admin_url('admin-ajax.php');
		$this->icon_pdf  = plugin_dir_url( dirname( __FILE__ ) ) . 'media/pdf-icon.png';
		$this->icon_file = plugin_dir_url( dirname( __FILE__ ) ) . 'media/file-icon.png';
		$this->client_lastaccess_file = '.client_lastaccess';
		$this->admin_lastaccess_file  = '.admin_lastaccess';
		$this->status_file = '.wooclientzone_status';
		$this->date_format = get_option('wooclientzone_date_format');
		$this->tools = new Wooclientzone_Tools($plugin_name, $version);
	}

	/**
	 * Set the file manager as busy
	 *
	 * @since	1.0.0
	 */
	public function do_busy() {
		$this->is_busy = true;
	}

	/**
	 * Set the file manager as not busy
	 *
	 * @since	1.0.0
	 */
	public function undo_busy() {
		$this->is_busy = false;
	}

	/**
	 * Check if the file manager is busy
	 *
	 * @since	1.0.0
	 */
	public function is_busy() {
		return $this->is_busy;
	}

	/**
	 * Create an array to be passed to the public js script, with those options
	 * that need to be set from the server
	 *
	 * @since	1.0.0
	 */
	public function get_public_js_options() {

		$orderid = isset($_GET['orderid']) ? absint($_GET['orderid']) : false;

		$accepted_files = get_option('wooclientzone_accepted_files_public');
		$max_filesize = get_option('wooclientzone_max_filesize_public');
		$refresh_rate = get_option('wooclientzone_refresh_rate');

		$nonce_load_communications = wp_create_nonce('wooclientzone_load_communications_public');
		$nonce_submit_message = wp_create_nonce('wooclientzone_submit_message_public');
		$nonce_my_account_notifications = wp_create_nonce('wooclientzone_my_account_notifications');

		$options = array(
			'orderid'							=> $orderid,
			'ajaxurl'							=> $this->ajaxurl,
			'dictDefaultMessage'				=> __('Click or drop your files here', 'wooclientzone'),
			'accepted_files'					=> $accepted_files,
			'max_filesize'						=> $max_filesize,
			'refresh_rate'						=> $refresh_rate,
			'loading_previous_communications'   => __('Loading ...'),
			'load_more_previous_communications' => __('Click to load more previous communications'),
			'nonce_load_communications'			=> $nonce_load_communications,
			'nonce_submit_message'				=> $nonce_submit_message,
			'nonce_my_account_notifications'	=> $nonce_my_account_notifications,
		);
		return $options;
	}

	/**
	 * Create an array to be passed to the admin js script, with those options
	 * that need to be set from the server
	 *
	 * @since	1.0.0
	 */
	public function get_admin_js_options() {

		$userid = isset($_GET['userid']) ? absint($_GET['userid']) : false;
		$orderid = isset($_GET['orderid']) ? absint($_GET['orderid']) : false;

		$accepted_files = get_option('wooclientzone_accepted_files_admin');
		$max_filesize = get_option('wooclientzone_max_filesize_admin');
		$refresh_rate = get_option('wooclientzone_refresh_rate');

		$nonce_load_communications = wp_create_nonce('wooclientzone_load_communications_admin');
		$nonce_submit_message = wp_create_nonce('wooclientzone_submit_message_admin');

		$nonce_notify_client = wp_create_nonce('wooclientzone_notify_client');
		$nonce_save_client_permissions = wp_create_nonce('wooclientzone_save_client_permissions');
		$nonce_move_clientzone = wp_create_nonce('wooclientzone_move_clientzone');

		$nonce_admin_widget_notifications = wp_create_nonce('wooclientzone_admin_widget_notifications');

//		$rating_required = false; // uncomment here to enable rating link ! get_option('wooclientzone_rating_done') ? true : false;
		$rating_required = ! get_option('wooclientzone_rating_done') ? true : false;
		$rating_string = sprintf( __( 'If you like <strong>WooClientZone</strong> please leave us a %s%s%s rating. Thank you so much in advance!', 'wooclientzone' ), '<a href="'.WOOCLIENTZONE_RATING_LINK.'" target="_blank" class="wooclientzone-rating-link">', '&#9733;&#9733;&#9733;&#9733;&#9733;', '</a>' );
		$upgrade_string = sprintf( __( '%sUpgrade now%s for file uploads and more!', 'wooclientzone' ), '<a href="'.WOOCLIENTZONE_UPGRADE_LINK.'" target="_blank">', '</a>' );

		
		$options = array(
			'userid'							=> $userid,
			'orderid'							=> $orderid,
			'ajaxurl'							=> $this->ajaxurl,
			'dictDefaultMessage'				=> __('Click or drop your files here', 'wooclientzone'),
			'accepted_files'					=> $accepted_files,
			'max_filesize'						=> $max_filesize,
			'refresh_rate'						=> $refresh_rate,
			'loading_previous_communications'   => __('Loading ...'),
			'load_more_previous_communications' => __('Click to load more previous communications'),
			'nonce_load_communications'			=> $nonce_load_communications,
			'nonce_submit_message'				=> $nonce_submit_message,
			'nonce_notify_client'				=> $nonce_notify_client,
			'nonce_save_client_permissions'		=> $nonce_save_client_permissions,
			'nonce_move_clientzone'				=> $nonce_move_clientzone,
			'nonce_admin_widget_notifications'  => $nonce_admin_widget_notifications,
			'save_changes_string'				=> __('Save changes', 'wooclientzone'),
			'moving_clientzone_string'			=> __('Moving Client Zone ...', 'wooclientzone'),
			'move_clientzone_string'			=> __('Move Client Zone', 'wooclientzone'),
			'saving_string'						=> __('Saving ...', 'wooclientzone'),
			'notify_client_string'				=> __('Notify Client', 'wooclientzone'),
			'sending_email_string'				=> __('Sending email ...', 'wooclientzone'),
			'thankyou_using_wooclientzone'		=> __('Thank you for using WooClientZone Lite!', 'wooclientzone'),
			'rating_required'					=> $rating_required,
			'rating_string'						=> $rating_string,
			'upgrade_string'					=> $upgrade_string,
			'wooclientzone_docs_link'			=> WOOCLIENTZONE_DOCS_LINK,
			'wooclientzone_docs_text'			=> __('View documentation', 'wooclientzone'),
		);
		return $options;
	}

	/**
	 * This function returns the root folder as defined in the plugin's back end parameters.
	 *
	 * This methods returns a standard object containing a path and a url for the root folder.
	 * If outside the web root the url is set as false (TODO DISABLED for now)
	 *
	 * @since	1.0.0
	 * @access   private
	 * @return	object
	 */
	private function get_root_folder() {

		$filerepo = get_option('wooclientzone_root_folder');

		// TODO for the time being we assume that the root folder is relative to the web root
		// $is_relative = get_option('wooclientzone_root_is_relative');
		$is_relative = 'yes';

		// create response object
		$response = new stdClass();
		$response->error = false;

		// reject if filerepo contains a double dot, or if the backend parameters are not defined
		if (!$filerepo || !$is_relative || strpos($filerepo, '..')) {
			$this->tools->log(__('The root folder was not found in the back end settings or was found to contain illegal characters', 'wooclientzone'), WOOCLIENTZONE_LOG_ALERT);
			$response->error = true;
			return $response;
		}

		// remove any spaces and other characters, as well as any trailing slash from filerepo
		$filerepo = trim($filerepo);
		$filerepo = rtrim($filerepo, DIRECTORY_SEPARATOR);

		// now set the response values
		if ($is_relative == 'yes') {
			$response->path = get_home_path().$filerepo.DIRECTORY_SEPARATOR;
			$response->url  = get_home_url().DIRECTORY_SEPARATOR.$filerepo.DIRECTORY_SEPARATOR;
		} else {
			// note that currently this is never executed
			// for now set url to false if the path is not relative
			// TODO check whether the path, even if expressed in absolute terms, is still within the web root
			$response->path = $filerepo.DIRECTORY_SEPARATOR;
			$response->url  = false;
		}
		return $response;
	}

	/**
	 * This is where the name of the current folder,  where files should be read and written, is defined.
	 * This depends on the user ID and the order ID. If no user ID is provided, then the current user ID is used.
	 * If there is no order ID we assume we are in the user's common area, otherwise we are inside one user's order folder
	 *
	 * This methods returns a standard object containing a path and a url for the current folder.
	 * If outside the web root the url is set as false (TODO DISABLED for now)
	 *
	 * @since	1.0.0
	 * @access   private
	 * @param	string	$userid		 optional: the user ID for which we are getting the folder
	 * @param	string	$orderid		optional: the order ID for which we are getting the folder
	 * @return	object
	 */
	private function get_current_folder($userid = false, $orderid = false) {

		// create response object
		$response = new stdClass();
		$response->error = false;

		// get the root folder
		$root_folder_object = $this->get_root_folder();

		if ($root_folder_object->error) {
			$response->error = true;
			return $response;
		}

		// now set the name of the current folder by adding a last part which depends on user id / order id

		// if no user ID provided (as when accessing it from admin to view data for a
		// specific user), assume current user (as when accessing it from the front end)
		if (!$userid) {
			$userid = get_current_user_id();
		}
		// all files are within the user ID
		$folder_last_part = 'User ID '.$userid.DIRECTORY_SEPARATOR;
		// specialise order-linked areas or common area
		if ($orderid) {
			$folder_last_part .= 'Order ID '.$orderid;
		}
		else {
			$folder_last_part .= 'Common';
		}

		$response->path = $root_folder_object->path.$folder_last_part.DIRECTORY_SEPARATOR;
		$response->url = $root_folder_object->url ? $root_folder_object->url.$folder_last_part.DIRECTORY_SEPARATOR : false;

		return $response;
	}

	/**
	 * Sets the current folder; we create it only if it doesn't exist.
	 *
	 * @since	1.0.0
	 * @access   private
	 * @param	  string	$current_folder		 the full path as defined by get_current_folder
	 * @return	bool
	 */
	private function create_current_folder($current_folder) {

		if (!$current_folder) {
			return false;
		}

		if (!is_dir($current_folder) && !($rc = @mkdir ($current_folder, 0777, true))) {
			return false;
		}
		return true; // success
	}

	/**
	 * For a given user, submit a new message by putting it in the content of a new file.
	 *
	 * @since	1.0.0
	 * @param	object $args
	 * @return	object
	 */
	public function submit_message($args) {

		$response = new stdClass();
		$response->error = false;

		// this is of course not needed, but it helps document the object elements
		$is_admin = $args->is_admin;
		$userid	  = $args->userid;
		$orderid  = $args->orderid;

		if (!$userid) {
			$response->error = true;
			$response->errorstring = __('No user ID found', 'wooclientzone');
			return $response;
		}
		// sanitize message data with the same sanitization level of a post
		$message = wp_kses_post(stripslashes($_POST['data']));
		if (!$message) {
			$response->error = true;
			$response->errorstring = __('No message found', 'wooclientzone');
			return $response;
		}
		$current_folder = $this->get_current_folder($userid, $orderid);
		if ($current_folder->error) {
			$response->error = true;
			$response->errorstring = __('Error getting current folder name', 'wooclientzone');
			return $response;
		}
		if (!$this->create_current_folder($current_folder->path)) {
			$response->error = true;
			$response->errorstring = __('Error creating the current folder', 'wooclientzone');
			return $response;
		}

		// set filename and create a new file with data as content
		$filename = $this->set_filename_prefix($is_admin, 'message')."msg.txt";
		if (!file_put_contents($current_folder->path.$filename, $message)) {
			$response->error = true;
			$response->errorstring = __('Error saving new message', 'wooclientzone');
			return $response;
		}

		return $response;
	}

	/**
	 * For a given user, load all communications into a stdClass object.
	 *
	 * @since	1.0.0
	 * @param	object $args
	 * @return	object
	 */
	public function load_communications_object($args) {

		// create response object
		$communications = new stdClass();
		$communications->error = false;

		// this is of course not needed, but it helps document the object elements
		$is_admin                        = $args->is_admin;
		$userid                          = $args->userid;
		$orderid                         = $args->orderid;
		$loading_initial_communications  = $args->loading_initial_communications;
		$refreshing                      = $args->refreshing;
		$loading_previous_communications = $args->loading_previous_communications;
		$first_file_timestamp            = $args->first_file_timestamp;

		if (!$userid) {
			$communications->error = true;
			$communications->errorlevel = 'warning';
			$communications->errorstring = __('No user ID found', 'wooclientzone');
			return $communications;
		}
		$current_folder = $this->get_current_folder($userid, $orderid);
		if ($current_folder->error) {
			$communications->error = true;
			$communications->errorlevel = 'warning';
			$communications->errorstring = __('Error getting current folder name', 'wooclientzone');
			return $communications;
		}

		// we now load the last access times from both parties, and as a minimum (even if no (new) files are found) we return them.
		if (is_file($current_folder->path.$this->client_lastaccess_file)) {
			$client_lastaccess = file_get_contents($current_folder->path.$this->client_lastaccess_file);
		} else {
			$client_lastaccess = 0;
		}
		if (is_file($current_folder->path.$this->admin_lastaccess_file)) {
			$admin_lastaccess = file_get_contents($current_folder->path.$this->admin_lastaccess_file);
		} else {
			$admin_lastaccess = 0;
		}
		$communications->client_lastaccess = $client_lastaccess;
		$communications->admin_lastaccess = $admin_lastaccess;

		// we are here, so $current_folder has the absolute path of the current directory;
		// we can now use our get_file_list method (derived from the WP function list_files) to create an array of files

		// now populate the $coomunications object
		//$communications->files = array();

		// if loading only the latest files, define the right argument for get_file_list()
		if ($refreshing) {
			$time_from = $is_admin ? $admin_lastaccess : $client_lastaccess;
		} else {
			$time_from = false;
		}
		
		// if loading the previous files, define the right argument for get_file_list()
		if ($loading_previous_communications) {
			$time_to = $first_file_timestamp;
		} else {
			$time_to = false;
		}

		// get the files | note that get_file_list() method takes the entire object $current_folder
		$response = $this->get_file_list($current_folder, $first_file_timestamp, $time_from, $time_to);

		// if error
		if ($response->error) {
			$communications->error = $response->error;
			$communications->errorlevel = $response->errorlevel;
			$communications->errorstring = $response->errorstring;
			return $communications;
		}

		// load flag for previous communications still available
		$communications->previous_communications_available = $response->previous_communications_available;

		// if files not found
		if (!$response->files) {
			$communications->error = true;
			$communications->errorlevel = $loading_initial_communications ? 'info' : 'silent'; // do not display when refreshing or loading previous items (the latter should not happen)
			$communications->errorstring = __('This communications area is currently empty', 'wooclientzone');
			return $communications;
		}

		// load files 2-dim array onto communications object
		$communications->files = $response->files;
		
		// Finally, as the client will be viewing the communications from the public side, we can write a timestamp to a hidden file, which, when read
		// on the admin side, will provide info about the viewed state of the various communications, with the display of a 'seen' indication below
		// the messages and/or files; and viceversa for when viewing happens from the admin side.

		// we cannot use the WP core function is_admin() because this function returns true for all ajax calls, both client-side and admin-side,
		// so we use this variable which is passed as an argument by the calling party.
		if ($is_admin) {
			$ret = file_put_contents($current_folder->path.$this->admin_lastaccess_file, $response->access_timestamp);
		} else {
			$ret = file_put_contents($current_folder->path.$this->client_lastaccess_file, $response->access_timestamp);
		}

		return $communications;

	}

	/**
	 * Reads the folder files and returns an object containing those files and
	 * the timestamp of access.
	 *
	 * This function is based on WP core function
	 * list_files(), but it builds the file object based on the prefix structure
	 * used by Wooclientzone. If $time_from is not null, we collect just the
	 * files uploaded after the specified timestamp in $time_from.
	 * TODO We could extend this function with a $time_to parameter which would
	 * aide in backward pagination.
	 *
	 * @since	1.0.0
	 * @access   private
	 * @param	  object	$folder		 the full path as defined by get_current_folder
	 * @param     int    $first_file_timestamp   the earlier file timestamp currently displayed (it comes from the Ajax call)
	 * @param	  int	 $time_from	  the minimum timestamp for the upload datetime to retrieve (used when refreshing)
	 * @param	  int	 $time_to	  the maximum timestamp for the upload datetime to retrieve (used when loading previous files)
	 * @return	object
	 */
	private function get_file_list($folder, $first_file_timestamp, $time_from, $time_to) {

		$response = new stdClass();
		$response->error = false;

		if (empty($folder->path)) {
			$response->error = true;
			$response->errorlevel = 'warning';
			$response->errorstring = __('An empty folder was passed', 'wooclientzone');
			return $response;
		}

		// check for dir existence; if it doesn't exist return like no files found
		if (!is_dir($folder->path)) {
			$response->files = false;
			return $response;
		}

		$dir = @opendir( $folder->path );
		if (!$dir) {
			$response->error = true;
			$response->errorlevel = 'warning';
			$response->errorstring = __('Error opening the client area', 'wooclientzone');
			return $response;
		}

		// we build an array of files to be added as a property of the response object
		$files = array();
		$first_upload_timestamp = 9999999999;
		while (($file = readdir($dir)) !== false) {

			// skip refs to current and upper dirs ( '.' and '..')
			if (in_array($file, array('.', '..'))) {
				continue;
			}

			// we don't display folders or hidden files (files beginning with a dot)
			if ( is_dir( $folder->path . $file ) || substr($file, 0, 1) == '.') {
				continue;
			}

			// we check that the file has the right wooclientzone prefix
			if (!$this->validate_file_prefix($file)) {
				continue;
			}

			// now we can get the timestamp based on the file prefix
			$upload_timestamp = $this->get_upload_timestamp($file);
			
			// we store the first timestamp, so that we can later tell if there are still earlier files left
			// note that we do this before filtering the files based on time_from or time_to
			if ($upload_timestamp < $first_upload_timestamp) {
				$first_upload_timestamp = $upload_timestamp;
			}

			// if requested, skip files already seen (we are refreshing)
			if ($time_from && $upload_timestamp <= $time_from) {
				continue;
			}

			// if requested, skip files older than a time_to timestamp (we are loading previous files)
			if ($time_to && $upload_timestamp >= $time_to) {
				continue;
			}

			// we build the file array in a separate private function
			$files[] = $this->get_file_array($file, $folder, $upload_timestamp);
		}
		@closedir( $dir );

		// sort files by name (equivalent to upload time)
		if (count($files) > 1) {
			usort($files, function($a, $b) {
				return strcmp($a['prefixed_name'], $b['prefixed_name']);
			});
		}

		// CHECK IF WE ARE LEAVING EARLIER FILES BEHIND

		// reduce files array to fit with the maximum number of loaded communications at a time (take the last ones and flag accordingly)
		$max_files_count = get_option('wooclientzone_items_loaded', 20);

		// unless no limit is set, only return a subset of the files array to display
		if ($max_files_count > 0) {

			$displayed_files = array();

			// if refreshing we are not touching earlier files, so we just compare timestamps of first valid file and earliest currently displayed one
			// note that we exclude the case of a refresh that shows the first communication (when $first_file_timestamp is 9999999999)
			if ($time_from) {
				$displayed_files = $files;
				$response->previous_communications_available = ($first_upload_timestamp < $first_file_timestamp && $first_file_timestamp != 9999999999) ? true : false;
			}

			// otherwise we check if the number of files exceeds $max_files_count,
			// and in that case we only pick the last ones (and flag there are more available)
			else {
				if (count($files) > $max_files_count) {
					// take only the last $max_files_count elements of the $files array
					$displayed_files = array_slice($files, - $max_files_count);
					$response->previous_communications_available = true;
				} else {
					// take all files
					$displayed_files = $files;
					$response->previous_communications_available = false;
				}
			}
			$response->files = $displayed_files;
		}
		else {
			$response->files = $files;
		}
		
		// and finally return response
		$response->access_timestamp = time();
		return $response;
	}

	/**
	 * Checks if a Client Zone exists.
	 *
	 * @since    1.0.0
	 * @param    int    $userid
	 * @param    int    $orderid (optional)
	 * @return   bool
	 */
	public function clientzone_exists($userid, $orderid = false) {

		$clientzone_folder = $this->get_current_folder($userid, $orderid);
		return is_dir($clientzone_folder->path);
	}

	/**
	 * Moves files from one client zone to another.
	 *
	 * @since    1.0.0
	 * @param    object    $args
	 * @return   object
	 */
	public function move_files_across_clientzones($args) {

		$response = new stdClass();
		$response->error = false;

		// first we get the target folder
		$target_folder = $this->get_current_folder($args->userid, $args->new_orderid);
		if ($target_folder->error) {
			$response->error = true;
			$response->errorstring = __('Error getting target folder name', 'wooclientzone');
			return $response;
		}

		// secondly we get the current folder
		$current_folder = $this->get_current_folder($args->userid, $args->orderid);
		if ($current_folder->error) {
			$response->error = true;
			$response->errorstring = __('Error getting current folder name', 'wooclientzone');
			return $response;
		}

		// we create the target if it doesn't exist
		if (!$this->create_current_folder($target_folder->path)) {
			$response->error = true;
			$response->errorstring = __('Error creating the target folder', 'wooclientzone');
			return $response;
		}

		// ok now let's move the files
		$files_found = $files_moved = 0;
		// this check if for extra precaution only. We should never get here because: when coming from admin to move a zone we would already be
		// in a zone to begin with (even if empty); when automoving, if the zone folder to move from does not exist we stop earlier in the code
		if (is_dir($current_folder->path)) {

			$files = scandir($current_folder->path);
			foreach ($files as $file) {
				if (in_array($file, array(".",".."))) {
					continue;
				}
				// if client permissions file was not to be moved, just delete it silently
				if ($file == $this->status_file && $args->move_permissions == 'false') {
					unlink($current_folder->path.$file);
					continue;
				}
				$files_found++;
				if (copy($current_folder->path.$file, $target_folder->path.$file)) {
					$files_moved++;
					unlink($current_folder->path.$file);
				}
			}
		}

		// return a warning if no files were found or not all files were moved
		if (!$files_found) {
			$response->error = true;
			$response->errorstring = __('Nothing to move!', 'wooclientzone');
			return $response;
		}
		if ($outstanding_files = $files_found - $files_moved) { // yes, we are making an assignment, which returns the value which is tested in the if statement
			$response->error = true;
			$response->errorstring = sprintf(__('%d files could not be moved', 'wooclientzone'), $outstanding_files);
		}
		return $response;
	}

	/**
	 * Get client permissions for a specific client zone.
	 *
	 * @since    1.0.0
	 * @param    int      $userid
	 * @param    int      $orderid
	 * @return   object
	 */
	public function get_client_permissions($userid, $orderid) {

		$response = new stdClass();
		$response->error = false;

		$client_message = $orderid ? get_option('wooclientzone_client_message_orderzones') : get_option('wooclientzone_client_message_userzones');
		$response->message_enabled  = $client_message == 'yes' ? true : false;
		$response->upload_enabled = false;
		return $response;
	}

	/**
	 * Return a readable file size.
	 *
	 * Adapted from php.net filesize function page http://php.net/manual/en/function.filesize.php
	 *
	 * @since    1.0.0
	 * @param    int     $bytes
	 * @return   string
	 */
	public function human_filesize($bytes) {

		$sz = 'BKMGTP';
		$factor = floor((strlen($bytes) - 1) / 3);

		if ($factor < 1) {
			return sprintf("%.0f", $bytes / pow(1024, $factor)) . ' ' . @$sz[$factor];
		} elseif ($factor < 2) {
			return sprintf("%.0f", $bytes / pow(1024, $factor)) . ' ' . @$sz[$factor] . 'B';
		} else {
			return sprintf("%.2f", $bytes / pow(1024, $factor)) . ' ' . @$sz[$factor] . 'B';
		}
	}

	/**
	 * Read a file in chunks.
	 *
	 * This function is reported in readfile() php.net page to bypass readfile() documented problems with large files
	 * TODO this will become private once the calling function is brought into the filemanager (will be used if we implement download by script)
	 *
	 * @since    1.0.0
	 * @param    string  $filename
	 * @param    bool     $retbytes
	 * @return   int (or boolean false)
	 */
	public function readfile_chunked($filename, $retbytes = true) {

		$chunksize = 1 * (1024 * 1024); // how many bytes per chunk
		$buffer = '';
		$counter = 0;

		$handle = fopen($filename, 'rb');
		if ($handle === false)
		{
			return false;
		}

		while (!feof($handle))
		{
			$buffer = fread($handle, $chunksize);
			echo $buffer;
			@ob_flush();
			@flush();

			if ($retbytes)
			{
				$counter += strlen($buffer);
			}
		}

		$status = fclose($handle);

		if ($retbytes && $status)
		{
			return $counter; // return num. bytes delivered like readfile() does.
		}

		return $status;
	}

	// ADMIN DASHBOARD WIDGET METHODS

	/**
	 * Read the latest file in all folders and return unseen data for notifying the admin.
	 *
	 * This method reads all folders and compares the last file in each folder
	 * with the last-access timestamp of the party that did not upload it;
	 * this detects whether the file has been seen. It returns an object that
	 * contains the array of objects notifications_array, which is used by both
	 * admin and public classes to build a notification to the user.
	 *
	 * @since    1.0.0
	 * @param    bool    $limit_to_current_user
	 * @return   object
	 */
	public function get_notifications_data($limit_to_current_user = false) {

		$response = new stdClass();
		$response->error = false;

		if ($limit_to_current_user) {
			$current_userid = get_current_user_id();
		}

		// check if user- and order-based client zones are enabled
		$userzones_enabled = get_option('wooclientzone_use_userzones') == 'yes';
		$orderzones_enabled = get_option('wooclientzone_use_orderzones') != 'never';

		// get the root folder
		$root_folder_object = $this->get_root_folder();

		if ($root_folder_object->error) {
			$response->error = true;
			$response->errorstring = __('Cannot get root folder', 'wooclientzone');
			return $response;
		}

		// we just need the path here
		$root_folder = $root_folder_object->path;

		// if the root folder has not been created (it is created with the first communication)
		// then report back as if there were no notifications to report
		if (!is_dir($root_folder)) {
			$response->error = true;
			$response->errorType = 'info';
			$response->errorstring = __('No unseen communications to report', 'wooclientzone');
			return $response;
		}

		// scan first level (users top folders)
		$notifications_array = array();
		$topfolders = array();
		$userids = array();
		$scan = scandir($root_folder);
		if (!$scan) {
			$response->error = true;
			$response->errorstring = __('Cannot scan top level folder', 'wooclientzone');
			return $response;
		}
		foreach($scan as $item) {
			if (is_dir($root_folder.$item) && substr($item, 0, 8) === "User ID ") {
				$parts = explode(' ', $item);
				if ($limit_to_current_user && $current_userid != $parts[count($parts) - 1]) {
					continue;   // skip other users if only data for the current user is requested
								// this also skips folder such as User ID n.backup
				}
				$userids[] = $parts[count($parts) - 1];
				$topfolders[] = $root_folder.$item.DIRECTORY_SEPARATOR;
			}
		}

		// scan second level to start building the notifications_array with userid, orderid and folder elements
		$j = 0;
		for ($i = 0; $i < count($userids); $i++) {

			// check first Common folder (only if userzones are enabled)
			if ($userzones_enabled && is_dir($topfolders[$i]."Common")) {
				$notifications_array[$j]->userid = $userids[$i];
				$notifications_array[$j]->orderid = '';
				$notifications_array[$j]->folder = $topfolders[$i]."Common".DIRECTORY_SEPARATOR;
				$j++;
			}

			// skip reading order dirs if orderzones are not enabled
			if (!$orderzones_enabled) {
				continue;
			}

			// now scan user dir for order dirs
			$scan = scandir($topfolders[$i]);
			if (!$scan) {
				$response->error = true;
				$response->errorstring = sprintf(__('Cannot scan user #%s toplevel folder', 'wooclientzone'), $userids[$i]);
				return $response;
			}

			foreach($scan as $item) {
				if (is_dir($topfolders[$i].$item) && substr($item, 0, 9) === "Order ID ") {
					$parts = explode(' ', $item);
					$notifications_array[$j]->userid = $userids[$i];
					$notifications_array[$j]->orderid = $parts[count($parts) - 1];
					$notifications_array[$j]->folder = $topfolders[$i].$item.DIRECTORY_SEPARATOR;
					$j++;
				}
			}
		}

		// now we can check each individual folder (note that we are iterating on a reference, hence we are changing the array)
		$notification_present = false;
		foreach($notifications_array as &$item) {

			// we only need to compare the last uploaded communications file with the other party last access time
			$scan = scandir($item->folder, SCANDIR_SORT_DESCENDING);
			if (!$scan) {
				$response->error = true;
				if ($item->orderid) {
					$response->errorstring = sprintf(__('Cannot scan user #%s folder for order #%s', 'wooclientzone'), $item->userid, $item->orderid);
				} else {
					$response->errorstring = sprintf(__('Cannot scan user #%s folder', 'wooclientzone'), $item->userid);
				}
				return $response;
			}
			foreach($scan as $file) {
				if ($this->validate_file_prefix($file)) {
					// this is the last uploaded communications file (we read the folder in descending order)
					$item->file = $file;
					break;
				}
			}
			// if no communications file was found then the directory is empty and we don't flag anything
			// (the calling function will not generate any flag)
			if (!$item->file) {
				continue;
			}

			// if we got here a communication file was found (the folder is not empty of communications files), and we make first a quick check on the last access files:
			// if one of those files does not exist (one must be there as there is at least one communications file) then the other party has not seen any files
			$client_lastaccess = file_get_contents($item->folder.$this->client_lastaccess_file);
			$admin_lastaccess = file_get_contents($item->folder.$this->admin_lastaccess_file);
			//
			if (!$client_lastaccess) {
				$item->client_unseen = true;
				$notification_present = true;
				continue;
			}
			if (!$admin_lastaccess) {
				$item->admin_unseen = true;
				$notification_present = true;
				continue;
			}

			// if we got here a (last) file was found and we check its upload time against the last access time of the party that did not upload it
			// note that we pass false to the get_upload_timestamp method to skip the sanity check, as we have already done it (see above) on this file
			$upload_timestamp = $this->get_upload_timestamp($item->file);
			if ($this->get_file_origin($item->file) == 'public' && $upload_timestamp > $admin_lastaccess) {
				// it's a client file older than the admin last access time
				$item->admin_unseen = true;
				$notification_present = true;
			} else if ($this->get_file_origin($item->file) == 'admin' && $upload_timestamp > $client_lastaccess) {
				// it's a client file older than the admin last access time
				$item->client_unseen = true;
				$notification_present = true;
			}
		}

		// check if notifications are present
		if (!$notification_present) {
			$response->error = true;
			$response->errorType = 'info';
			$response->errorstring = __('No unseen communications to report', 'wooclientzone');
			return $response;
		}

		// return response with the notifications array
		$response->notifications_array = $notifications_array;

		// TEST data
//		$response->notifications_array = array();
//		$response->notifications_array[] = (object)array( 'client_unseen' => true, 'userid' => 1, 'orderid' => 264 );
//		$response->notifications_array[] = (object)array( 'admin_unseen' => true, 'userid' => 1, 'orderid' => 261 );
//		$response->notifications_array[] = (object)array( 'admin_unseen' => true, 'userid' => 1, 'orderid' => '' );

		return $response;
	}

	// METHODS TO MANAGE FILE PREFIXES AND FILE NAMING

	/**
	 * Validates the file based on its prefix.
	 *
	 * We perform a check on the prefix, which should be numbers; note that
	 * we do a loose check on these characters being number, for simplicity
	 * we don't check if they are actual valid date/time numbers.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    string   $file
	 * @return   bool
	 */
	private function validate_file_prefix($file) {

//		if (preg_match('/^\d{14}/', $file)) {

		// futureproof: to add more flags, we need to modify this regex with
		// (e.g.) [XY]? after [MF] (the '?' to make it backward compatible)

		if (preg_match('/^\d{14}-[AP][MF]-\d{3}_msg.txt/', $file)) {
			return true;
		}
		return false;
	}

	/**
	 * Get the upload timestamp of a file based on its prefix.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    string   $file
	 * @return   int
	 */
	private function get_upload_timestamp($file) {

		$year   = substr($file, 0, 4);
		$month  = substr($file, 4, 2);
		$day    = substr($file, 6, 2);
		$hour   = substr($file, 8, 2);
		$minute = substr($file, 10, 2);
		$second = substr($file, 12, 2);

		return mktime($hour, $minute, $second, $month, $day, $year);
	}

	/**
	 * Populate an array with file data, mainly based on its prefix.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    string   $file
	 * @param    object   $folder
	 * @param    int      $upload_timestamp
	 * @return   array
	 */
	private function get_file_array($file, $folder, $upload_timestamp) {

		$files = array();

		// we calculate the file name start position as it may change in the
		// future if we want to add extra flags before the '_' sign,
		// which we use at the character marking the end of the prefix
		$filename_start_pos = strpos($file, '_') + 1;

		$files['prefixed_name'] = $file;
		$files['name'] = substr($file, $filename_start_pos);
		$files['url'] = $folder->url ? $folder->url.$file : false;
		$files['origin'] = $this->get_file_origin($file);
		$files['is_message'] = (substr($file, 16, 1) == 'M' ? true : false);

		// futureproof: here we would read a future flag; for backward compatibility
		// we would need to check for its existence based on the value of $filename_start_pos

		$files['message'] = ($files['is_message'] ? file_get_contents($folder->path.$file) : '');
		$files['type'] = mime_content_type($folder->path.$file);
		$files['upload_timestamp'] = $upload_timestamp;
		$files['upload_date'] = date($this->date_format, $upload_timestamp);

		return $files;
	}

	/**
	 * Set a file prefix.
	 *
	 * We create a prefix based on the current timestamp, whether it is a
	 * file uploaded by the merchant or the client, and whether the file
	 * is meant to be an attachment file or a messaging file.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    bool     $is_admin
	 * @param    string   $type
	 * @return   string
	 */
	private function set_filename_prefix($is_admin, $type) {

		// we are using prefix format (regex): \d{14}-[AP][MF]-\d{3}_
		$prefix = date('YmdHis', time());
		$prefix .= "-";
		$prefix .= $is_admin ? 'A' : 'P';
		$prefix .= $type == 'message' ? 'M' : 'F';

		// futureproof: to add more flags, we need to modify the regex in
		// validate_file_prefix() as described therein, and add the flag below
		// (e.g.) $prefix .= (condition) ? 'X' : 'Y';
		// we would also need to modify get_file_array() to read the new flag,
		// and for backward compatibility we would need to check the value of
		// $filename_start_pos before attempting to read the new flag

		// we add a random element to make it more difficult to link directly to this resource
		$prefix .= "-".rand(100, 999)."_";

		return $prefix;
	}

	/**
	 * Gets the originating party of a file, based on its prefix.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @param    string   $file
	 * @return   string
	 */
	private function get_file_origin($file) {

		if (substr($file, 15, 1) === 'A') {
			return 'admin';
		}
		return 'public';
	}


}
