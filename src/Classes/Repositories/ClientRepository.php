<?php
/**
 * @license     http://mit-license.org/
 * @link        https://github.com/thephpleague/oauth2-server
 */
namespace RCTrials\Repositories;
use League\OAuth2\Server\Repositories\ClientRepositoryInterface;
use RCTrials\Entities\ClientEntity;

class ClientRepository implements ClientRepositoryInterface
{
	const CLIENT_NAME = 'Randomized Controlled Trials Tool';
	const REDIRECT_URI = 'https://rctrials.interfacemaster.ca/';

	/**
	 * {@inheritdoc}
	 */
	public function getClientEntity($clientIdentifier)
	{
		$client = new ClientEntity();
		$client->setIdentifier($clientIdentifier);
		$client->setName($clients[$clientIdentifier]['name']);
		$client->setRedirectUri($clients[$clientIdentifier]['redirect_uri']);
		return $client;
	}

	public function validateClient($clientIdentifier, $clientSecret = null, $grantType = null) {
		$clients = [
			'rctrials.interfacemaster.ca' => [
				'secret'          => password_hash('doascience', PASSWORD_BCRYPT),
				'name'            => self::CLIENT_NAME,
				'redirect_uri'    => self::REDIRECT_URI,
				'is_confidential' => true,
			],
		];
		// Check if client is registered
		if (array_key_exists($clientIdentifier, $clients) === false) {
			return false;
		}
		if (
			$clients[$clientIdentifier]['is_confidential'] === true
			&& password_verify($clientSecret, $clients[$clientIdentifier]['secret']) === false
		) {
			return false;
		}

		return true;
	}
}
