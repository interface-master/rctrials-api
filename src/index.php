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
 * @api {post} /register New User
 * @apiName PostRegister
 * @apiVersion 0.0.1
 * @apiGroup Admin
 *
 * @apiParam {String} email Admin's email address.
 * @apiParam {String} hash Admin's hashed password address.
 * @apiParam {String} name Admin's name.
 * @apiParam {String=admin} role Admin's role.
 * @apiParam {String} pass `!! REMOVE THIS !!` actual password for testing.
 * @apiParam {String} salt `!! REMOVE THIS !!` salt used for hashing the password.
 *
 * @apiSuccess {String} id A Unique ID for the Admin.
 * @apiSuccessExample {json} Success-Response:
 *     HTTP/1.1 200 OK
 *     {
 *       "id": "xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"
 *     }
 *
 * @apiError EmailExists The <code>email</code> provided already exists in the system.
 * @apiErrorExample {json} Error-Response:
 *     HTTP/1.1 409 Conflict
 *     {
 *       "message": "Email xxx@xxx.xx already exists."
 *     }
 *
 * @apiDescription
 *
 * TODO:
 * - remove pass and salt from being sent and stored
 * - return error when one of the required fields isn't sent
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
		if( strlen($output->error) > 0 ) {
			$response = $response->withStatus( $output->status );
		}
		$response = $response->withHeader( 'Content-type', 'application/json' );
		unset( $output->status );
		$response = $response->withJson( $output );
		return $response;
	}
);

// ########### vvv NEEDS TO BE REMOVED vvv ###########
// CONFIRMS ADMIN EMAIL IN THE SYSTEM
/**
 * @api {post} /validate/email Validate Email
 * @apiPrivate
 * @apiName PostValidateEmail
 * @apiVersion 0.0.1
 * @apiGroup Admin
 *
 * @apiParam {String} username Admin's email address.
 *
 * @apiSuccess {String} salt The salt that was added to the password for hashing.
 *
 * @apiDescription Validates if an email address exists in the system.
 *
 * TODO:
 * - potentially get rid of sending the salt back; should be stored by FE.
 * - currently, returns blank object if email doesn't exist
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
// ########### ^^^ NEEDS TO BE REMOVED ^^^ ###########

// CONFIRMS ADMIN CREDENTIALS
/**
 * @api {post} /validate/login Validate Credentials
 * @apiName PostValidateLogin
 * @apiVersion 0.0.1
 * @apiGroup Admin
 *
 * @apiParam {String} username Admin's email address.
 * @apiParam {String} password Admin's hashed password; hashed via `sha256(pass+salt)``.
 * @apiParam {String} client_id `mrct`
 * @apiParam {String} client_secret `doascience`
 * @apiParam {String} scope `basic`
 * @apiParam {String} grant_type `password`
 *
 * @apiSuccess {String} token_type The value `Bearer`.
 * @apiSuccess {Number} expires_in An integer representing the TTL of the access token.
 * @apiSuccess {String} access_token A JWT signed with the authorization server’s private key.
 * @apiSuccess {String} refresh_token An encrypted payload that can be used to refresh the access token when it expires.
 * @apiSuccessExample {json} Success-Response:
 *     HTTP/1.1 200 OK
 *     {
 *       "token_type": "Bearer",
 *       "expires_in": 3600,
 *       "access_token": "abc...xyz",
 *       "refresh_token": "abc...xyz"
 *     }
 *
 * @apiError InvalidCredentials The <code>username</code> or <code>password</code> provided are incorrect.
 * @apiErrorExample {json} Error-Response:
 *     HTTP/1.1 401 Unauthorized
 *     {
 *       "error": "invalid_credentials",
 *       "message": "The user credentials were incorrect."
 *     }
 *
 * @apiDescription Validates login credentials and returns OAuth access and refresh tokens.
 *
 */
