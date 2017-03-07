<?php 
/**
 * Crawler Web Application
 *
 * @copyright  Copyright (c) 2017 Flavius Rosu (http://www.webdesignrr.ro)
 * @version    1.0
 */

/**
 * Emails.php
 * here we have the emails model
 * @category   Crawler
 * @package    Console
 * @author     Flavius Rosu
 */

class Console_Model_Emails
{
	/**
	 * made method wraped around mail function
	 * @param string $to
	 * @param string $subject
	 * @param string $text
	 * @return void
	 */
	public function send($to, $subject, $text)
	{
		$Sender = 'Web Crawler<...>';
		$customHeader = '';
		$headers[] = 'MIME-Version: 1.0';
		$headers[] = 'Content-type: text/html; charset=iso-8859-1';
		$headers[] = 'From: '.$Sender;
		$headers[] = 'Reply-To: ';
		$headers[] = 'X-Mailer: PHP/' . phpversion();
		
		mail($to, $subject, $text, implode("\r\n", $headers));
	}
}