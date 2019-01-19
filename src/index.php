<?php

require './vendor/autoload.php';

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\CryptKey;
use League\OAuth2\Server\ResourceServer;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Grant\PasswordGrant;
use League\OAuth2\Server\Grant\RefreshTokenGrant;
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

		// refresh grant
		$grant_refresh = new RefreshTokenGrant(
			new RefreshTokenRepository()
		);
		$grant_refresh->setRefreshTokenTTL(new \DateInterval('P1M'));
		$server->enableGrantType(
			$grant_refresh,
			new \DateInterval('PT1H')
		);
		$server->grant_refresh = $grant_refresh;

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

// ADMIN REGISTRATION
/**
 * @api {post} /register Register new Admin user
 * @apiName PostRegister
 * @apiGroup Admin
 *
 * @apiParam {String} email Admin's email address.
 * @apiParam {String} email Admin's email address.
 *
 * @apiSuccess {String} uuid A Unique ID for the Admin.
 *
 */
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
		$output = $this->db->newAdmin( $obj );
		// output
		$response = $response->withHeader( 'Content-type', 'application/json' );
		$response = $response->withJson( $output );
		return $response;
	}
);

// CONFIRMS ADMIN EMAIL IN THE SYSTEM
/**
 * @api {post} /validate/email Validates if an email address exists in the system
 * @apiName PostValidateEmail
 * @apiGroup Admin
 *
 */
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
// CONFIRMS ADMIN CREDENTIALS
/**
 * @api {post} /validate/login Validates login credentials and returns an OAuth token
 * @apiName PostValidateLogin
 * @apiGroup Admin
 *
 */
$app->post( '/validate/login',
	function( Request $request, Response $response ) use ( $app ) {
		$server = $app->getContainer()->get(AuthorizationServer::class);
		try {
			return $server->respondToAccessTokenRequest($request, $response);

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
/**
 * @api {get} /user/details Returns user details based on OAuth token
 * @apiName GetUserDetails
 * @apiGroup Admin
 *
 */
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
// ADMIN LIST TRIALS
/**
 * @api {get} /user/trials Returns an array of `Trials` belonging to the current Admin
 * @apiName GetUserTrials
 * @apiGroup Admin
 *
 */
$app->get( '/user/trials',
	function( Request $request, Response $response ) use ( $app ) {
		$output = new \stdClass();
		$user = $this->db->getUserByAuth( $request->getAttribute('oauth_access_token_id') );
		$output = $this->db->getUserTrials( $user->uid );
		$response = $response->withHeader( 'Content-type', 'application/json' );
		$response = $response->withJson( $output );
		return $response;
	}
)->add( new ResourceServerMiddleware($app->getContainer()->get(ResourceServer::class)) );
// ADMIN TRIAL DETAILS
/**
 * @api {get} /trial/:tid Returns the complete details for a given Trial ID
 * @apiName GetTrial
 * @apiGroup Admin
 *
 */
$app->get( '/trial/{tid}',
	function( Request $request, Response $response, array $args ) use ( $app ) {
		$output = new \stdClass();
		$user = $this->db->getUserByAuth( $request->getAttribute('oauth_access_token_id') );
		$tid = $args['tid'];
		$output = $this->db->getTrialDetails( $user->uid, $tid );
		$response = $response->withHeader( 'Content-type', 'application/json' );
		$response = $response->withJson( $output );
		return $response;
	}
)->add( new ResourceServerMiddleware($app->getContainer()->get(ResourceServer::class)) );

// ADMIN NEW TRIAL
/**
 * @api {post} /new/trial Creates a new Trial
 * @apiName PostNewTrial
 * @apiGroup Admin
 *
 */
$app->post( '/new/trial',
	function( Request $request, Response $response ) use ( $app ) {
		$output = new \stdClass();
		$user = $this->db->getUserByAuth( $request->getAttribute('oauth_access_token_id') );
		if( $user !== false ) {
			$trial = json_decode( $request->getParam('trial') );
			$trial->uid = $user->uid;
			$output = $this->db->newTrial( $trial );
		}
		$response = $response->withHeader( 'Content-type', 'application/json' );
		$response = $response->withJson( $output );
		return $response;
	}
)->add( new ResourceServerMiddleware($app->getContainer()->get(ResourceServer::class)) );


// SUBJECT REGISTRATION
/**
 * @api {post} /register/:tid Registers a new Subject into a given Trial ID
 * @apiName PostRegisterForTrial
 * @apiGroup Subject
 *
 */
$app->post( '/register/{tid}',
	function( Request $request, Response $response, array $args ) use ( $app ) {
		$output = new \stdClass();
		$tid = $args['tid'];
		// output
		$output = $this->db->newSubject( $tid );
		$response = $response->withHeader( 'Content-type', 'application/json' );
		$response = $response->withJson( $output );
		return $response;
	}
);

// TRIAL SURVEY LIST
/**
 * @api {get} /trial/:tid/surveys Returns a list of Surveys from a given Trial ID for a given Subject ID
 * @apiName GetTrialSurveys
 * @apiGroup Subject
 *
 */
$app->get( '/trial/{tid}/surveys',
	function( Request $request, Response $response, array $args ) use ( $app ) {
		$output = new \stdClass();
		$tid = $args['tid'];
		$uid = $request->getParam('uuid');;
		// output
		$output = $this->db->getSubjectSurveys( $uid, $tid );
		$response = $response->withHeader( 'Content-type', 'application/json' );
		$response = $response->withJson( $output );
		return $response;
	}
);

// SUBJECT SURVEY POST
/**
 * @api {post} /trial/:tid/survey/:sid Stores Survey answers to a given Trial and Survey
 * @apiName PostSurveyAnswers
 * @apiGroup Subject
 *
 */
$app->post( '/trial/{tid}/survey/{sid}',
	function( Request $request, Response $response, array $args ) use ( $app ) {
		$output = new \stdClass();
		$tid = $args['tid'];
		$sid = $args['sid'];
		$uid = $request->getParam('uuid');
		$answers = json_decode( $request->getParam('answers') );
		// output
		$output->success = $this->db->saveSurveyAnswers( $uid, $tid, $sid, $answers );
		$output->answers = $answers;
		$response = $response->withHeader( 'Content-type', 'application/json' );
		$response = $response->withJson( $output );
		return $response;
	}
);

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
