<?php
/**
 * @author      Michael Vinogradov <interface.master@gmail.com>
 * @copyright   Copyright (c) Michael Vinogradov
 * @license     http://mit-license.org/
 *
 * @link        https://github.com/thephpleague/oauth2-server
 */
namespace MRCT\Entities;

use League\OAuth2\Server\Entities\AuthCodeEntityInterface;
use League\OAuth2\Server\Entities\Traits\AuthCodeTrait;
use League\OAuth2\Server\Entities\Traits\EntityTrait;
use League\OAuth2\Server\Entities\Traits\TokenEntityTrait;

class AuthCodeEntity implements AuthCodeEntityInterface {
	use EntityTrait, TokenEntityTrait, AuthCodeTrait;
}
