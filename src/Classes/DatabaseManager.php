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
				( UUID(), :salt, :hash, :email, :pass, :name, :role );"
			);
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

	public function newSubject( $tid ) {
		// return value
		$ret = new \stdClass();
		try {
			// look up trial
			$stmt = $this->dbh->prepare(
				"SELECT COUNT(*) AS `count`
				FROM `trials`
				WHERE `regopen` <= NOW()
				AND `regclose` > NOW()
				AND `tid` = :tid;"
			);
			$stmt->execute(array(
				'tid' => $tid
			));
			$found = $stmt->fetch(\PDO::FETCH_OBJ)->count > 0;
			// create new registration
			if( $found ) {
				$this->dbh->exec("SET @UID = UUID();");
				$stmt = $this->dbh->prepare(
					"INSERT INTO
					`subjects` ( id, tid )
					VALUES
					( @UID, :tid );"
				);
				$stmt->execute(array(
					'tid' => $tid
				));
				$stmt = $this->dbh->prepare(
					"SELECT @UID AS `uid`;"
				);
				$stmt->execute();
				$subject = $stmt->fetch(\PDO::FETCH_OBJ);
				// get pre-test surveys
				$stmt = $this->dbh->prepare(
					"SELECT
						`tid`, `sid`, `name`
					FROM
						`surveys`
					WHERE
						`tid` = :tid
					AND `pre` = 1;"
				);
				$stmt->execute(array(
					'tid' => $tid
				));
				$surveys = $stmt->fetchAll(\PDO::FETCH_OBJ);
				foreach( $surveys as $key => $survey ) {
					$stmt2 = $this->dbh->prepare(
						"SELECT
							`qid`, `text`, `type`, `options`
						FROM
							`questions`
						WHERE
							`tid` = :tid
						AND `sid` = :sid
						ORDER BY
							`qid`;"
					);
					$stmt2->execute(array(
						'tid' => $tid,
						'sid' => $survey->sid
					));
					$questions = $stmt2->fetchAll(\PDO::FETCH_OBJ);
					$survey->questions = $questions;
				}
				// output
				$ret->uuid = $subject->uid;
				$ret->surveys = $surveys;
				$ret->status = 200;
			} else {
				$ret->status = 204;
				$ret->message = "The registration window for this trial is closed.";
			}
		} catch( PDOException $e ) {
			$ret->status = $e->getCode();
			$ret->message = $e->getMessage();
		}
		// return
		return $ret;
	}

	/**
	 * Inserts a new trial into the `trials` table
	 * inserts new groups into the `groups` table
	 * inserts new surveys into the `surveys` table
	 * inserts new questions into the `questions` table
	 */
	public function newTrial( $obj ) {
		// return value
		$ret = new \stdClass();
		// attempt add
		try {
			$stmt = $this->dbh->prepare(
				"INSERT INTO
				`trials`
				(
					`uid`, `title`,
					`regopen`, `regclose`,
					`trialstart`, `trialend`,
					`trialtype`,`timezone`
				)
				VALUES
				(
					:uid, :title,
					:regopen, :regclose,
					:trialstart, :trialend,
					:trialtype, :timezone
				);"
			);
			$stmt->execute(array(
				'uid' => $obj->uid,
				'title' => $obj->title,
				'regopen' => $obj->regopen,
				'regclose' => $obj->regclose,
				'trialstart' => $obj->trialstart,
				'trialend' => $obj->trialend,
				'trialtype' => $obj->trialtype,
				'timezone' => $obj->timezone
			));
			// get trial id by selecting last inserted row
			$stmt = $this->dbh->prepare(
				"SELECT `tid`
				FROM `trials`
				WHERE `uid`=:uid
				ORDER BY `created` DESC
				LIMIT 1;"
			);
			$stmt->execute(array(
				'uid' => $obj->uid
			));
			$row = $stmt->fetch(\PDO::FETCH_OBJ);
			$ret->tid = $row->tid;
			// insert groups
			$ret->groups = 0;
			foreach( $obj->groups as $key => $group ) {
				$stmt = $this->dbh->prepare(
					"INSERT INTO
					`groups`
					( `tid`, `gid`, `name`, `size`, `size_n` )
					VALUES
					( :tid, :gid, :name, :size, :size_n )"
				);
				$stmt->execute(array(
					'tid' => $ret->tid,
					'gid' => $group->group_id,
					'name' => $group->group_name,
					'size' => $group->group_size,
					'size_n' => $group->group_size_n
				));
				$ret->groups++;
			}
			// insert surveys
			$ret->surveys = 0;
			$ret->questions = 0;
			foreach( $obj->surveys as $keyS => $survey ) {
				$stmt = $this->dbh->prepare(
					"INSERT INTO
					`surveys`
					( `tid`, `sid`, `name`, `groups`, `pre`, `during`, `post`, `interval`, `frequency` )
					VALUES
					( :tid, :sid, :name, :groups, :pre, :during, :post, :interval, :frequency )"
				);
				$stmt->execute(array(
					'tid' => $ret->tid,
					'sid' => $survey->survey_id,
					'name' => $survey->survey_name,
					'groups' => json_encode($survey->survey_groups),
					'pre' => $survey->survey_pre,
					'during' => $survey->survey_during,
					'post' => $survey->survey_post,
					'interval' => $survey->survey_interval,
					'frequency' => $survey->survey_frequency
				));
				$ret->surveys++;
				// insert questions
				foreach( $survey->survey_questions as $keyQ => $question ) {
					$stmt = $this->dbh->prepare(
						"INSERT INTO
						`questions`
						( `tid`, `sid`, `qid`, `text`, `type`, `options` )
						VALUES
						( :tid, :sid, :qid, :text, :type, :options )"
					);
					$stmt->execute(array(
						'tid' => $ret->tid,
						'sid' => $survey->survey_id,
						'qid' => $question->question_id,
						'text' => $question->question_text,
						'type' => $question->question_type,
						'options' => $question->question_options
					));
					$ret->questions++;
				}
			}
			// all done
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
	/*
	public function getUserByRequest( $request ) {
		$email = $request->getParam('username');
		$hash = $request->getParam('password');
		$refresh = $request->getParam('request_token');
		$rows = $this->getUserByLogin( $email, $hash );
		return $rows;
	}
	*/

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
		$stmt->execute(array(
			'tid' => $token
		));
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


	/**
	 * returns a list of trials for admin user
	 */
	public function getUserTrials( $uid ) {
		$stmt = $this->dbh->prepare(
			"SELECT
				`tid`, `title`,
				`regopen`, `regclose`,
				`trialstart`, `trialend`,
				`trialtype`, `timezone`,
				`created`, `updated`
			FROM
				`trials`
			WHERE
				`uid` = :uid
			ORDER BY `created` DESC"
		);
		$stmt->execute(array(
			'uid' => $uid
		));
		$rows = $stmt->fetchAll(\PDO::FETCH_OBJ);
		return $rows;
	}

	/**
	 * returns details for a trial
	 * as long as it matches the user's uid
	 */
	public function getTrialDetails( $uid, $tid ) {
		// trial
		$trial = new \stdClass();
		$stmt = $this->dbh->prepare(
			"SELECT
				*
			FROM
				`trials`
			WHERE
				`uid` = :uid
				AND
				`tid` = :tid
			ORDER BY `created` DESC;"
		);
		$stmt->execute(array(
			'uid' => $uid,
			'tid' => $tid
		));
		$trial = $stmt->fetch(\PDO::FETCH_OBJ);
		unset( $trial->uid );
		// groups
		$stmt = $this->dbh->prepare(
			"SELECT
				`gid`, `name`, `size`, `size_n`
			FROM
				`groups`
			WHERE
				`tid` = :tid
			ORDER BY `gid`;"
		);
		$stmt->execute(array(
			'tid' => $tid
		));
		$trial->groups = $stmt->fetchAll(\PDO::FETCH_OBJ);
		// surveys
		$stmt = $this->dbh->prepare(
			"SELECT
				`sid`, `name`, `groups`
			FROM
				`surveys`
			WHERE
				`tid` = :tid
			ORDER BY `sid`;"
		);
		$stmt->execute(array(
			'tid' => $tid
		));
		$surveys = $stmt->fetchAll(\PDO::FETCH_OBJ);
		$trial->surveys = array();
		foreach( $surveys as $key => $survey ) {
			// questions
			$stmt2 = $this->dbh->prepare(
				"SELECT
					`qid`, `text`, `type`, `options`
				FROM
					`questions`
				WHERE
					`tid` = :tid
					AND
					`sid` = :sid
				ORDER BY `qid`;"
			);
			$stmt2->execute(array(
				'tid' => $tid,
				'sid' => $survey->sid
			));
			$questions = $stmt2->fetchAll(\PDO::FETCH_OBJ);
			$survey->questions = $questions;
			array_push( $trial->surveys, $survey );
		}
		return $trial;
	}

	/**
	 *
	 */
	public function getSubjectSurveys( $uid, $tid ) {
		// get surveys
		$stmt = $this->dbh->prepare(
			"SELECT
				`s`.`tid`, `s`.`sid`, `s`.`name`,
				`s`.`pre`, `s`.`post`, `s`.`during`, `s`.`interval`, `s`.`frequency`
			FROM
				`subjects` AS `u`
			INNER JOIN
				`trials` AS `t`
				ON (`u`.`tid` = `t`.`tid`)
			INNER JOIN
				`groups` AS `g`
				ON (`u`.`group` = `g`.`gid` AND `u`.`tid` = `g`.`tid`)
			INNER JOIN
				`surveys` AS `s`
				ON (
					`u`.`tid` = `s`.`tid`
					AND
					FIND_IN_SET(`g`.`gid`, SUBSTRING( `s`.`groups`, 2, length(`s`.`groups`)-2 )) <> 0
					AND
					IF( NOW() < `t`.`trialstart`, `s`.`pre` = 1, `s`.`pre` = 0 )
					AND
					IF( NOW() > `t`.`trialend`, `s`.`post` = 1, `s`.`post` = 0 )
					AND
					IF( NOW() > `t`.`trialstart` AND NOW() < `t`.`trialend`, `s`.`during` = 1, `s`.`during` = 0 )
				)
			WHERE
				`u`.`id` = :uid
			AND `u`.`tid` = :tid;"
		);
		$stmt->execute(array(
			'uid' => $uid,
			'tid' => $tid
		));
		$surveys = $stmt->fetchAll(\PDO::FETCH_OBJ);
		// get questions
		foreach( $surveys as $key => $survey ) {
			$stmt = $this->dbh->prepare(
				"SELECT
					`qid`, `text`, `type`, `options`
				FROM
					`questions`
				WHERE
					`tid` = :tid
				AND `sid` = :sid;"
			);
			$stmt->execute(array(
				'tid' => $tid,
				'sid' => $survey->sid
			));
			$questions = $stmt->fetchAll(\PDO::FETCH_OBJ);
			$survey->questions = $questions;
		}
		// return
		return $surveys;
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
