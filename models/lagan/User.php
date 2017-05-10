<?php

namespace Lagan\Model;

/**
 * Example Lagan content model
 */

class User extends \Lagan\Lagan {

	function __construct() {
		$this->type = 'user';
		
		// Description in admin interface
		$this->description = 'User settings.';

		$this->properties = [
			// Allways have a title
			[
				'name' => 'title',
				'description' => 'Name',
				'required' => true,
				'searchable' => true,
				'type' => '\Lagan\Property\Str',
				'input' => 'text',
				'validate' => 'minlength(3)'
			],
			[
				'name' => 'email',
				'description' => 'Email address',
				'required' => true,
				'searchable' => true,
				'unique' => true,
				'type' => '\Lagan\Property\Str',
				'input' => 'text',
				'validate' => 'emaildomain'
			],
			[
				'name' => 'password',
				'description' => 'Password',
				'required' => true,
				'type' => '\Lagan\Property\Passwordhash',
				'input' => 'text',
				'validate' => 'minlength(3)'
			],
			[
				'name' => 'reset',
				'description' => 'Password reset code',
				'type' => '\Lagan\Property\Str',
				'input' => 'readonly'
			]
			// TO DO: Add a role
		];
	}

}

?>