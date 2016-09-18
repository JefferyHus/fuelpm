# Fuel PM Package.

A full fledged personal message class for Fuel. Send and save personal messages inside your project.

# Summary

* Send plain/text.

# Usage

	$message = Fpm::forge();
	$message->from('me@domain.com', 'Your Name Here');
	
	// Set to
	$message->to('mail@domain.com');
	
	// Set as array
	$message->to(array(
		'mail@domain.com',
	));
	
	// Work the same for ->cc and ->bcc
	
	
	// Set a body message
	$message->body('My email body');

	// Set a subject
	$message->subject('This is the subject');
	
	// And send it
	$result = $message->send();

# Exceptions

	+ \InvalidEmailException, thrown when one or more email addresses doesn't pass validation
	+ \MessageSendingFailedException, thrown when the driver failed to send the exception

Example:

	// Use the default config and change the driver
	$message = \Fpm::forge(array('defaults' =>  array('validate' => false) ));
	$message->subject('My Subject');
	$message->body('hey, today is my birthday !!!');
	$message->from('me@example.com');
	$message->to('other@example.com');
	
	try
	{
		$message->send();
	}
	catch(\InvalidEmailException $e)
	{
		// The validation failed
	}
	catch(\MessageSendingFailedException $e)
	{
		// The message could not be saved and sent
	}

## TODO

- Add the attachment functionality
- Add the i18n configurable messages, so you can have your personal success and error messages in your language

```

# That's it. Questions? 

