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

\Autoloader::add_core_namespace('Fpm');

\Autoloader::add_classes(array(

	/**
	 * FPM Classes
	 */
	'Fpm\Fpm'								=> __DIR__ . '/classes/fpm.php',

	/**
	 * FPM Exceptions
	 */
	'Fpm\FpmException'						=> __DIR__ . '/classes/fpm.php',
	'Fpm\MessageSendingFailedException'		=> __DIR__ . '/classes/fpm.php',
	'Fpm\AttachmentNotFoundException'		=> __DIR__ . '/classes/fpm.php',
	'Fpm\InvalidAttachmentsException'		=> __DIR__ . '/classes/fpm.php',
	'Fpm\InvalidEmailException'				=> __DIR__ . '/classes/fpm.php',
	'Fpm\AuthLoginDriverNotFoundException'	=> __DIR__ . '/classes/fpm.php',
));