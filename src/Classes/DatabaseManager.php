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
	 * Inserts a new player into the `users` table
	 * makes sure that the email doesn't already exist
	 */
	public function newPlayer( $obj ) {
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
			$insert = $this->insertRecords(
				'users',
				"(`id`,`salt`,`hash`,`email`,`pass`,`name`,`role`)",
				"( UUID(), :salt, :hash, :email, :pass, :name, :role )",
				array(
					'salt' => $obj->salt,
					'hash' => $obj->hash,
					'email' => $obj->email,
					'pass' => $obj->pass,
					'name' => $obj->name,
					'role' => $obj->role
				)
			);
			// get id
			// TODO: this is possibly unnecessary, isn't used by the FE yet
			$user = $this->getUserByEmail( $obj->email );
			$ret->id = $user->id;
		} catch( PDOException $e ) {
			$ret->status = $e->getCode();
			$ret->message = $e->getMessage();
		}
		// return
		return $ret;
	}

	/**
	 * Check `users` table by email passed
	 */
	public function getUserByEmail( $email ) {
		$stmt = $this->dbh->prepare("SELECT * FROM `users` WHERE `email`=:email");
		$stmt->bindParam( ':email', $email );
		$stmt->execute();
		$rows = $stmt->fetch(\PDO::FETCH_OBJ);
		if( $rows !== false ) {
			return $rows;
		} else {
			return false;
		}
	}

	/**
	 * Check `users` table by email and hash
	 */
	public function getUserByLogin( $email, $hash ) {
		$stmt = $this->dbh->prepare(
			"SELECT * FROM `users`
			WHERE `email`=:email AND `hash`=:hash");
		$stmt->bindParam( ':email', $email );
		$stmt->bindParam( ':hash', $hash );
		$stmt->execute();
		$rows = $stmt->fetch(\PDO::FETCH_OBJ);
		if( $rows !== false ) {
			return $rows;
		} else {
			return false;
		}
	}

	/**
	 * Check `users` table by `tid` from Authorization
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
		if( $rows !== false ) {
			return $rows;
		} else {
			return false;
		}
	}

	/**
	 *
	 */
	public function saveToken( $obj ) {
		$insert = $this->insertRecords(
			'tokens',
			"(`uid`,`tid`,`token`,`expires`)",
			"( :uid, :tid, :token, :expires )",
			array(
				'uid' => $obj->uid,
				'tid' => $obj->tid,
				'token' => $obj->access_token, // TODO: don't save this - use `tid` instead
				'expires' => $obj->date_expires
			),
			"ON DUPLICATE KEY UPDATE
				`tid`=:tid,
				`token`=:token,
				`expires`=:expires"
		);
		// return $obj->access_token;
	}


	/**
	 * Insert records
	 */
	public function insertRecords( $table, $inputs, $values, $obj, $update = '' ) {
		try {
			$stmt = $this->dbh->prepare("INSERT INTO $table $inputs VALUES $values $update");
			$stmt->execute( $obj );
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

?>
