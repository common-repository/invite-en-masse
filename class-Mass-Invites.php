<?php
/*
Plugin Name: Invite En Masse
Plugin URI: http://simonwheatley.co.uk/wordpress/invite-en-masse
Description: Send out masses of invites by uploading a CSV file of names and emails, these people will be sent an invitation email to register on your site.
Author: Simon Wheatley
Version: 1.0
Author URI: http://simonwheatley.co.uk/wordpress/
*/

/*  Copyright 2009 Simon Wheatley

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*/

require_once( dirname (__FILE__) . '/class-Mass-Invites-Plugin.php' );

/**
 *
 * @package default
 * @author Simon Wheatley
 **/
class MassInvites extends MassInvites_Plugin
{

	protected $invites;
	protected $rejects;
	
	protected $email_subject;
	
	public function __construct()
	{
		if ( is_admin() ) {
			// Activate
			$this->register_activation ( __FILE__ );
			// Deactivate
			$this->register_deactivation ( __FILE__ );
			// Admin menu
			$this->add_action( 'admin_menu' );
			// On load of the Create Invitations page
			$this->add_action( 'users_page_create-invites', 'load_create_invitations' );
			// On load of the Invitations page
			$this->add_action( 'users_page_invites', 'load_invitations' );
		}
		// Register and stuff
		$this->register_plugin ( 'mass-invites', __FILE__ );
		// Add stuff into the login header
		$this->add_action( 'login_head' );
		// Add in our login message
		$this->add_filter( 'login_message' );
		// Vars and stuff
		$this->invites = array();
		$this->rejects = array();
		$this->email_subject = "Invitation to join %blogname%";
	}
	
	// HOOKS
	// -----
	
	public function activate()
	{
		require_once( 'model/database.php' );
		$db = new MassInvites_Database();
		$db->install();
	}
	
	public function deactivate()
	{
		require_once( 'model/database.php' );
		$db = new MassInvites_Database();
		$db->uninstall();
	}
	
	public function admin_menu()
	{
		add_submenu_page( 'users.php', __( 'Create Invitations', 'mass-invites' ), __( 'Create Invitations', 'mass-invites' ), 'create_users', 'create-invites', array( & $this, 'page_create_invitations' )  );
		add_submenu_page( 'users.php', __( 'Invitations', 'mass-invites' ), __( 'Invitations', 'mass-invites' ), 'create_users', 'invites', array( & $this, 'page_invitations' )  );
	}
	
	public function login_head()
	{
		$this->process_invite();
		// Print some CSS to hide the main form if this is an invite
		$invite = (bool) @ $_GET[ 'mi_invite_key' ];
		if ( $invite ) $this->render_login_head_tpl();
	}
	
	public function login_message( $message )
	{
		// Check for an invite key, and return whatever message if it
		// doesn't exist.
		if ( ! $this->invite_key_passed() ) return $message;
		// Otherwise, if there is an invite key, then return our message
		$login_url = wp_login_url();
		return '<div class="message">' . __( 'Thank you for accepting our invitation. Please check your email, we have sent you your username and password, now check your email and then visit the <a href="' . $login_url . '">login page</a>.', 'mass-invites' ) . '</div>';
	}
	
	// Admin pages
	
	public function load_create_invitations()
	{
		$this->process_file_upload();
	}
	
	public function page_create_invitations()
	{
		$no_invite_link = false;
		$email_text = stripslashes( @ $_POST[ 'mi_email_text' ] );
		if ( $email_text && ! $this->email_contains_invite_link() ) $no_invite_link = true;
		if ( ! $email_text ) $email_text = file_get_contents( $this->dir() . '/email.txt' );

		$email_subject = stripslashes( @ $_POST[ 'mi_email_subject' ] );
		if ( ! $email_subject ) $email_subject = $this->email_subject;

		$vars = array();
		$vars[ 'example_file' ] = $this->url() . '/example.csv';
		$vars[ 'invites' ] = $this->invites;
		$vars[ 'rejects' ] = $this->rejects;
		$vars[ 'email_subject' ] = esc_attr( $email_subject );
		$vars[ 'email_text' ] = format_to_edit( $email_text );
		$vars[ 'no_invite_link' ] = $no_invite_link;
		$this->render_admin( 'create-invitations', $vars );
	}
	
	public function load_invitations()
	{
		$this->process_deletions();
	}
	
	public function page_invitations()
	{
		$vars = array();
		$vars[ 'invites' ] = $this->get_all_invites();
		$this->render_admin( 'invitations', $vars );
	}
	
	// UTILITIES
	// ---------
	
	protected function email_contains_invite_link()
	{
		$email_text = @ $_POST[ 'mi_email_text' ];
		return ( stripos( $email_text, '%inviteurl%' ) !== false );
	}
	
	
	protected function replace_tokens( $text, $name, $email, $username, $inviteurl )
	{
		// Replacement values
		$blogname = get_bloginfo( 'name' );
		$blogurl = get_bloginfo( 'url' );
		// Make up the array args for str_replace
		$search = array( '%name%', '%email%', '%username%', '%inviteurl%', '%blogname%', '%blogurl%' );
		$replace = array( $name, $email, $username, $inviteurl, $blogname, $blogurl );
		return str_replace( $search, $replace, $text );
	}
	
