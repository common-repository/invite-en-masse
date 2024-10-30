<?php if (!defined ('ABSPATH')) die ('No direct access allowed'); ?>

<div class="wrap" id="lifespan">

	<div id="icon-users" class="icon32"><br /></div>
	<h2><?php _e( 'Create Invitations', 'mass-invites' ); ?></h2>
	
	<p><strong><?php _e( 'WHEN THIS PLUGIN IS DEACTIVATED ALL EXISTING INVITATIONS ARE DELETED.', 'mass-invites' ); ?></strong></p>
	
	<p><?php echo sprintf( __( 'You can create invitations by uploading a CSV file with the following column order and no header row (download a <a href="%s">sample file</a>):', 'mass-invites' ), $example_file ); ?></p>
	
	<ol>
		<li><?php _e( 'Email', 'mass-invites' ); ?></li>
		<li><?php _e( 'First Name', 'mass-invites' ); ?></li>
		<li><?php _e( 'Last Name', 'mass-invites' ); ?></li>
		<li><?php _e( 'Username', 'mass-invites' ); ?></li>
	</ol>
	
	<p><?php _e( 'If a user with the same email already exists in this site then no new user will be created. If the username as already been used, then no user will be created. Once the invitations have been created the details of any users not created will be shown so you can make amendments are re-upload if you wish.', 'mass-invites' ); ?></p>

	<form method="post" action="" enctype='multipart/form-data'>
		<input name="mi_file_upload" type="hidden" value="1" />
		<?php wp_nonce_field( 'mi_create_invites', '_mass_invites_nonce' ); ?>
		
		<table class="form-table">
			<!-- CSV File -->
			<tr valign="top">
				<th scope="row"><label for="mi_csv_file"><?php _e( 'CSV File', 'mass-invites' ); ?></label></th>
				<td>
					<input name="mi_csv_file" type="file" id="mi_csv_file" />
				</td>
			</tr>
			<!-- Email Subject -->
			<tr>
				<th><label for="mi_email_subject">Email Subject</label></th>
				<td>
				<?php if ( $no_subject ) { ?>
					<p class="error"><?php _e( 'You must include a subject for your email. Please type a subject, re-upload your CSV and try again.', 'mass-invites' ); ?></p>
				<?php } ?>	
				<input type="text" class="regular-text" name="mi_email_subject" value="<?php echo $email_subject; ?>" id="mi_email_subject" /><br/>
				<span class="mi_email_subject"><?php _e( 'There are some tokens you can use in the email subject which will be dynamically replaced: <code>%inviteurl%</code> (<strong>this token is required</strong>, it is replaced by the link the invitee clicks on to accept the invitation), <code>%blogurl%</code> (the web address for your blog), <code>%blogname%</code> (the name of your blog), <code>%email%</code> (the email address of the invitee), <code>%name%</code> (the name of the invitee), <code>%username%</code> (the username you have chosen for the invitee).', 'mass-invites' ); ?>.</span></td>
			</tr>
			<!-- Email text -->
			<tr>
				<th><label for="mi_email_text">Email Text</label></th>
				<td>
				<?php if ( $no_invite_link ) { ?>
					<p class="error"><?php _e( 'You must include the token for the invite URL (%inviteurl%) in your email text. Please amend the email text, re-upload your CSV and try again.', 'mass-invites' ); ?></p>
				<?php } ?>	
				<textarea cols="50" rows="10" id="mi_email_text" name="mi_email_text"><?php echo $email_text; ?></textarea><br/>
				<span class="mi_email_text"><?php _e( 'There are some tokens you can use in the email text which will be dynamically replaced: <code>%inviteurl%</code> (<strong>this token is required</strong>, it is replaced by the link the invitee clicks on to accept the invitation), <code>%blogurl%</code> (the web address for your blog), <code>%blogname%</code> (the name of your blog), <code>%email%</code> (the email address of the invitee), <code>%name%</code> (the name of the invitee), <code>%username%</code> (the username you have chosen for the invitee).', 'mass-invites' ); ?>.</span></td>
			</tr>
		</table>

		<p class="submit">
			<input type="submit" name="Submit" class="button-primary" value="Create Invitations" />
		</p>
	</form>

	<?php if ( $invites ) { ?>
		<h2><?php _e( 'Created Invitations', 'mass-invites' ); ?></h2>
		
		<table class="widefat fixed" cellspacing="0">
			<thead>
				<tr class="thead">
					<th scope="col" id="first_name" class="column-name">First Name</th>
					<th scope="col" id="last_name" class="column-name">Last Name</th>
					<th scope="col" id="email" class="column-email">E-mail</th>
					<th scope="col" id="username" class="column-username">Username</th>
				</tr>
			</thead>

			<tfoot>
				<tr class="thead">
					<th scope="col" class="column-name">First Name</th>
					<th scope="col" class="column-name">Last Name</th>
					<th scope="col" class="column-email">E-mail</th>
					<th scope="col" class="column-username">Username</th>
				</tr>
			</tfoot>

			<tbody class="list:user user-list">
				<?php foreach( $invites as $invite ) { ?>
					<tr class="alternate">
						<th scope="col" class="column-email"><?php echo $invite[ 'email' ]; ?></th>
						<td scope="col" class="column-name"><?php echo $invite[ 'first_name' ]; ?></td>
						<td scope="col" class="column-name"><?php echo $invite[ 'last_name' ]; ?></td>
						<td scope="col" class="column-username"><?php echo $invite[ 'username' ]; ?></td>
					</tr>
				<?php } ?>
			</tbody>

		</table>

	<?php } ?>
	
	<?php if ( $rejects ) { ?>
		<h2><?php _e( 'Rejected Invitations', 'mass-invites' ); ?></h2>
		

		<table class="widefat fixed" cellspacing="0">
			<thead>
				<tr class="thead">
					<th scope="col" id="email" class="column-email">E-mail</th>
					<th scope="col" id="reason" class="column-reason">Reason</th>
					<th scope="col" id="first_name" class="column-name">First Name</th>
					<th scope="col" id="last_name" class="column-name">Last Name</th>
					<th scope="col" id="username" class="column-username">Username</th>
				</tr>
			</thead>

			<tfoot>
				<tr class="thead">
					<th scope="col" class="column-email">E-mail</th>
					<th scope="col" class="column-reason">Reason</th>
					<th scope="col" class="column-name">First Name</th>
					<th scope="col" class="column-name">Last Name</th>
					<th scope="col" class="column-username">Username</th>
				</tr>
			</tfoot>

			<tbody class="list:user user-list">
				<?php foreach( $rejects as $reject ) { ?>
					<tr class="alternate">
						<th scope="col" class="column-email"><?php echo $reject[ 'email' ]; ?></th>
						<td scope="col" class="column-reason"><?php echo $reject[ 'reason' ]; ?></td>
						<td scope="col" class="column-name"><?php echo $reject[ 'first_name' ]; ?></td>
						<td scope="col" class="column-name"><?php echo $reject[ 'last_name' ]; ?></td>
						<td scope="col" class="column-username"><?php echo $reject[ 'username' ]; ?></td>
					</tr>
				<?php } ?>
			</tbody>

		</table>

	<?php } ?>
	
</div>
