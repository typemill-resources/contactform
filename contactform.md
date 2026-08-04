# Simple Contactform

This plugin adds a simple contact form to your Typemill website. You have to install the mail-plugin for the contact form.

Simply configure your contact form in the plugin settings and choose a page where to integrate the form. A recaptcha field is automatically added. You can switch it off or change it, so that it appears only after the first wrong input. Alternatively you can activate a Google recaptcha feature to avoid spam. 

## History

## Version 2.2.0

Checked for PHP 8.5 compability.

### Version 2.1.0

Adjusted to the new mail feature in Typemill core.

### Version 2.0.2

Fixed wording and improved handling of old input.

### Version 2.0.1

Improved error handling if email plugin is not installed.

### Version 2.0.0

Made compatible to Typemill Version 2. Typemill 2 does not provide a public endpoint for post-requests anymore. Instead the plugins have to handle post requests on their own now. This requires a deep refactoring.

### Version 1.1.0

* Adjusted to the new security middleware of Typemill version 1.5.0. 
* Added configuration for the php-captcha-field.
* Adjusted configuration for the Google recaptcha field.
* Removed css (style should be handled by the themes). 
* Removed all local security measures because they will be handled centrally now.