	protected function process_file_upload()
	{
		$stuff_to_do = (bool) @ $_POST[ 'mi_file_upload' ];
		if ( ! $stuff_to_do ) return;
		// Are we authorised to do anything?
		$this->verify_nonce( 'mi_create_invites' );

		$tmp_filename = @ $_FILES['mi_csv_file']['tmp_name'];
		// Check this is a actual successfully uploaded file
		if ( ! is_uploaded_file( $tmp_filename ) ) return;
		
		// Check for errors in the upload
		$upload_error = @ $_FILES['userphoto_image_file']['error'];
		if ( $upload_error ) {
			// SWTODO: Pass on meaningful upload errors to user
			return;
		}
		
		// All looking OK. Let's load the invitations
		$this->parse_csv_to_invites( $tmp_filename );
		
		// Unlink temporary file for completeness.
		unlink( $tmp_filename );
	}
	
	protected function parse_csv_to_invites( $filename )
	{
		$handle = fopen( $filename, "r" );
		while ( ( $row = fgetcsv( $handle, 1000, "," ) ) !== FALSE ) {
			$this->create_invite( $row[ 0 ], $row[ 1 ], $row[ 2 ], $row[ 3 ] );
		}
		fclose( $handle );
	}
	
	protected function create_invite( $email, $first_name, $last_name, $username )
	{
		global $wpdb;
		// Ensure that neither user nor invite exists for the email address
		if ( $this->invite_exists_by_email( $email ) ) {
			$reason = "Invite for this email already exists";
			$this->record_reject( $reason, $email, $first_name, $last_name, $username );
			return;
		}
		if ( $this->user_exists_by_username( $username ) ) {
			$reason = "User with this username already exists";
			$this->record_reject( $reason, $email, $first_name, $last_name, $username );
			return;
		}
		if ( $this->user_exists_by_email( $email ) ) {
			$reason = "User with this email already exists";
			$this->record_reject( $reason, $email, $first_name, $last_name, $username );
			return;
		}
		// Now create the invite
		$key = md5( uniqid( mt_rand(), true ) );
		$invite_table = $wpdb->prefix . "mi_invitations";
		$sql  = " INSERT INTO $invite_table ( `email`, `first_name`, `last_name`, `username`, `key`, `created_date` ) ";
		$sql .= " VALUES ( %s, %s, %s, %s, %s, NOW() ) ";
		$prepared_sql = $wpdb->prepare( $sql, $email, $first_name, $last_name, $username, $key );
		$wpdb->query( $prepared_sql );
		$this->send_invite( $email, $first_name, $last_name, $key );
		$this->record_invite( $email, $first_name, $last_name, $username );
	}
	
	protected function user_exists_by_email( $email )
	{
		global $wpdb;
		$sql = " SELECT ID FROM $wpdb->users WHERE user_email = %s ";
		$prepared_sql = $wpdb->prepare( $sql, $email );
		return ( bool ) $wpdb->get_var( $prepared_sql );
	}
	
	protected function user_exists_by_username( $username )
	{
		global $wpdb;
		$sql = " SELECT ID FROM $wpdb->users WHERE user_login = %s ";
		$prepared_sql = $wpdb->prepare( $sql, $username );
		return ( bool ) $wpdb->get_var( $prepared_sql );
	}
	
	protected function invite_exists_by_email( $email )
	{
		global $wpdb;
		$invite_table = $this->invite_table();
		$sql = " SELECT ID FROM $invite_table WHERE email = %s ";
		$prepared_sql = $wpdb->prepare( $sql, $email );
		return ( bool ) $wpdb->get_var( $prepared_sql );
	}
	
	protected function invite_table()
	{
		global $wpdb;
		return $wpdb->prefix . "mi_invitations";
	}
	
	protected function record_reject( $reason, $email, $first_name, $last_name, $username )
	{
		$this->rejects[] = array( 'reason' => $reason, 'email' => $email, 'first_name' => $first_name, 'last_name' => $last_name, 'username' => $username );
	}
	
	protected function send_invite( $email, $first_name, $last_name, $key )
	{
		// Setup
		$name = "$first_name $last_name";
		$to = " \"$name\" <$email> ";
		$inviteurl = $this->create_invite_url( $key );
		// Get the subject
		$subject_raw = stripslashes( @ $_POST[ 'mi_email_subject' ] );
		if ( ! $subject_raw ) $subject_raw = $this->email_subject;
		// Populate the tokens in the subject
		$subject = $this->replace_tokens( $subject_raw, $name, $email, $username, $inviteurl );
		// Get the body
		$body_raw = stripslashes( @ $_POST[ 'mi_email_text' ] );
		// Populate the tokens in the body
		$body = $this->replace_tokens( $body_raw, $name, $email, $username, $inviteurl );
		// Headers and that
		if ( defined( 'MI_FORCE_EMAIL' ) ) {
			$headers = "X-WAS-TO: $to";
			$to = MI_FORCE_EMAIL;
		}
		// Last but not least, send the email
		@ wp_mail( $to, $subject, $body, $headers);
	}
	
