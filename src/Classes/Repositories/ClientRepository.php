<?php
/**
 * @author      Michael Vinogradov <interface.master@gmail.com>
 * @copyright   Copyright (c) Michael Vinogradov
 * @license     http://mit-license.org/
 *
 * @link        https://github.com/thephpleague/oauth2-server
 */
namespace MRCT\Repositories;
use League\OAuth2\Server\Repositories\ClientRepositoryInterface;
use MRCT\Entities\ClientEntity;

class ClientRepository implements ClientRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function getClientEntity($clientIdentifier, $grantType = null, $clientSecret = null, $mustValidateSecret = true)
    {
        $clients = [
            'mrct' => [
                'secret'          => password_hash('doascience', PASSWORD_BCRYPT),
                'name'            => 'Mobile Randomized Controlled Trial',
                'redirect_uri'    => 'https://interfacemaster.ca/',
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