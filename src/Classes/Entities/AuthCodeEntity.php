<?php
/**
 * @license     http://mit-license.org/
 * @link        https://github.com/thephpleague/oauth2-server
 */
namespace RCTrials\Entities;

use League\OAuth2\Server\Entities\AuthCodeEntityInterface;
use League\OAuth2\Server\Entities\Traits\AuthCodeTrait;
use League\OAuth2\Server\Entities\Traits\EntityTrait;
use League\OAuth2\Server\Entities\Traits\TokenEntityTrait;

class AuthCodeEntity implements AuthCodeEntityInterface {
	use EntityTrait, TokenEntityTrait, AuthCodeTrait;
}
