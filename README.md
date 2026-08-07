# Simple Contact Form

The **Simple Contact Form** plugin adds an easy-to-use contact form to your Typemill website. Configure the form in the plugin settings, select the page where it should appear, and visitors can send messages directly to your inbox.

![screenshot of the contact form](media/live/contactform.png){.center loading="lazy" width="820" height="665"}

The plugin uses Typemill's built-in mail functionality, so no additional mail integration is required.

## Features

- Easy setup through the Typemill plugin settings.
- Display the form on any page by specifying its path.
- Customize all field labels and button text.
- Add a legal notice or other information above the submit button using Markdown.
- Customize the success and error messages shown after form submission.
- Uses Typemill's internal mail system to deliver messages.
- Built-in CAPTCHA to reduce spam.
- Optional Google reCAPTCHA integration.

## History

### Version 2.2.0

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