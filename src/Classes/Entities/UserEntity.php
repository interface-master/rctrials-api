<?php
/**
 * @license     http://mit-license.org/
 * @link        https://github.com/thephpleague/oauth2-server
 */

namespace RCTrials\Entities;

use League\OAuth2\Server\Entities\UserEntityInterface;

class UserEntity implements UserEntityInterface {

	function __construct( $id ) {
		$this->id = $id;
	}

	/**
	 * Return the user's identifier.
	 *
	 * @return mixed
	 */
	public function getIdentifier() {
		return $this->id;
	}

}
