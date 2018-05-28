<?php

namespace PS;

class DatabaseManager {

	public function __construct() {
		// TODO: read from config
		$host = 'mysql';
		$port = '3306';
		$dbname = 'mrct';
		$user = 'root';
		$pass = 'rooot';
		$conn = "mysql:host=$host;dbname=$dbname;port=$port;charset=utf8mb4;";
		// create object
		// $this->host = $host;
		// $this->port = $port;
		// $this->dbname = $dbname;
		// $this->user = $user;
		// $this->pass = $pass;
		// $this->manager = new \MongoDB\Driver\Manager( "mongodb://$user:$pass@$host:$port/$dbname" );

		$this->dbh = new \PDO( $conn, $user, $pass );
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
		if( sizeof($cursor) > 0 ) {
			$ret->status = 500;
			$ret->message = 'Email already exists';
			return $ret;
		}
		// attempt add
		try {
			$insert = $this->insertRecords(
				'users',
				"(`id`,`salt`,`hash`,`email`,`pass`,`name`,`role`)",
				"( UUID(), :salt, :hash, :email, :pass, :name, :role )",
				array(
					'salt'=>$obj->salt,
					'hash'=>$obj->hash,
					'email'=>$obj->email,
					'pass'=>$obj->pass,
					'name'=>$obj->name,
					'role'=>$obj->role
				)
			);
			$user = $this->getUserByEmail( $obj->email );
			$ret->id = $user[0]->id;
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
		$rows = $stmt->fetchAll(\PDO::FETCH_OBJ);
		return $rows;
	}

	/**
	 * Insert records
	 */
	public function insertRecords( $table, $inputs, $values, $obj ) {
		try {
			$stmt = $this->dbh->prepare("INSERT INTO $table $inputs VALUES $values");
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
