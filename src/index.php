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

use MRCT\Repositories\ClientRepository;
use MRCT\Repositories\AccessTokenRepository;
use MRCT\Repositories\ScopeRepository;
use MRCT\Repositories\UserRepository;
use MRCT\Repositories\RefreshTokenRepository;

use MRCT\DatabaseManager;

DEFINE( 'PATH_RSA_KEYS', 'file://'.__DIR__.'/../keys/' );


$app = new \Slim\App([
	'settings' => [
		'displayErrorDetails' => true, // TODO: remove this for production
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
$container['db'] = DatabaseManager::getInstance();


// CORS
$app->options('/{routes:.+}', function ($request, $response, $args) {
	return $response;
});
$app->add(function ($req, $res, $next) {
	$response = $next($req, $res);
	return $response
		->withHeader('Access-Control-Allow-Origin', '*')
		->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
		->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS')
		->withHeader('Access-Control-Max-Age', '600');
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

$app->post( '/validate/email',
	function( Request $request, Response $response ) use ( $app ) {
		$output = new \stdClass();
		$email = $request->getParam('username');
		$user = $this->db->getUserByEmail( $email );
		if( $user !== false ) {
			$output = array(
				'salt' => $user->salt
			);
		}
		$response = $response->withHeader( 'Content-type', 'application/json' );
		$response = $response->withJson( $output );
		return $response;
	}
);

$app->post( '/validate/login',
	function( Request $request, Response $response ) use ( $app ) {
		$server = $app->getContainer()->get(AuthorizationServer::class);
		try {
			$output = new \stdClass();
			$email = $request->getParam('username');
			$hash = $request->getParam('password');
			$user = $this->db->getUserByLogin( $email, $hash );
			if( $user !== false ) {
				$resp = $server->respondToAccessTokenRequest($request, $response);
				$respjson = array();
				if( $resp !== false ) {
					// get response body
					$respbody = $resp->getBody();
					$respbody->rewind();
					$respjson = json_decode( $respbody->getContents() );
					// modify response with user info
					$respjson->id = $user->id;
					$respjson->name = $user->name;
					$respjson->role = $user->role;
				}
			} else {
				$respjson = array(
					'error' => "incorrect_login",
					'message' => "Username or Password is incorrect"
				);
			}
			$response = $response->withHeader( 'Content-type', 'application/json' );
			$response = $response->withJson( $respjson );
			return $response;
		} catch (OAuthServerException $exception) {
			// All instances of OAuthServerException can be converted to a PSR-7 response
			return $exception->generateHttpResponse($response);
		} catch (\Exception $exception) {
			// Catch unexpected exceptions
			$body = $response->getBody();
			$body->write( $exception->getMessage() );
			return $response->withStatus(500)->withBody( $body );
		}
	}
);


// USER INFO
$app->get( '/user/details',
	function( Request $request, Response $response ) use ( $app ) {
		// $uid = $this->db->getUserFromAuth( $request->getHeader('authorization') );
		$output = new \stdClass();
		$user = $this->db->getUserByAuth( $request->getAttribute('oauth_access_token_id') );
		if( $user !== false ) {
			$output = array(
				'uid' => $user->uid,
				'email' => $user->email,
				'name' => $user->name,
				'role' => $user->role
			);
		}
		$response = $response->withHeader( 'Content-type', 'application/json' );
		$response = $response->withJson( $output );
		return $response;
	}
)->add( new ResourceServerMiddleware($app->getContainer()->get(ResourceServer::class)) );


// TESTING
// TODO: remove this
$app->get('/hello/{name}', function (Request $request, Response $response, array $args) {
	$name = $args['name'];

	// $output = $this->db->getCursor();

	// $response = $response->withHeader( 'Content-type', 'application/json' );
	// $response = $response->withJson( $output );

	$response->getBody()->write("Hello, $name");
	return $response;
})->add( new ResourceServerMiddleware($app->getContainer()->get(ResourceServer::class)) );

$app->run();
