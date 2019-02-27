<?php
/**
 * @license     http://mit-license.org/
 * @link        https://github.com/thephpleague/oauth2-server
 */
namespace RCTrials\Repositories;
use League\OAuth2\Server\CryptKey;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\UserRepositoryInterface;

use RCTrials\DatabaseManager;
use RCTrials\Entities\AccessTokenEntity;
use RCTrials\Entities\UserEntity;

class UserRepository implements UserRepositoryInterface
{
	/**
	 * {@inheritdoc}
	 */
	public function getUserEntityByUserCredentials(
		$username,
		$password,
		$grantType,
		ClientEntityInterface $clientEntity
	) {
		// Retrieve record from the db
		$db = DatabaseManager::getInstance();
		$record = $db->getUserByLogin( $username, $password );

		if( $record !== false ) {
			return new UserEntity( $record->id );
		} else {
			return false;
		}

		// otherwise return nothing
		return;
	}
}
