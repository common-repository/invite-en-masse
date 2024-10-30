<?php

class MassInvites_Database
{
	function upgrade( $old, $new )
	{
		
	}
	
	function install()
	{
		global $wpdb;

		$sql = array();

		// A table to store the ppremium content cost and content in
		$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}mi_invitations (
			`id` BIGINT(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
			`email` VARCHAR(100) NOT NULL,
			`first_name` VARCHAR(255) NOT NULL,
			`last_name` VARCHAR(255) NOT NULL,
			`username` VARCHAR(60) NOT NULL,
			`key` VARCHAR(255) NOT NULL,
			`created_date` DATETIME NOT NULL
		) ENGINE=MyISAM CHARSET=utf8";

		if (version_compare (mysql_get_server_info (), '4.0.18', '<')) {
			foreach ($sql AS $pos => $line) {
				$sql[$pos] = str_replace ('ENGINE=MyISAM ', '', $line);
			}
		}
		
		foreach ($sql AS $pos => $line) {
			$wpdb->query ($line);
		}
	}

	function uninstall()
	{
		global $wpdb;

		$sql = array();

		// A table to store the ppremium content cost and content in
		$sql[] = "DROP TABLE IF EXISTS {$wpdb->prefix}mi_invitations";

		if (version_compare (mysql_get_server_info (), '4.0.18', '<')) {
			foreach ($sql AS $pos => $line) {
				$sql[$pos] = str_replace ('ENGINE=MyISAM ', '', $line);
			}
		}
		
		foreach ($sql AS $pos => $line) {
			$wpdb->query ($line);
		}
	}
}
?>
