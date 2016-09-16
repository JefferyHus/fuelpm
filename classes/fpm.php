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

class FpmException extends \FuelException {}

class MessageSendingFailedException extends \FuelException {}

class AttachmentNotFoundException extends \FuelException {}

class InvalidAttachmentsException extends \FuelException {}

class InvalidEmailException extends \FuelException {}

class AuthLoginDriverNotFoundException extends \FuelException {}

class Fpm
{
	/**
	 * Instance for singleton usage.
	 */
	public static $_instance = null;

	/**
	 * The unique hash code, sued as index for search
	 */
	protected $hash 	= null;

	/**
	 * The sender email
	 */
	protected $from 	= null;

	/**
	 * To recipients list
	 */
	protected $to 		= array();

	/**
	 * Cc recipients list
	 */
	protected $cc 		= array();

	/**
	 * Bcc recipients list
	 */
	protected $bcc 		= array();

	/**
	 * Message body
	 */
	protected $body 	= null;

	/**
	 * Message subject
	 */
	protected $subject	= null;

	/**
	 * Attachments array
	 */
	protected $attachments = array(
		'inline'     => array(),
		'attachment' => array(),
	);

	/**
	 * Driver config defaults.
	 */
	protected static $_defaults;

	/**
	 * Init, config loading.
	 */
	public static function _init(){
		\Config::load('fpm', true);

		static::$_defaults = \Config::get('fpm.defaults');
	}

	/**
	 * Load a message driver to the array of loaded drivers
	 *
	 * @param   Array  settings for the new driver
	 * @throws  FpmException  on driver load failure
	 */
	public static function forge($custom = array()){
		// Return a new instance
		$instance = new static();

		$custom = is_string( $custom ) ? (array) $custom : $custom;

		static::$_defaults = \Arr::merge(static::$_defaults, $custom);

		return $instance;
	}

	/**
	 * Sets the message sender
	 * 
	 * @param  string 	$email 	The from address
	 * @return $this
	 */
	public function from($email = null){
		// Check whether the Auth package is installed or not
		if( is_null( $email ) )
			static::check();

		$this->from = $email === null ? \Auth::get('email') : $email;

		return $this;
	}

	/**
	 * Sets the to recievers list.
	 * 
	 * @param  string 	$email The to address
	 * @return $this
	 */
	public function to($email = null){
		static::add_to_list('to', $email);

		return $this;
	}

	/**
	 * Sets the subject
	 *
	 * @param  string  $subject  The message subject
	 *
	 * @return  $this
	 */
	public function subject($subject = null){
		$this->subject = (string) $subject;

		return $this;
	}

	/**
	 * Sets the body
	 *
	 * @param  string  $body  The message body
	 *
	 * @return  $this
	 */
	public function body($body = null){
		$this->body = (string) $body;

		return $this;
	}

	/**
	 * Sets the cc recipients list.
	 * 
	 * @param  string 	$email The to address
	 * @return $this
	 */
	public function cc($email = null){
		static::add_to_list('cc', $email);

		return $this;
	}

	/**
	 * Sets the bcc recipients list.
	 * 
	 * @param  string 	$email The to address
	 * @return $this
	 */
	public function bcc($email = null){
		static::add_to_list('bcc', $email);

		return $this;
	}

	/**
	 * Add to a recipients list.
	 *
	 * @param   string          $list   List to add to (to, cc, bcc)
	 * @param   string|array    $email  Email address or list of email addresses, array(email)
	 *
	 * @return  void
	 */
	protected function add_to_list($list, $email)
	{
		if ( ! is_array($email))
		{
			$email = array($email);
		}

		foreach ($email as $_email => $name)
		{
			if (is_numeric($_email))
			{
				$_email = $name;
			}

			$this->{$list}[$_email] = array(
				'email' => $_email,
			);
		}
	}

	/**
	 * Clear the a recipient list.
	 *
	 * @param   string|array    $list   List or array of lists
	 *
	 * @return  void
	 */
	protected function clear_list($list)
	{
		is_array($list) or $list = array($list);

		foreach ($list as $_list)
		{
			$this->{$_list} = array();
		}
	}

	/**
	 * Clear all recipient lists.
	 *
	 * @return  $this
	 */
	public function clear_recipients()
	{
		static::clear_list(array('to', 'cc', 'bcc'));

		return $this;
	}

