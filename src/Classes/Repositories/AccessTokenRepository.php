<?php
/**
 * @license     http://mit-license.org/
 * @link        https://github.com/thephpleague/oauth2-server
 */
namespace RCTrials\Repositories;

use League\OAuth2\Server\CryptKey;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;

use RCTrials\DatabaseManager;
use RCTrials\Entities\AccessTokenEntity;

class AccessTokenRepository implements AccessTokenRepositoryInterface {
	/**
	 * {@inheritdoc}
	 */
	public function persistNewAccessToken(AccessTokenEntityInterface $accessTokenEntity) {
		// Some logic here to save the access token to a database
		$db = DatabaseManager::getInstance();

		// get the token and necessary info
		$privateKey = new CryptKey( 'file://' . __DIR__ . '/../../../../../ssl/rctrials.key' );
		$tokenJWT = $accessTokenEntity->__toString();

		$obj = new \stdClass();
		$obj->uid = $accessTokenEntity->getUserIdentifier(); // this is the token subscriber: user id
		$obj->tid = $accessTokenEntity->getIdentifier(); // token identifier
		$obj->access_token = $tokenJWT; // this is the token that's sent back to the user
		$obj->date_expires = $accessTokenEntity->getExpiryDateTime()->format("Y-m-d H:i:s"); // this is when the token expires

		$output = $db->saveToken( $obj );
		return $output;
	}
	/**
	 * {@inheritdoc}
	 */
	public function revokeAccessToken($tokenId) {
		// Some logic here to revoke the access token
	}
	/**
	 * {@inheritdoc}
	 */
	public function isAccessTokenRevoked($tokenId) {
		// look up token in db
		$db = DatabaseManager::getInstance();
		$user = $db->getUserByAuth( $tokenId );
		if( $user == false ) {
			return true; // Access token is revoked
		}
		// else return false
		return false; // Access token hasn't been revoked
	}
	/**
	 * {@inheritdoc}
	 */
	public function getNewToken(ClientEntityInterface $clientEntity, array $scopes, $userIdentifier = null) {
		$accessToken = new AccessTokenEntity();
		$accessToken->setClient($clientEntity);
		foreach ($scopes as $scope) {
			$accessToken->addScope($scope);
		}
		$accessToken->setUserIdentifier($userIdentifier);
		return $accessToken;
	}
}
