<?php

// http://alexandre-salome.fr/blog/Generate-Mails-With-Twig

/*

Send emails from Twig templates.

*/

class Email {

	protected $twig;
	protected $from_email;
	protected $from_name;

	public function __construct($from_email, $from_name) {
		$this->from_email = $from_email;
		$this->from_name = $from_name;

		$loader = new Twig_Loader_Filesystem(ROOT_PATH.'/templates/email');
		$this->twig = new Twig_Environment($loader, array(
			'debug' => true,
			'charset' => 'utf-8',
			'cache' => realpath(ROOT_PATH.'/cache-email'),
			'auto_reload' => true,
			'strict_variables' => false,
			'autoescape' => true
		));

	}

	// PHPMailer
	// https://github.com/PHPMailer/PHPMailer

	// @param string $template_name
	// @param string $to Email address
	// @param array $parameters Parameters to be used in email template
	public function send( $template_name, $to, $parameters ) {

		$template = $this->twig->loadTemplate( $template_name );
		
		// Render template
		$subject = trim( preg_replace( '/\s+/', ' ', $template->renderBlock( 'subject', $parameters ) ) );
		$body_text = $template->renderBlock('body_text', $parameters);
		$body_html = $template->renderBlock('body_html', $parameters);
		
		// Send mail PHP Mailer
		$mail = new PHPMailer;
		$mail->setFrom($this->from_email, $this->from_name);
		$mail->addAddress($to); // Add a recipient

		$mail->isHTML(true); // Set email format to HTML

		$mail->Subject = $subject;
		$mail->Body    = $body_html;
		$mail->AltBody = $body_text;

		if( !$mail->send() ) {
			throw new Exception( 'Mailer Error: ' . $mail->ErrorInfo );
		}

	}

}

?>