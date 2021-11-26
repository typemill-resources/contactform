<?php

namespace Plugins\contactform;

use \Typemill\Plugin;

class ContactForm extends Plugin
{
	protected $settings;
	protected $pluginSettings;
	protected $originalHtml;
	protected $contactpage;
	
    public static function getSubscribedEvents()
    {
		return array(
			'onSessionSegmentsLoaded' 	=> 'onSessionSegmentsLoaded',
			'onHtmlLoaded' 				=> 'onHtmlLoaded',
		);
    }	
		
	# add the path stored in user-settings to initiate session and csrf-protection
	public function onSessionSegmentsLoaded($segments)
	{
		$this->settings = $this->getSettings();
		$this->pluginSettings = $this->getPluginSettings('contactform');
		$this->contactpage = trim($this->pluginSettings['page_value'], '/');

		if($this->path == $this->contactpage )
		{
			# get url-segments with cookies on
			$data 	= $segments->getData();
			
			# add the page for contact form to the segments with cookies
			$data[]	= $this->contactpage;
			$data[] = '/' . $this->contactpage;
			
			$segments->setData($data);
		}
	}

	# create the output
	public function onHtmlLoaded($html)
	{
		if($this->path == $this->contactpage)
		{
			$content = $html->getData();

			# check if form data have been stored
			$formdata = $this->getFormdata('contactform');

			if($formdata)
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
	
				# add thank you to the content
				$content = $content . '<div class="tm-contactresult">' . $result . '</div>';
			}
			else
			{
				# get the public forms for the plugin
				$contactform = $this->generateForm('contactform', 'form.save');				
				
				# add forms to the content
				$content = $content . '<div class="tm-contactform">' . $contactform . '</div>';					
			}
			$html->setData($content);
		}
	}
}