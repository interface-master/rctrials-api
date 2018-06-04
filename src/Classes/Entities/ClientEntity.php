<?php
/**
 * @author      Michael Vinogradov <interface.master@gmail.com>
 * @copyright   Copyright (c) Michael Vinogradov
 * @license     http://mit-license.org/
 *
 * @link        https://github.com/thephpleague/oauth2-server
 */
namespace MRCT\Entities;

use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\Traits\ClientTrait;
use League\OAuth2\Server\Entities\Traits\EntityTrait;

class ClientEntity implements ClientEntityInterface {
	use EntityTrait, ClientTrait;

	public function setName($name) {
		$this->name = $name;
	}

	public function setRedirectUri($uri) {
		$this->redirectUri = $uri;
	}
}