	protected function record_invite( $email, $first_name, $last_name, $username )
	{
		$this->invites[] = array( 'email' => $email, 'first_name' => $first_name, 'last_name' => $last_name, 'username' => $username );
	}
	
	protected function invite_key_passed()
	{
		return ( bool ) @ $_GET[ 'mi_invite_key' ];
	}
	
	protected function render_login_head_tpl()
	{
		$vars = array();
		$this->render_admin( 'login-head', $vars );
	}
	
	protected function create_user_from_invitation( $invite )
	{
		require_once( ABSPATH . WPINC . '/registration.php' );
		$password = wp_generate_password();
		// Set the feed author name as the display name in usermeta
		$user_id = wp_create_user( $invite->username, $password, $invite->email );
		$userdata = array( 'ID' => $user_id );
		$userdata[ 'display_name' ] = $invite->first_name . " " . $invite->last_name;
		$userdata[ 'first_name' ] = $invite->first_name;
		$userdata[ 'last_name' ] = $invite->last_name;
		$updated_user_id = wp_update_user( $userdata );
		if ( $user_id != $updated_user_id ) throw new exception( "User ID of updated user does not match that of the previously created user." );
		// Now set the role to subscriber
		$user_obj = new WP_User( $user_id );
		$user_obj->set_role( 'subscriber' );
		// Send the notification email
		wp_new_user_notification( $user_id, $password );
		// Job jobbed.
	}
	
	protected function get_requested_invite()
	{
		global $wpdb;
		$key = @ $_GET[ 'mi_invite_key' ];
		$invite_table = $this->invite_table();
		$sql = " SELECT * FROM $invite_table WHERE `key` = %s ";
		$prepared_sql = $wpdb->prepare( $sql, $key );
		return $wpdb->get_row( $prepared_sql );
	}
	
	protected function delete_requested_invite()
	{
		global $wpdb;
		$key = @ $_GET[ 'mi_invite_key' ];
		$invite_table = $this->invite_table();
		$sql = " DELETE FROM $invite_table WHERE `key` = %s ";
		$prepared_sql = $wpdb->prepare( $sql, $key );
		return $wpdb->query( $prepared_sql );
	}
	
	protected function process_invite()
	{
		if ( ! $this->invite_key_passed() ) return;
		$invite = $this->get_requested_invite();
		if ( empty( $invite ) ) wp_die( __( 'We could not find your invitation, sorry. Maybe you have already clicked on the invitation link?', 'mass-invite' ) );
		// Ensure that a user with the same username or email does not exist
		$reason = __( "Sorry, we could not create your user. Please contact the site owner" , 'mass-invites');
		if ( $this->user_exists_by_username( $invite->username ) ) wp_die( $reason );
		if ( $this->user_exists_by_email( $invite->email ) ) wp_die( $reason );
		// Create the user
		$this->create_user_from_invitation( $invite );
		// Delete the invite now we've used it
		$this->delete_requested_invite();
	}
	
	protected function get_all_invites()
	{
		global $wpdb;
		$invite_table = $this->invite_table();
		$sql = " SELECT * FROM $invite_table ";
		return $wpdb->get_results( $sql );
	}
	
	protected function create_invite_url( $key )
	{
		$url = site_url( 'wp-login.php' );
		$args = array( 'mi_invite_key' => $key );
		$url = add_query_arg( $args, $url );
		return $url;
	}
	
	protected function process_deletions()
	{
		global $wpdb;
		$stuff_to_do = ( @ $_POST[ 'action' ] == 'delete_invites' || @ $_POST[ 'action2' ] == 'delete_invites' );
		if ( ! $stuff_to_do ) return;
		// Are we authorised to do anything?
		$this->verify_nonce( 'mi_delete_invites' );
		// OK. Now let's do it.
		$invites = (array) @ $_POST[ 'mi_invites' ];
		// Ensure all the IDs are integers
		foreach ( $invites as $i => $id ) {
			$invites[ $i ] = absint( $id );
		}
		$ids = join( ',', $invites );
		$invite_table = $this->invite_table();
		$sql = " DELETE FROM $invite_table WHERE id IN ( $ids ) ";
		$wpdb->query( $sql );
	}

	protected function verify_nonce( $action )
	{
		$nonce = @ $_POST[ '_mass_invites_nonce' ];
		if ( wp_verify_nonce( $nonce, $action ) ) return true;
//		throw new exception( "Wrong wrong wrong $action, $nonce" );
		wp_die( __('Sorry, there has been an error. Please hit the back button, refresh the page and try again.') );
		exit; // Redundant, unless wp_die fails.
	}

}

/**
 * Instantiate the plugin
 *
 * @global
 **/

$mass_invites = new MassInvites();

?>