<?php if (!defined ('ABSPATH')) die ('No direct access allowed'); ?>

<div class="wrap" id="lifespan">

	<div id="icon-users" class="icon32"><br /></div>
	<h2><?php _e( 'Currently Active Invitations', 'mass-invites' ); ?></h2>
	
	<form id="posts-filter" action="" method="post">
		<input type="hidden" name="delete_invitations" value="1" />
		<?php wp_nonce_field( 'mi_delete_invites', '_mass_invites_nonce' ); ?>

	<div class="tablenav">

	<div class="alignleft actions">
	<select name="action">
	<option value="" selected="selected">Bulk Actions</option>
	<option value="delete_invites">Delete</option>
	</select>
	<input type="submit" value="Apply" name="doaction" id="doaction" class="button-secondary action" />
</div>

	<br class="clear" />
	</div>

	<table class="widefat fixed" cellspacing="0">

	<thead>
	<tr class="thead">
		<th scope="col" id="cb" class="manage-column column-cb check-column" style=""><input type="checkbox" /></th>
		<th scope="col" id="username" class="manage-column column-username" style="">Username</th>
		<th scope="col" id="first_name" class="manage-column column-first_name" style="">First Name</th>
		<th scope="col" id="last_name" class="manage-column column-last_name" style="">Last Name</th>
		<th scope="col" id="email" class="manage-column column-email" style="">E-mail</th>
		<th scope="col" id="invite_url" class="manage-column column-invite_url" style="">Invite URL</th>
		<th scope="col" id="date" class="manage-column column-date num" style="">Created Date</th>
	</tr>
	</thead>

	<tfoot>
	<tr class="thead">
		<th scope="col" class="manage-column column-cb check-column" style=""><input type="checkbox" /></th>
		<th scope="col" class="manage-column column-username" style="">Username</th>
		<th scope="col" class="manage-column column-first_name" style="">First Name</th>
		<th scope="col" class="manage-column column-last_name" style="">Last Name</th>
		<th scope="col" class="manage-column column-email" style="">E-mail</th>
		<th scope="col" class="manage-column column-invite_url" style="">Invite URL</th>
		<th scope="col" class="manage-column column-date num" style="">Created Date</th>
	</tr>
	</tfoot>

	<tbody id="users" class="list:user user-list">

		<?php foreach ( $invites as $i ) { ?>
			<tr id='user-<?php echo $i->id; ?>' class="alternate">
				<th scope='row' class='check-column'><input type='checkbox' name='mi_invites[]' id='mi_invites_<?php echo $i->id; ?>' value='<?php echo $i->id; ?>' /></th>
				<td class="username column-username"><?php echo $i->username; ?></td>
				<td class="first_name column-first_name"><?php echo $i->first_name; ?></td>
				<td class="last_name column-last_name"><?php echo $i->last_name; ?></td>
				<td class="email column-email"><a href="mailto:<?php echo $i->email; ?>"><?php echo $i->email; ?></a></td>
				<td class="invite_url column-invite_url"><input type="text" class="small-text" name="key_<?php echo $i->id ?>" value="<?php echo $this->create_invite_url( $i->key ); ?>" /></td>
				<td class="created column-created"><?php echo $i->created_date; ?></td>
			</tr>
		<?php } ?>
	</tbody>
	</table>

	<div class="tablenav">


	<div class="alignleft actions">
	<select name="action2">
	<option value="" selected="selected">Bulk Actions</option>
	<option value="delete_invites">Delete</option>
	</select>
	<input type="submit" value="Apply" name="doaction2" id="doaction2" class="button-secondary action" />
	</div>

	<br class="clear" />
	</div>


	</form>
	
</div>
