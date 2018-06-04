<?php
/**
 * @author      Michael Vinogradov <interface.master@gmail.com>
 * @copyright   Copyright (c) Michael Vinogradov
 * @license     http://mit-license.org/
 *
 * @link        https://github.com/thephpleague/oauth2-server
 */
namespace MRCT\Repositories;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\ScopeRepositoryInterface;
use MRCT\Entities\ScopeEntity;
class ScopeRepository implements ScopeRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function getScopeEntityByIdentifier($scopeIdentifier)
    {
        $scopes = [
            'basic' => [
                'description' => 'Allows basic access to post data',
            ],
            'admin' => [
                'description' => 'Allows admin access to create studies and view info',
            ]
        ];
        if (array_key_exists($scopeIdentifier, $scopes) === false) {
            return;
        }
        $scope = new ScopeEntity();
        $scope->setIdentifier($scopeIdentifier);
        return $scope;
    }
    /**
     * {@inheritdoc}
     */
    public function finalizeScopes(
        array $scopes,
        $grantType,
        ClientEntityInterface $clientEntity,
        $userIdentifier = null
    ) {
        // Example of programatically modifying the final scope of the access token
        // if ((int) $userIdentifier === 1) {
        //     $scope = new ScopeEntity();
        //     $scope->setIdentifier('basic');
        //     $scope->setIdentifier('email');
        //     $scopes[] = $scope;
        // }
        // if ((int) $userIdentifier === 2) {
        //     $scope = new ScopeEntity();
        //     $scope->setIdentifier('basic');
        //     $scope->setIdentifier('attendance');
        //     $scopes[] = $scope;
        // }
        return $scopes;
    }
}
