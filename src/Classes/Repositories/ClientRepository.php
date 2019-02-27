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
	/**
	 * {@inheritdoc}
	 */
	public function getClientEntity($clientIdentifier, $grantType = null, $clientSecret = null, $mustValidateSecret = true)
	{
		$clients = [
			'rctrials.tk' => [
				'secret'          => password_hash('doascience', PASSWORD_BCRYPT),
				'name'            => 'Randomized Controlled Trials Tool',
				'redirect_uri'    => 'https://RCTrials.tk/',
				'is_confidential' => true,
			],
		];
		// Check if client is registered
		if (array_key_exists($clientIdentifier, $clients) === false) {
			return;
		}
		if (
			$mustValidateSecret === true
			&& $clients[$clientIdentifier]['is_confidential'] === true
			&& password_verify($clientSecret, $clients[$clientIdentifier]['secret']) === false
		) {
			return;
		}
		$client = new ClientEntity();
		$client->setIdentifier($clientIdentifier);
		$client->setName($clients[$clientIdentifier]['name']);
		$client->setRedirectUri($clients[$clientIdentifier]['redirect_uri']);
		return $client;
	}
}
