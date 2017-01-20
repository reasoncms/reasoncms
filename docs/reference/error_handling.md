#Error Handling

Reason includes a php error handler that provides several useful functions. If you're not familiar with how it works, it can also case some puzzlement.

##Error messages hidden by default

By default, error messages are hidden from the output stream. 

This is considered a [best security practice](https://www.owasp.org/index.php/A7_2004_Improper_Error_Handling), since error messages can reveal details about the underlying system that an attacker can use to perform an attack.

It is also nice for our users, since developer-oriented error messages are rarely helpful to someone who can't do anything about the issue.

##Errors shown to developer IP addresses

Since it is useful for developers to see error messages directly in Reason's output, reason offers two ways to turn inline errors on:

1. Include your IP address in the [error handler settings](../../settings/error_handler_settings.php).

2. Turn error handling on for your current session. Go to Master Admin -> Admin Tools -> Toggle Error Visibility. This is particularly useful if you are not at your own workstation, if you have a dynamic IP, or if you are connecting via a VPN. Note tht you can also use this to turn error handling temporarily off without mucking with the settings.

##Errors written to logs

Error messages are written to a log file. By default this file is at `/tmp/php-errors-[your hostname]`. So if your host name is example.com, errors would be written to `/tmp/php-errors-example.com`.

To see the latest error messages, simply `tail /tmp/php-errors-example.com`.

If this is a production instance of Reason, you should make sure that this file is included in your log rotation.

##Turning error output off in php

As a developer, you may need to suppress inline error output in PHP. A common example is if you are producing structured non-HTML output like XML, JSON, CSV, etc. that would break strict parsers. HEre's how to do that inside php:

```php
turn_carl_util_error_output_off();
// Code
turn_carl_util_error_output_on()
```