<?php

namespace Fuel\Migrations;

class Fpm_Create_Messagetables
{
	function up(){
		// get the tablename
		\Config::load('fpm', true);
		$table = \Config::get('fpm.table_name', 'message');

		// make sure the correct connection is used
		$this->dbconnection('fpm');

		// only do this if it doesn't exist yet
		if ( ! \DBUtil::table_exists($table))
		{
			// table message
			\DBUtil::create_table($table, array(
				'id' => array('type' => 'int', 'constraint' => 11, 'auto_increment' => true),
				'message_hash' => array('type' => 'varchar', 'constraint' => 255),
				'headers' => array('type' => 'text'),
				'receiver' => array('type' => 'varchar', 'constraint' => 50),
				'sender' => array('type' => 'varchar', 'constraint' => 50),
				'cc' => array('type' => 'varchar', 'constraint' => 255, 'default' => 0),
				'bcc' => array('type' => 'varchar', 'constraint' => 255, 'default' => 0),
				'subject' => array('type' => 'varchar', 'constraint' => 255),
				'body' => array('type' => 'text'),
				'opened' => array('type' => 'tinyint', 'default' => 0),
				'created_at' => array('type' => 'int', 'constraint' => 11, 'default' => 0),
				'updated_at' => array('type' => 'int', 'constraint' => 11, 'default' => 0),
			), array('id'));

			// add a unique index on message_hash
			\DBUtil::create_index($table, 'message_hash', 'message_hash', 'UNIQUE');
		}

		// reset any DBUtil connection set
		$this->dbconnection(false);
	}

	function down(){
		// get the tablename
		\Config::load('fpm', true);
		$table = \Config::get('fpm.table_name', 'message');

		// make sure the correct connection is used
		$this->dbconnection('fpm');

		// drop the admin_users table
		\DBUtil::drop_table($table);
	}

	/**
	 * check if we need to override the db connection for auth tables
	 */
	protected function dbconnection($type = null)
	{
		static $connection;

		switch ($type)
		{
			// switch to the override connection
			case 'fpm':

			// switch back to the configured migration connection, or the default one
			case false:
				if ($connection)
				{
					\DBUtil::set_connection(\Config::get('migrations.connection', null));
				}
				break;

			default:
				// noop
		}
	}
}