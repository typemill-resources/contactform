<?php

namespace Plugins\contactform;

use \Typemill\Plugin;

class ContactForm extends Plugin
{
	protected $settings;
	protected $pluginSettings;
	protected $originalHtml;
	protected $active = false;
	
    public static function getSubscribedEvents()
    {
		return array(
			'onSessionSegmentsLoaded' 	=> 'onSessionSegmentsLoaded',
			'onHtmlLoaded' 				=> 'onHtmlLoaded',
			'onPageReady'				=> 'onPageReady',
		);
    }	
		
	# add the path stored in user-settings to initiate session and csrf-protection
	public function onSessionSegmentsLoaded($segments)
	{
		$this->settings = $this->getSettings();
		
		if(isset($this->settings['plugins']['mail']) AND $this->settings['plugins']['mail']['active'])
		{
			$this->active = true;
			$this->pluginSettings = $this->getPluginSettings('contactform');
		}
		
		if($this->active && $this->getPath() == $this->pluginSettings['page_value'])
		{
			# get url-segments with cookies on
			$data 	= $segments->getData();
			
			# add the page for contact form to the segments with cookies
			$data[]	= $this->pluginSettings['page_value'];
			
			$segments->setData($data);
		}
	}

	# create the output
	public function onHtmlLoaded($html)
	{
		if($this->active && $this->getPath() == $this->pluginSettings['page_value'])
		{
			$content = $html->getData();
			
			# add css
			# $this->addCSS('/contactform/css/contactform.css');
			
			# check if form data have been stored
			$formdata = $this->getFormdata('contactform');

			if($formdata)
			{
				if($formdata == 'bot')
				{
					$result = '<div class="mailresult"><h3>Sorry!</h3><p>But we think you are a bot...</p></div>';
				}
				else
				{
					$send = false; 
					
					if(isset($this->container['mail']))
					{ 
						$mail = $this->container['mail'];
						$mail->addAdress($this->pluginSettings['mailto']);
						$mail->addReplyTo($formdata['email'], $formdata['name']);
						$mail->setSubject($formdata['subject']);
						$mail->setBody($formdata['message']);
						$send = $mail->send();
					}

					if($send === true)
					{
						$result = '<div class="mailresult">' . $this->markdownToHtml($this->pluginSettings['message_success']) . '</div>';
					}
					else
					{
						$result = '<div class="mailresult">' . $this->markdownToHtml($this->pluginSettings['message_error']) . '</div>';
					}
				}
	
				# add thank you to the content
				$content = $content . '<div class="tm-contactresult">' . $result . '</div>';
			}
			else
			{
				# get the public forms for the plugin
				$contactform = $this->generateForm('contactform');				
				
				# add forms to the content
				$content = $content . '<div class="tm-contactform">' . $contactform . '</div>';					
			}
			$html->setData($content);
		}
	}

	public function onPageReady($data)
	{
		if(!$this->active && $this->container['flash'] && strpos($this->getPath(), 'tm/plugins') !== false)
		{
			$pagedata = $data->getData();
			$pagedata['messages']['error'] = ['You have to activate the mail-plugin to use the contact form'];
			$data->setData($pagedata);
		}
	}	
}