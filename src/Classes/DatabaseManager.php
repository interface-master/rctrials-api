<?php

namespace MRCT;

class DatabaseManager {
	protected static $instance = null;

	protected function __construct() {
	}

	protected function __clone() {
	}

	public static function getInstance() {
		if (!isset(static::$instance)) {
			// TODO: read from config
			$host = 'mysql';
			$port = '3306';
			$dbname = 'mrct';
			$user = 'root';
			$pass = 'rooot';
			$conn = "mysql:host=$host;dbname=$dbname;port=$port;charset=utf8mb4;";
			// instantiate
			static::$instance = new DatabaseManager();
			self::$instance->dbh = new \PDO( $conn, $user, $pass );
		}
		return static::$instance;
	}

	/**
	 * Inserts a new admin into the `users` table
	 * makes sure that the email doesn't already exist
	 */
	public function newAdmin( $obj ) {
		// return value
		$ret = new \stdClass();
		// check if email exists
		$cursor = $this->getUserByEmail( $obj->email );
		if( $cursor !== false ) {
			$ret->status = 500;
			$ret->message = 'Email '.$obj->email.' already exists';
			return $ret;
		}
		// attempt add
		try {
			// set @id=UUID();
			// insert into <table>(<col1>,<col2>) values (@id,'another value');
			// select @id;
			$stmt = $this->dbh->prepare(
				"INSERT INTO
				`users`
				(`id`,`salt`,`hash`,`email`,`pass`,`name`,`role`)
				VALUES
				( UUID(), :salt, :hash, :email, :pass, :name, :role );");
			$stmt->execute(array(
				'salt' => $obj->salt,
				'hash' => $obj->hash,
				'email' => $obj->email,
				'pass' => $obj->pass,
				'name' => $obj->name,
				'role' => $obj->role
			));

			// get id
			// TODO: this is possibly unnecessary, isn't used by the FE yet
			$user = $this->getUserByEmail( $obj->email );
			$ret->id = $user->id;
			$ret->status = 200;
		} catch( PDOException $e ) {
			$ret->status = $e->getCode();
			$ret->message = $e->getMessage();
		}
		// return
		return $ret;
	}

	/**
	 * Check `users` table by email passed
	 * internal function
	 */
	public function getUserByEmail( $email ) {
		$stmt = $this->dbh->prepare(
			"SELECT *
			FROM `users`
			WHERE `email`=:email;");
		$stmt->execute(array(
			'email' => $email
		));
		$rows = $stmt->fetch(\PDO::FETCH_OBJ);
		return $rows;
	}

	/**
	 * Check login by extracting necessary info from request
	 * internal function
	 * NOT USED !?
	 */
	public function getUserByRequest( $request ) {
		$email = $request->getParam('username');
		$hash = $request->getParam('password');
		$refresh = $request->getParam('request_token');
		$rows = $this->getUserByLogin( $email, $hash );
		return $rows;
	}

	/**
	 * Check `users` table by email and hash
	 * internal function
	 */
	public function getUserByLogin( $email, $hash ) {
		$stmt = $this->dbh->prepare(
			"SELECT * FROM `users`
			WHERE `email`=:email AND `hash`=:hash");
		$stmt->execute(array(
			'email' => $email,
			'hash' => $hash
		));
		$rows = $stmt->fetch(\PDO::FETCH_OBJ);
		return $rows;
	}

	/**
	 * Check `users` table by `tid` from Authorization
	 * internal function
	 */
	public function getUserByAuth( $token ) {
		$stmt = $this->dbh->prepare(
			"SELECT
				u.id AS uid,
				u.email,
				u.name,
				u.role
			FROM
				users AS u
			INNER JOIN
				tokens AS t
				ON (t.uid = u.id)
			WHERE
				t.tid=:tid");
		$stmt->bindParam( ':tid', $token );
		$stmt->execute();
		$rows = $stmt->fetch(\PDO::FETCH_OBJ);
		return $rows;
	}

	/**
	 *
	 */
	public function saveToken( $obj ) {
		try {
			$stmt = $this->dbh->prepare(
				"INSERT INTO
				`tokens`
				(`uid`,`tid`,`token`,`expires`)
				VALUES
				( :uid, :tid, :token, :expires )
				ON DUPLICATE KEY UPDATE
					`tid`=:tid,
					`token`=:token,
					`expires`=:expires
				");
			$stmt->execute(array(
				'uid' => $obj->uid,
				'tid' => $obj->tid,
				'token' => $obj->access_token, // TODO: don't save this - use `tid` instead
				'expires' => $obj->date_expires
			));
			return true;
		} catch( PDOException $e ) {
			return false;
		}
	}




	// public function getCursor() {
	// 	$stmt = $this->dbh->prepare(
	// 		"SELECT * FROM users WHERE true LIMIT 3"
	// 	);
	// 	$stmt->execute();
	// 	$rows = $stmt->fetchAll(\PDO::FETCH_OBJ);
	// 	return $rows;
	// }

}
