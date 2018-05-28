<?php

require './vendor/autoload.php';

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\CryptKey;
use League\OAuth2\Server\ResourceServer;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Grant\PasswordGrant;
use League\OAuth2\Server\Middleware\AuthorizationServerMiddleware;
use League\OAuth2\Server\Middleware\ResourceServerMiddleware;

include_once( __DIR__ . '/utils/DatabaseManager.php' );
use PS\DatabaseManager;

DEFINE( 'PATH_RSA_KEYS', 'file://'.__DIR__.'/../keys/' );


$app = new \Slim\App([
	'settings' => [
		'displayErrorDetails' => true,
	],
	// Add the authorization server to the DI container
	AuthorizationServer::class => function () {
		// Setup the authorization server
		$server = new AuthorizationServer(
			new ClientRepository(),       // instance of ClientRepositoryInterface
			new AccessTokenRepository(),  // instance of AccessTokenRepositoryInterface
			new ScopeRepository(),        // instance of ScopeRepositoryInterface
			PATH_RSA_KEYS.'private.key',  // path to private key
			PATH_RSA_KEYS.'public.key'    // path to public key
		);
		// password grant
		$grant_pass = new PasswordGrant(
			new UserRepository(),           // instance of UserRepositoryInterface
			new RefreshTokenRepository()    // instance of RefreshTokenRepositoryInterface
		);
		$grant_pass->setRefreshTokenTTL(new \DateInterval('P1M')); // refresh tokens will expire after 1 month
		// Enable the password grant on the server with a token TTL of 1 hour
		$server->enableGrantType(
			$grant_pass,
			new \DateInterval('PT1H') // access tokens will expire after 1 hour
		);
		return $server;
	},
	ResourceServer::class => function () {
		$server = new ResourceServer(
			new AccessTokenRepository(),
			PATH_RSA_KEYS.'public.key'
		);
		return $server;
	},
	ResourceServerMiddleware::class => function() {
		$AccessTokenRepository = new AccessTokenRepository();
		$publicKeyPath = PATH_RSA_KEYS.'public.key';
		$server = new ResourceServer(
			$accessTokenRepository,
			$publicKeyPath
		);
		return new ResourceServerMiddleware( $server );
	}
]);

// get container
$container = $app->getContainer();
// set up us the database
$container['db'] = new DatabaseManager(); // host,port,db,usr,pass


// CORS
$app->options('/{routes:.+}', function ($request, $response, $args) {
	return $response;
});
$app->add(function ($req, $res, $next) {
	$response = $next($req, $res);
	return $response
		->withHeader('Access-Control-Allow-Origin', '*')
		->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
		->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
});

// REGISTRATION ENDPOINT
$app->post( '/register',
	function( Request $request, Response $response ) use ( $app ) {
		$obj = new \stdClass();
		$obj->email = $request->getParam('email');
		$obj->pass = $request->getParam('pass'); // TODO: DO NOT SAVE THIS
		$obj->name = $request->getParam('name');
		$obj->role = $request->getParam('role');
		$obj->salt = $request->getParam('salt');
		$obj->hash = $request->getParam('hash');
		// check if email exists
		$output = $this->db->newPlayer( $obj );
		// output
		$response = $response->withHeader( 'Content-type', 'application/json' );
		$response = $response->withJson( $output );
		return $response;
	}
);


// TESTING
// TODO: remove this
// $app->get('/hello/{name}', function (Request $request, Response $response, array $args) {
// 	$name = $args['name'];
//
// 	$output = $this->db->getCursor();
//
// 	// $response = $response->withHeader( 'Content-type', 'application/json' );
// 	// $response = $response->withJson( $output );
//
// 	$response->getBody()->write("Hello, $name");
// 	return $response;
// });

$app->run();
