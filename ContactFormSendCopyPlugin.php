<?php
namespace Craft;

class ContactFormSendCopyPlugin extends BasePlugin
{
	function getName()
	{
		return Craft::t('Contact Form Send Copy');
	}

	function getVersion()
	{
		return '1.1';
	}

	function getDeveloper()
	{
		return 'Noregt';
	}

	function getDeveloperUrl()
	{
		return 'http://www.noregt.com';
	}

    public function init()
    {
        craft()->on('email.onBeforeSendEmail', function(Event $event) {
            $email = $event->params['emailModel'];

            if(!isset($event->params['variables']['secondEmail'])) {
                //check if email was sent via the Contact Form Plugin
                $settings = craft()->plugins->getPlugin('contactform')->getSettings();
                if(strpos($email->fromName, $settings->prependSender) === 0 &&
                    strpos($email->subject, $settings->prependSubject) === 0) {

                    //this is the email for the system administration
                    $email->fromEmail = $email->replyTo;
                    $email->body = "Message from our contactform \n\n
                        ".$email->fromName."\n\n
                        Email address: ".$email->replyTo."\n\n
                        The message: \n\n ".$email->body;
                }
            }
        });

        //logic for sending a second email
        craft()->on('email.onSendEmail', function(Event $event) {
           //  echo "Variables: <pre>".print_r($event->params['variables'], true)."</pre>";
            if(!isset($event->params['variables']['secondEmail'])) {
                //this is the email for the user
                $secondEmail = new EmailModel();
                $emailSettings = craft()->email->getSettings();
                $secondEmail->attributes = $event->params['emailModel']->attributes; //copy all from first email

                //check if email was sent via the Contact Form Plugin
                $settings = craft()->plugins->getPlugin('contactform')->getSettings();
                if(strpos($secondEmail->fromName, $settings->prependSender) === 0 &&
                    strpos($secondEmail->subject, $settings->prependSubject) === 0) {

                    //switch emails and senders manually
                    $secondEmail->subject = $emailSettings['senderName'].': Your message has been received'; //you can put your own text here
                    $secondEmail->toEmail = $secondEmail->replyTo;
                    $secondEmail->fromEmail = $emailSettings['emailAddress'];
                    $secondEmail->replyTo = $emailSettings['emailAddress'];
                    $secondEmail->fromName = $emailSettings['senderName'];

                    $secondEmail->body = "Thanks for your message, we'll respond as soon as possible!\n\n
                    The message: ".$secondEmail->body;

                    $vars['secondEmail'] = true;
                    craft()->email->sendEmail($secondEmail, $vars);
                }
            }
        });

    }
}
