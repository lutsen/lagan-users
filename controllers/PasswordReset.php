<?php

// Generate secret and send email
class PasswordReset {

	public function forgot( $email ) {
		$u = new \Lagan\Model\User;

		// Set reset secret
		// (Throws exception if user does not exist)
		$secret = bin2hex(openssl_random_pseudo_bytes(32)); // http://stackoverflow.com/a/29137661/496538
		$user = $u->read( $email, 'email' );
		$user = $u->update([ 'reset'=> $secret ], $user->id);

		// Send email
		$e = new Email( 'info@hoverkraft.nl', 'HoverKraft' );
		$e->send(
			'email_forgot.html',
			$user->email,
			[ 'app_url' => APP_URL, 'secret' => $secret, 'email' => urlencode( $user->email ) ]
		);
	}

	public function reset( $secret, $email, $password ) {
		$u = new \Lagan\Model\User;
		$user = $u->read( $email, 'email' );
		if ( strlen($user->reset) > 0 && $user->reset == $secret ) {
			$u->update([ 'reset'=> '', 'password' => $password ], $user->id); // Cannot set reset to NULL because then Lagan won't update it
		} else {
			throw new \Exception('This user does not exist.');
		}
	}

}

?>