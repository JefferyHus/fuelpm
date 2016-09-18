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

class FpmInvalidField extends \FpmException{}

class Fpm_Driver{

	/**
	 * The email of whom we use as index
	 */
	public $_email = '';

	/**
	 * Contains the results to return when finished
	 */
	public $_results = array();

	/**
	 * Array of all messages
	 */
	protected static $_messages = array();

	/**
	 * Fields used to sort the list of results
	 */
	protected static $_fields = array(
		'date' 		=> 'created_at',
		'sender' 	=> 'sender',
		'receiver'	=> 'receiver',
		'subject'	=> 'subject'
	);

	/**
	 * Sorting types used in the sort function
	 */
	protected static $_sort_types = array(
		'asc' 	=> 'ASC',
		'desc' 	=> 'DESC'
	);

	/**
	 * Driver's name
	 */
	protected static $_driver = false;

	/**
	 * The instance of the Driver class
	 */
	protected static $_instance = false;

	/**
	 * Prevent the initiation
	 */
	public function __construct($email){
		$this->_email = $email;
	}

	public static function forge($email = null, $driver){
		// If the email is not null then set it to the current
		// logged in user
		$email = is_null( $email ) ? \Auth::get('email') : $email;
		// Create the driver instance
		static::$_driver = '\Fpm_Driver_'.$driver;
		// Check if an instance is already set
		if( is_bool( static::$_instance ) ){
			static::$_instance = new static::$_driver( $email );
		}
		// Init the results
		static::$_instance->init();
		
		return static::$_instance;
	} 

	/**
	 * Used with the array_walk_recursive
	 * @param  string $field 	the field we are looking for
	 * @return bool        		true if the key exists
	 */
	public function field_exists($field){
		$_found = false;
		// Checks if the results list has $field
		foreach ($this->_results as $key => $item) {
			if( array_key_exists($field, $item) ){
				$_found = true;
				// exit the loop
				break;
			}
		}

		return $_found;
	}

	/**
	 * [_sort description]
	 * @param  string $field 	field used to sort the array by
	 * @param  string $type  	type of sorting asc|desc
	 * @return callback        	function using passed args by the usort() function
	 */
	public static function _sort($field, $type){
		return function($first, $second) use ($field, $type){
			// If the field is date
			if( $field == "date" ){
				$first[$field] 	= strtotime( $first[$field] );
				$second[$field] = strtotime( $second[$field] );
			}

			// Compare and return result
			if( $type == 'DESC' )
				return $first[$field] > $second[$field] ? -1 : 1;
			else
				return $first[$field] > $second[$field] ? 1 : -1;
		};
	}

	/**
	 * This function will sort the results according to $field
	 * @param  string $field the field name used to sort by
	 * @param  string $type   the type of sorting
	 * @return $this
	 *
	 * @throws \FpmInvalidField throws when the $field does not exists inside the results
	 */
	public function sort($field, $type = 'asc'){
		// Get the correct string from the __CLASS_ $_fields and $_sort_types lists
		$field = \Arr::get(static::$_fields, $field);
		$type  = \Arr::get(static::$_sort_types, $type);

		if( empty( $field ) || empty( $type ) )
			throw new \FpmInvalidField("Invalid arguments given to the function.");

		if( count( static::$_messages ) > 0 and count( $this->_results ) > 0 ){
			// Check if the fiels exists
			if( !$this->field_exists($field) )
				throw new \FpmInvalidField("This key does not exist in the results list.");

			// Now we sort the results using $field and $type
			usort($this->_results, static::_sort($field, $type));
		}

		return $this;
	}

	/**
	 * Alias for all existing functions
	 * @param  boolean $method the method which can perform the wanted action
	 * @return method          the method to be called
	 *
	 * @throws \FpmException If the method has not been found in the class
	 */
	public function get($method = false){
		// If not method is set then return $this
		if( is_bool( $method ) ) return $this;
		// Create the method name
		$method = 'get_'.$method;
		// Else we check if the method exists first
		if( !method_exists($this, $method) ){
			throw new \FpmException("Method \Fpm_Driver::".$method."() has not bee found.");
		}

		// Pop the first value
		$arguments = func_get_args() ;
		unset($arguments[0]);

		return $this->{$method}( $arguments );
	}

	/**
	 * Checks if a given hash exists in the results
	 * @param  mixed $hashCodes string or array of hash codes of each message
	 * @return $this
	 */
	public function get_hash($hashCodes){
		array_map(function($item) use ($hashCodes){
			// Check if $hashCode is an array
			foreach ($hashCodes as $key => $hash) {
				if( strcasecmp($item['message_hash'], $hash) === 0 )
					$this->_results[] = $item;
			}
		}, static::$_messages);
		
		return $this;
	}

	/**
	 * Sets all the messages into the results list
	 * @return $this
	 */
	public function get_all(){
		$this->_results = static::$_messages;

		return $this;
	}

	/**
	 * Checks if there is any read messages and returns it
	 * @return $this
	 */
	public function get_read(){
		// Walk through the array of messages and save the read one
		foreach (static::$_messages as $key => $message) {
			$message = (object) $message;

			if( $message->opened )
				$this->_results[] = (array) $message;
		}

		return $this;
	}

	/**
	 * Checks if there is any unread messages and returns it
	 * @return $this
	 */
	public function get_unread(){
		// Walk through the array of messages and save the unread one
		foreach (static::$_messages as $key => $message) {
			$message = (object) $message;

			if( !$message->opened )
				$this->_results[] = (array) $message;
		}

		return $this;
	}

	/**
	 * Clears all parametres and destroys the current instance
	 */
	public function clear(){
		static::$_messages 	= array();
		$this->_results		= array();
		static::$_driver 	= false;
		static::$_instance	= false;
	}

	/**
	 * Finishes the process and returns the results
	 * @return [type] [description]
	 */
	public function finish(){
		// Save the results
		$_results = $this->_results;
		// Clear all variables
		$this->clear();

		return $_results;
	}

	/**
	 * Returns the Driver instance name.
	 *
	 *     echo (string) $driver;
	 *
	 * @return  string
	 */
	final public function __toString()
	{
		return static::$_driver;
	}
}