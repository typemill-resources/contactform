<?php

namespace Plugins\contactform;

use \Typemill\Plugin;
use Typemill\Models\Mail;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;


class ContactForm extends Plugin
{
	protected $settings;
	protected $pluginSettings;
	protected $originalHtml;
	protected $contactpage = false;
	
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
		# get url-segments with cookies on
		$data 	= $segments->getData();

		$this->settings 		= $this->getSettings();
		$this->pluginSettings 	= $this->getPluginSettings();

		if(empty($this->pluginSettings) OR !isset($this->pluginSettings['page_value']) OR ($this->pluginSettings['page_value'] == ''))
		{
			$segments->setData($data);

			return;
		}

		$this->contactpage 		= trim($this->pluginSettings['page_value'], '/');
		
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
		if(!$this->contactpage)
		{
			$content = $html->getData();

			$content = '<div style="width=100%;display:block;padding:5px;background-color:red;color:white;">Please define a contact page in the contact plugin.</div>' . $content;

			$html->setData($content);

			return;
		}

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
					$content = $content . '<div class="tm-contactresult"><div class="mailresult">' . $this->markdownToHtml($this->pluginSettings['message_error']) . ($_SESSION['contactform']['error'] ?? '') . '</div></div>';
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
		$forminput 			= $request->getParsedBody();
		$referer 			= $request->getHeaderLine('Referer');

		if(empty($referer))
		{
			$baseurl 		= rtrim($this->urlinfo['baseurl'], '/');
			$contactpage 	= $this->contactpage ? ltrim($this->contactpage, '/') : '';
			$referer 		= $baseurl . '/' . $contactpage;
		}

		# validate input
		$validvalues 		= $this->validateParams($forminput);
		if(!$validvalues)
		{
			# errors are set to session already
			return $response->withHeader('Location', $referer)->withStatus(302);
		}

		$pluginSettings 	= $this->getPluginSettings();
		$settings 			= $this->getSettings();

		$mail 				= new Mail($settings);

		$subject 			= $validvalues['subject'];
		$message 			= 'From: ' . $validvalues['email'] . ', ' . $validvalues['name'] . "\r\r" . $validvalues['message'];

		$send 				= $mail->send($pluginSettings['mailto'], $subject, $message);

		if($send)
		{
			$_SESSION['contactform']['result'] = 'success';
		}
		else
		{
			$_SESSION['contactform']['result'] = 'error';
			$_SESSION['contactform']['error'] = $mail->error;
		}

		return $response->withHeader('Location', $referer)->withStatus(302);
	}
}