	/**
	 * Selects all recieved emails
	 * @param  string $email The current user's email or another email
	 * @return bool|array    flase if nothing has been found, array of recieved emails
	 */
	public static function inbox($email = null){
		// Check whether the Auth package is installed or not
		if( is_null( $email ) )
			static::check();

		$email = is_null( $email ) ? \Auth::get('email') : $email;

		$inbox = \DB::select_array( \Config::get('fpm.table_columns') )
		->from( \Config::get('fpm.table_name') )
		->where_open()
		->where( \Config::get('fpm.recipient_attribute'), '=', $email )
		->where_close()
		->execute()
		->as_array();

		return empty( $inbox ) ? false : $inbox;
	}

	/**
	 * Check if the Auth driver is loaded successfully or not
	 * 
	 * @return true if the package is loaded
	 * @throws \AuthLoginDriverNotFoundException if the Auth package has not been loaded
	 */
	public static function check(){
		if( !\Package::loaded( 'auth' ) )
			throw new AuthLoginDriverNotFoundException("The Auth package has not been loaded, make sure to load it first.", 1);
		
		return true;
	}

	/**
	 * Validates all the email addresses.
	 *
	 * @return  bool|array  True if all are valid or an array of recipients which failed validation.
	 */
	protected function validate_addresses()
	{
		$failed = array();

		foreach (array('to', 'cc', 'bcc') as $list)
		{
			foreach ($this->{$list} as $recipient)
			{
				if ( ! filter_var($recipient['email'], FILTER_VALIDATE_EMAIL))
				{
					$failed[$list][] = $recipient;
				}
			}
		}

		if (count($failed) === 0)
		{
			return true;
		}

		return $failed;
	}

	/**
	 * Returns a formatted string of email addresses.
	 *
	 * @param   array   $addresses  Array of adresses array(array(email=>email));
	 *
	 * @return  string              Correctly formatted email addresses
	 */
	protected static function format_addresses($addresses)
	{
		$return = array();

		foreach ($addresses as $recipient)
		{
			$return[] = $recipient['email'];
		}

		return join(', ', $return);
	}

	/**
	 * Initiates the sending process.
	 *
	 * @param   bool    Whether to validate the addresses
	 *
	 * @throws \InvalidEmailException  			One or more email addresses did not pass validation
	 * @throws \FuelException                   Cannot send without from address/Cannot send without recipients
	 *
	 * @return  bool
	 */
	public function send($validate = null)
	{
		if (empty($this->to) and empty($this->cc) and empty($this->bcc))
		{
			throw new \FuelException('Cannot send email without recipients.');
		}

		// Check which validation bool to use
		is_bool($validate) or $validate = static::$_defaults['validate'];

		// Validate the email addresses if specified
		if ($validate and ($failed = $this->validate_addresses()) !== true)
		{
			throw new \InvalidEmailException('One or more email addresses did not pass validation.');
		}

		$this->_save();

		return true;
	}

	/**
	 * Saves the message into the database table
	 * @return bool Whether is has been saved
	 */
	public function _save()
	{	
		// Create a UNIQUE hash code for the message
		$this->hash = \Crypt::encode( \Date::forge()->get_timestamp(), $this->from );

		$this->to 	= is_array( $this->to )  ? static::format_addresses( $this->to ) : $this->to;
		$this->cc 	= is_array( $this->cc )  ? static::format_addresses( $this->cc ) : $this->cc;
		$this->bcc 	= is_array( $this->bcc ) ? static::format_addresses( $this->bcc ) : $this->bcc;

		$message = array(
			'message_hash'	=> $this->hash,
			'from_email'	=> $this->from,
			'to_email'		=> $this->to,
			'cc'			=> $this->cc,
			'bcc'			=> $this->bcc,
			'subject'		=> $this->subject,
			'body'			=> $this->body,
			'opened' 		=> false
		);

		$result = \DB::insert( \Config::get('fpm.table_name') )->set( $message )->execute( \Config::get('fpm.db_connection') );

		return ($result[1] > 0) ? $result[0] : false;
	}

	/**
	 * Prevent initiation
	 */
	public function __construct(){}

	/**
	 * Call rerouting for static usage.
	 *
	 * @param    string $method method name called
	 * @param    array  $args supplied arguments
	 *
	 * @throws \BadMethodCallException Invalid method
	 *
	 * @return mixed
	 */
	public static function __callStatic($method, $args = array())
	{
		if(static::$_instance === false)
		{
			$instance = static::forge();
			static::$_instance = &$instance;
		}

		if(is_callable(array(static::$_instance, $method)))
		{
			return call_fuel_func_array(array(static::$_instance, $method), $args);
		}

		throw new \BadMethodCallException('Invalid method: '.get_called_class().'::'.$method);
	}
}