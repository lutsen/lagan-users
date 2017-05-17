<?php

class Auth {
	public $session;

	public function __construct() {
		$this->session = new \SlimSession\Helper;
	}

	public function login( $user ) {
		$this->session->set('authenticated', [
			'name' => $user->title,
			'email' => $user->email,
			'id' => $user->id
		]);
	}

	public function logout() {
		$this->session->delete('authenticated');
	}

	/**
	 * Check if logged in user has right properties. Throws an exception if not.
	 *
	 * @var string[] $allowed String with single id or array with allowed user id's
	 *
	 * @return string[] Array with logged in user name, email and id.
	 */
	public function check( $allowed ) {
		if ( isset( $this->session->authenticated ) ) {
			if (
				( is_array( $allowed ) && in_array( $app->session->authenticated['id'], $allowed ) )
				||
				$this->session->authenticated['id'] == $allowed

			) {
				return $this->session->authenticated;
			} else {
				throw new \Exception('You are not allowed to perform this action.');
			}
		} else {
			throw new \Exception('You need to log in to perform this action.');
		}
	}

}

?>