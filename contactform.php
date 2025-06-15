<?php

namespace Plugins\contactform;

use \Typemill\Plugin;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Routing\RouteContext;
use \Typemill\Events\OnTwigLoaded;


class ContactForm extends Plugin
{
	protected $settings;
	protected $pluginSettings;
	protected $originalHtml;
	protected $contactpage;
	
    public static function getSubscribedEvents()
    {
		return [
			'onSessionSegmentsLoaded' 	=> 'onSessionSegmentsLoaded',
			'onHtmlLoaded' 				=> 'onHtmlLoaded',
		];
    }

	# you can add new routes for public, api, or admin-area	
	public static function addNewRoutes()
	{
		return [ 

			# add a frontend route to receive form data
			[
				'httpMethod' 	=> 'post', 
				'route' 		=> '/contactprocessor', 
				'name' 			=> 'contact.send', 
				'class' 		=> 'Plugins\contactform\ContactForm:send',
			],
		];
	}

	# add the path stored in user-settings to initiate session and csrf-protection
	public function onSessionSegmentsLoaded($segments)
	{
		$this->settings 		= $this->getSettings();
		$this->pluginSettings 	= $this->getPluginSettings();
		$this->contactpage 		= trim($this->pluginSettings['page_value'], '/');

		# get url-segments with cookies on
		$data 	= $segments->getData();
		
		# add the page for contact form to the segments with cookies
		$data[]	= $this->contactpage;
		$data[] = '/' . $this->contactpage;
		$data[]	= 'contactprocessor';
		$data[]	= '/contactprocessor';
		
		$segments->setData($data);
	}

	# create the output
	public function onHtmlLoaded($html)
	{
		if($this->route == $this->contactpage)
		{
			$content = $html->getData();

			# if a mail has been send
			$result = $_SESSION['contactform']['result'] ?? false;
			if($result)
			{
				if($result == 'success')
				{
					$content = $content . '<div class="tm-contactresult"><div class="mailresult">' . $this->markdownToHtml($this->pluginSettings['message_success']) . '</div></div>';
				}
				elseif($result == 'error')
				{
					$content = $content . '<div class="tm-contactresult"><div class="mailresult">' . $this->markdownToHtml($this->pluginSettings['message_error']) . $_SESSION['contactform']['error'] . '</div></div>';
				}
				unset($_SESSION['contactform']);
			}
			else
			{
				# get the public forms for the plugin
				$contactform = $this->generateForm('contact.send');

				# add forms to the content
				$content = $content . '<div class="tm-contactform">' . $contactform . '</div>';
			}

			$html->setData($content);
		}
	}

	public function send(Request $request, Response $response, $args)
	{
		# we have to dispatch the twig event so mail gets loaded
		$this->container->get('dispatcher')->dispatch(new OnTwigLoaded(false), 'onTwigLoaded');		

		$forminput 			= $request->getParsedBody();
		$referer 			= $request->getHeader('HTTP_REFERER');

		# validate input
		$validvalues 		= $this->validateParams($forminput);
		if(!$validvalues)
		{
			# errors are set to session already
			# do you want to add flash message? But it requires that theme has flash
			return $response->withHeader('Location', $referer[0])->withStatus(302);
		}
		
		$pluginSettings 	= $this->getPluginSettings();
		$send 				= false;
		try {
		    $mail = $this->container->get('mail');
		} 
		catch (\DI\NotFoundException $e)
		{
		    $mail 			= false;
		}

		if($mail)
		{
			$message = 'From: ' . $validvalues['email'] . ', ' . $validvalues['name'] . "\r\r" . $validvalues['message'];

			$mail->addAdress($pluginSettings['mailto']);
			$mail->addReplyTo($validvalues['email']);
			$mail->setSubject($validvalues['subject']);
			$mail->setBody($message);

			$send = $mail->send();
		}

		if($send === true)
		{
			$_SESSION['contactform']['result'] = 'success';
		}
		else
		{
			$_SESSION['contactform']['result'] = 'error';
			$_SESSION['contactform']['error'] = $send;
		}
		
		return $response->withHeader('Location', $referer[0])->withStatus(302);
	}
}