/**
 * @api {post} /validate/login Refresh Token
 * @apiName PostRefreshToken
 * @apiVersion 0.0.1
 * @apiGroup Admin
 *
 * @apiParam {String} refresh_token Admin's `refresh_token` issued with an earlier `/login` call.
 * @apiParam {String} client_id `mrct`
 * @apiParam {String} client_secret `doascience`
 * @apiParam {String} scope `basic`
 * @apiParam {String} grant_type `refresh_token`
 *
 * @apiSuccess {String} token_type The value `Bearer`.
 * @apiSuccess {Number} expires_in An integer representing the TTL of the access token.
 * @apiSuccess {String} access_token A JWT signed with the authorization server’s private key.
 * @apiSuccess {String} refresh_token An encrypted payload that can be used to refresh the access token when it expires.
 * @apiSuccessExample {json} Success-Response:
 *     HTTP/1.1 200 OK
 *     {
 *       "token_type": "Bearer",
 *       "expires_in": 3600,
 *       "access_token": "abc...xyz",
 *       "refresh_token": "abc...xyz"
 *     }
 *
 * @apiError InvalidCredentials The <code>username</code> or <code>password</code> provided are incorrect.
 * @apiErrorExample {json} Error-Response:
 *     HTTP/1.1 401 Unauthorized
 *     {
 *       "error": "invalid_request",
 *       "message": "The refresh token is invalid.",
 *       "hint": "Cannot decrypt the refresh token"
 *     }
 *
 * @apiDescription Validates login credentials and returns OAuth access and refresh tokens.
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
 * @api {get} /user/details User Details
 * @apiName GetUserDetails
 * @apiVersion 0.0.1
 * @apiGroup Admin
 * @apiPermission admin
 *
 * @apiHeader {String} Authorization Admin's `access_token`.
 * @apiHeaderExample {String} Header-Example:
 *     {
 *       "Authorization": "Bearer abc...xyz"
 *     }
 *
 * @apiSuccess {String} uid User's unique ID.
 * @apiSuccess {String} email User's email address.
 * @apiSuccess {String} name User's name.
 * @apiSuccess {String} role User's role.
 * @apiSuccessExample {json} Success-Response:
 *     HTTP/1.1 200 OK
 *     {
 *       "uid": "xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx",
 *       "email": "user@mail.ca",
 *       "name": "Reece Ercher",
 *       "role": "admin"
 *     }
 *
 * @apiError AccessDenied The <code>access_token</code> provided has expired.
 * @apiErrorExample {json} Error-Response:
 *     HTTP/1.1 401 Unauthorized
 *     {
 *       "error": "access_denied",
 *       "message": "The resource owner or authorization server denied the request.",
 *       "hint": "Access token is invalid"
 *     }
 *
 * @apiDescription Returns user details based on the provided OAuth token.
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
 * @api {get} /user/trials List Trials
 * @apiName GetUserTrials
 * @apiVersion 0.0.1
 * @apiGroup Admin
 * @apiPermission admin
 *
 * @apiHeader {String} Authorization Admin's `access_token`.
 * @apiHeaderExample {String} Header-Example:
 *     {
 *       "Authorization": "Bearer abc...xyz"
 *     }
 *
 * @apiSuccess {Object[]} trials User's `Trials`.
 * @apiSuccessExample {json} Success-Response:
 *     HTTP/1.1 200 OK
 *     [{
 *       "tid": "xxxx",
 *       "title": "Test Trial",
 *       "regopen": "0000-00-00 00:00:00",
 *       "regclose": "0000-00-00 00:00:00",
 *       "trialstart": "0000-00-00 00:00:00",
 *       "trialend": "0000-00-00 00:00:00",
 *       "timezone": "TZ database name",
 *       "created": "0000-00-00 00:00:00",
 *       "updated": "0000-00-00 00:00:00"
 *     }...]
 *
 * @apiDescription Returns an array of `Trials` belonging to the current Admin.
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
 * @api {get} /trial/:tid Trial Details
 * @apiName GetTrial
 * @apiVersion 0.0.1
 * @apiGroup Admin
 * @apiPermission admin
 *
 * @apiHeader {String} Authorization Admin's `access_token`.
 * @apiHeaderExample {String} Header-Example:
 *     {
 *       "Authorization": "Bearer abc...xyz"
 *     }
 *
 * @apiSuccess {Object[]} trials User's `Trials`.
 * @apiSuccessExample {json} Success-Response:
 *     HTTP/1.1 200 OK
 *     [{
 *       "tid": "xxxx",
 *       "title": "Test Trial",
 *       "regopen": "0000-00-00 00:00:00",
 *       "regclose": "0000-00-00 00:00:00",
 *       "trialstart": "0000-00-00 00:00:00",
 *       "trialend": "0000-00-00 00:00:00",
 *       "trialtype": "simple",
 *       "timezone": "TZ database name",
 *       "created": "0000-00-00 00:00:00",
 *       "updated": "0000-00-00 00:00:00",
 *       "groups": [{
 *           "gid": ...,
 *           "name": ...,
 *           "size": ...,
 *           "size_n": ...
 *       },...],
 *       "surveys": [{
 *         "sid": ...,
 *         "name": ...,
 *         "groups: "[gid,...]",
 *         "questions": [{
 *           "qid": ...,
 *           "text": ...,
 *           "type": ...,
 *           "options": ...
 *         },...]
 *       },...]
 *     },...]
 *
 * @apiDescription Returns the complete details for a given Trial ID.
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
 * @api {post} /new/trial New Trial
 * @apiName PostNewTrial
 * @apiVersion 0.0.1
 * @apiGroup Admin
 * @apiPermission admin
 *
 * @apiParam {Object} trial Object representing the new `Trial`.
 * @apiParamExample {Object} Request-Example:
 *     {
 *       "title": "Mental Health Trial",
 *       "regopen":"2019-01-07T05:00:00.000Z",
 *       "regclose":"2019-01-14T05:00:00.000Z",
 *       "trialstart":"2019-01-14T05:00:00.000Z",
 *       "trialend":"2019-01-18T05:00:00.000Z",
 *       "trialtype":"simple",
 *       "groups":[{
 *         "group_id":0,
 *         "group_name":"Control",
 *         "group_size":"auto",
 *         "group_size_n":""
 *       },{
 *         "group_id":1,
 *         "group_name":"Experiment",
 *         "group_size":"auto",
 *         "group_size_n":""
 *       }],
 *       "features":[],
 *       "surveys":[{
 *         "survey_id":0,
 *         "survey_name":"Demographics",
 *         "survey_groups":[0,1],
 *         "survey_questions":[{
 *           "question_id":0,
 *           "question_text":"What's your age?",
 *           "question_type":"mc",
 *           "question_options":"under 20|20-30|30-40|40-50|50+"
 *         },{
 *           "question_id":2,
 *           "question_text":"What's your major?",
 *           "question_type":"text",
 *           "question_options":""
 *         },...]
 *       },...],
 *       "timezone":"America/Toronto"}
 *     }
 *
 * @apiHeader {String} Authorization Admin's `access_token`.
 * @apiHeaderExample {String} Header-Example:
 *     {
 *       "Authorization": "Bearer abc...xyz"
 *     }
 *
 * @apiSuccess {Object} confirmation Object confirming the ID of the newly generated trial, the number of groups, surveys, and questions that were created.
 * @apiSuccessExample {json} Success-Response:
 *     HTTP/1.1 200 OK
 *     {
 *       "tid": "xxxx",
 *       "groups": number,
 *       "surveys": number,
 *       "questions": number,
 *     }
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
 * @api {post} /register/:tid Register
 * @apiName PostRegisterForTrial
 * @apiVersion 0.0.1
 * @apiGroup Subject
 *
 * @apiSuccess {String} uuid Unique identifier for the new subject.
 * @apiSuccess {Object} surveys Object containing all the pre-intervention `Surveys` with `Questions`.
 * @apiSuccessExample {json} Success-Response:
 *     HTTP/1.1 200 OK
 *     {
 *       "uuid": "xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx",
 *       "surveys": [...]
 *     }
 *
 * @apiError RegistrationClosed The registration window for this trial has closed.
 * @apiErrorExample {json} Error-Response:
 *     HTTP/1.1 410 Gone
 *     {
 *       "message": "The registration window for this trial is closed."
 *     }
 *
 * @apiDescription Creates a new `Subject` in a given `Trial`, and returns a unique identifier.
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
 * @api {get} /trial/:tid/surveys Available Surveys
 * @apiName GetTrialSurveys
 * @apiVersion 0.0.1
 * @apiGroup Subject
 *
 * @apiParam {String} uuid Subject ID for whom to list available surveys.
 *
 * @apiSuccess {Object} surveys Object containing all the `Surveys` with `Questions` available to this `Subject`.
 * @apiSuccessExample {json} Success-Response:
 *     HTTP/1.1 200 OK
 *     {
 *       "tid": "xxxx",
 *       "sid": "#",
 *       "name": "...",
 *       "pre": "0",
 *       "post": "0",
 *       "during": "1",
 *       "interval": "7",
 *       "frequency": "days",
 *       "questions": [{
 *         "qid": "#",
 *         "text": "...",
 *         "type": "...",
 *         "options": "...|...|...",
 *       },...]
 *     }
 *
 * @apiDescription Returns a list of Surveys from a given Trial ID for a given Subject ID.
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
 * @api {post} /trial/:tid/survey/:sid Survey Answers
 * @apiName PostSurveyAnswers
 * @apiVersion 0.0.1
 * @apiGroup Subject
 *
 * @apiParam {String} uuid Subject ID for whom to list available surveys.
 * @apiParam {Object[]} answers An array of answers to be stored in the database.
 * @apiParamExample {Object} Request-Example:
 *     {
 *       "uuid": "xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx",
 *       "answers":[{
 *         "qid":..., "answer":...
 *       },...]
 *     }
 *
 * @apiDescription Stores the Answers to a given Trial and Survey.
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
