<?php
/**
 * FPM
 *
 * FPM is a lightweight, easy and fast personal messagin system
 *
 * @package    FPM
 * @version    1.0
 * @author     Jaafari El Housseine - JefferyHus
 * @license    MIT License
 * @copyright  2016 JefferyHus
 * @link       https://github.com/JefferyHus
 */

namespace Fpm;

class Fpm_Driver_Outbox extends \Fpm_Driver{

	/**
	 * Re-build the parent instance
	 * @param [type] $email [description]
	 */
	public function __construct($email){
		parent::__construct($email);
	}

	/**
	 * Will set the messages list
	 */
	public function init(){
		// Select all messages sent from this email
		static::$_messages = \DB::select_array( \Config::get('fpm.table_columns') )
		->from( \Config::get('fpm.table_name') )
		->where_open()
		->where( \Config::get('fpm.transmitter_attribute'), '=', $this->_email )
		->where_close()
		->execute()
		->as_array();
		
		return $this;
	}

}