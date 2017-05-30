<?php

/**
 * The user routes.
 */

/*

TO DO: CSRF: https://github.com/slimphp/Slim-Csrf

*/

// Add Session middleware
// Remeber to add "bryanjhv/slim-session": "^3.0" to your composer file.
$app->add(new \Slim\Middleware\Session([
	'name' => 'lagan_user_session',
	'autorefresh' => true,
	'lifetime' => '1 hour'
]));

// Add authentication service
$container['auth'] = function ($container) {
	return new \Auth($container);
};

/**
 * Sanitize input data. Throws an exception if restricted variables are found.
 *
 * @var string[] $data Input data array
 * @var string[] $restricted Array with variable names that are not allowed
 */
function sanitizeData( $data, $restricted ) {
	foreach ($data as $key => $value) {
		if ( in_array($key, $restricted) ) {
			throw new \Exception('You are not allowed to change this variable.');
		}
	}
}

// User routes
$app->group('/user', function () {

	// List
	$this->get('[/]', function ($request, $response, $args) {
		$u = new \Lagan\Model\User;

		// Show list of users
		return $this->view->render(
			$response, 'user/users.html', [
				'users' => $u->read(),
				'authenticated' => $this->auth->session->authenticated,
				'flash' => $this->flash->getMessages()
			]
		);

	})->setName('listusers');

	// Form to add new user
	$this->get('/add', function ($request, $response, $args) {
		// Show form
		return $this->view->render($response, 'user/user.html', [
			'method' => 'post',
			'flash' => $this->flash->getMessages()
		]);
	})->setName('adduser');

	// Forgot form
	$this->get('/forgot', function ($request, $response, $args) {
		return $this->view->render($response, 'user/forgot.html', [ 'flash' => $this->flash->getMessages() ]);
	})->setName('forgotform');

	// Forgot action
	$this->post('/forgot', function ($request, $response, $args) {
		try {
			// Set reset key and send email
			$data = $request->getParsedBody();
			$p = new \PasswordReset;
			$p->forgot( $data['email'] );

			return $this->view->render($response, 'user/message.html', [
				'message' => '<p>You will receive an email with a link to reset your password.</a></p>'
			]);
		} catch (Exception $e) {
			$this->flash->addMessage( 'error', $e->getMessage() );

			return $response->withStatus(302)->withHeader(
				'Location',
				$this->get('router')->pathFor( 'forgotform' )
			);
		}
	})->setName('forgotuser');

	// Reset form
	$this->get('/reset/{email}/{secret}', function ($request, $response, $args) {
		return $this->view->render($response, 'user/reset.html', [
			'flash' => $this->flash->getMessages(),
			'email' => $args['email'],
			'secret' => $args['secret']
		]);
	})->setName('resetform');

	// Reset action
	$this->post('/reset/{email}/{secret}', function ($request, $response, $args) {
		try {
			// Reset password
			$data = $request->getParsedBody();
			$p = new \PasswordReset;
			$p->reset( $args['secret'], urldecode( $args['email'] ), $data['password'] );

			return $this->view->render($response, 'user/message.html', [
				'message' => '<p>You can now <a href="'.$this->get('router')->pathFor( 'loginform' ).'">login</a></p>'
			]);
		} catch (Exception $e) {
			$this->flash->addMessage( 'error', $e->getMessage() );

			return $response->withStatus(302)->withHeader(
				'Location',
				$this->get('router')->pathFor( 'resetform', [ 'email' => urlencode( $args['email'] ), 'secret' => $args['secret'] ] )
			);
		}
	})->setName('resetuser');

	// Login form
	$this->get('/login', function ($request, $response, $args) {
		return $this->view->render($response, 'user/login.html', [ 'flash' => $this->flash->getMessages() ]);
	})->setName('loginform');

	// Login action
	$this->post('/login', function ($request, $response, $args) {
		$u = new \Lagan\Model\User;
		$data = $request->getParsedBody();

		try {
			$user = $u->read( $data['email'], 'email' );

			if ( password_verify ( $data['password'] , $user->password ) ) {
				// Create session for user
				$this->auth->login($user);

				$this->flash->addMessage( 'success', 'You are logged in as '.$user->title.'.' );
				return $response->withStatus(302)->withHeader(
					'Location',
					$this->get('router')->pathFor( 'getuser', [ 'id' => $user->id ] )
				);
			} else {
				throw new \Exception('This email password combination is not correct.');
			}
		} catch (Exception $e) {
			$this->flash->addMessage( 'error', $e->getMessage() );

			return $response->withStatus(302)->withHeader(
				'Location',
				$this->get('router')->pathFor( 'loginform' )
			);
		}
	})->setName('loginuser');

	// Logout
	$this->get('/logout', function ($request, $response, $args) {

		// Remove user from session
		$this->auth->logout();
		$this->flash->addMessage( 'success', 'You are logged out.' );

		return $response->withStatus(302)->withHeader(
			'Location',
			$this->get('router')->pathFor( 'loginform' )
		);
	})->setName('logout');

	// View existing user
	$this->get('/{id}', function ($request, $response, $args) {
		$u = new \Lagan\Model\User;

		try {

			// Show populated form
			return $this->view->render($response, 'user/user.html', [
				'method' => 'put',
				'flash' => $this->flash->getMessages(),
				'user' => $u->read( $args['id'] ),
				'authenticated' => $this->auth->session->authenticated
			]);

		} catch (Exception $e) {
			$this->flash->addMessage( 'error', $e->getMessage() );

			return $response->withStatus(302)->withHeader(
				'Location',
				$this->get('router')->pathFor( 'listusers' )
			);
		}

	})->setName('getuser');

	// Add
	$this->post('[/]', function ($request, $response, $args) {
		$u = new \Lagan\Model\User;
		$data = $request->getParsedBody();

		try {
			sanitizeData( $data, [ 'id', 'modified', 'reset' ] ); // Throws exception on fail
			$user = $u->create( $data );
			$this->flash->addMessage( 'success', $user->title.' is added.' );

			return $response->withStatus(302)->withHeader(
				'Location',
				$this->get('router')->pathFor( 'getuser', [ 'id' => $user->id ] )
			);
		} catch (Exception $e) {
			$this->flash->addMessage( 'error', $e->getMessage() );

			return $response->withStatus(302)->withHeader(
				'Location',
				$this->get('router')->pathFor( 'adduser' )
			);
		}
	})->setName('postuser');

	// Update
	$this->put('/{id}', function ($request, $response, $args) {
		$u = new \Lagan\Model\User;
		$data = $request->getParsedBody();

		try {

			// Check if user is logged in
			$user = $u->read( $args['id'] );
			$this->auth->check( $user->id ); // Throws exception on fail

			sanitizeData( $data, [ 'id', 'modified', 'reset' ] ); // Throws exception on fail
			$u->update( $data , $args['id'] );
			$this->flash->addMessage( 'success', $user->title.' is updated.' );

		} catch (Exception $e) {
			$this->flash->addMessage( 'error', $e->getMessage() );
		}

		return $response->withStatus(302)->withHeader(
			'Location',
			$this->get('router')->pathFor( 'getuser', [ 'id' => $args['id'] ] )
		);
	})->setName('putuser');

	// Delete
	$this->delete('/{id}', function ($request, $response, $args) {
		$u = new \Lagan\Model\User;
		
		try {

			// Check if user is logged in
			$user = $u->read( $args['id'] );
			$this->auth->check( $user->id ); // Throws exception on fail

			$u->delete( $args['id'] );
			$this->flash->addMessage( 'success', 'The user is deleted.' );

		} catch (Exception $e) {
			$this->flash->addMessage( 'error', $e->getMessage() );
		}
		return $response->withStatus(302)->withHeader(
			'Location',
			$this->get('router')->pathFor( 'listusers' )
		);
	})->setName('deleteuser');

});

?>