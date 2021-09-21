<?php

namespace RCTrials;


class DatabaseManager {
	protected static $instance = null;

	protected function __construct() {
	}

	protected function __clone() {
	}

	public static function getInstance() {
		if (!isset(static::$instance)) {
			try {
				$options = [
					\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
				];
				$dbConnInfo = file_get_contents( __DIR__ . "/../../../conn/dbconn.json");
				$obj = json_decode($dbConnInfo, true);
				$host = $obj['host'];
				$port = $obj['port'];
				$dbname = $obj['dbname'];
				$user = $obj['user'];
				$pass = $obj['pass'];
				$conn = "mysql:host=$host;dbname=$dbname;port=$port;charset=utf8mb4;";
				// instantiate
				static::$instance = new DatabaseManager();
				self::$instance->dbh = new \PDO( $conn, $user, $pass, $options );
				// self::$instance->dbh->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
			} catch ( \Exception $err ) {
				return null;
			}
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
			$ret->status = 409;
			$ret->error = 'Email '.$obj->email.' already exists.';
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
				(`id`,`salt`,`hash`,`email`,`name`,`role`)
				VALUES
				( UUID(), :salt, :hash, :email, :name, :role );"
			);
			$stmt->execute(array(
				'salt' => $obj->salt,
				'hash' => $obj->hash,
				'email' => $obj->email,
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
			$ret->error = $e->getMessage();
		}
		// return
		return $ret;
	}

	public function newSubject( $tid, $opt_research, $firebase_token ) {
		// return value
		$ret = new \stdClass();
		try {
			// look up trial
			$stmt = $this->dbh->prepare(
				"SELECT COUNT(*) AS `count`
				FROM `trials`
				WHERE `regopen` <= NOW()
				AND (
					`regclose` > NOW()
					OR
					`regclose` = 0
				)
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
					`subjects` ( id, tid, research_opt, f6e_token )
					VALUES
					( @UID, :tid, :opt, :token );"
				);
				$stmt->execute(array(
					'tid' => $tid,
					'opt' => $opt_research,
					'token' => $firebase_token
				));
				$stmt = $this->dbh->prepare(
					"SELECT `s`.`id` AS `uid`, `s`.`group`
						 FROM `subjects` AS `s`
						WHERE `s`.`id` = @UID;"
				);
				$stmt->execute();
				$subject = $stmt->fetch(\PDO::FETCH_OBJ);
				// get pre-test surveys
				$stmt = $this->dbh->prepare(
					"SELECT
						`tid`, `sid`, `name`, `time`, `intro`
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
				// process
				foreach ( $surveys as $survey ) {
					$survey->sid = intval($survey->sid);
					foreach ($survey->questions as $question) {
						$question->qid = intval($question->qid);
					}
				}
				// output
				$ret->uuid = $subject->uid;
				$ret->pbl = boolval( $subject->group );
				$ret->surveys = $surveys;
				$ret->status = 200;
			} else {
				$ret->status = 410;
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
					`trialtype`,`timezone`,
					`created`
				)
				VALUES
				(
					:uid, :title,
					:regopen, :regclose,
					:trialstart, :trialend,
					:trialtype, :timezone,
					NOW()
				);"
			);
			$regclosedate = substr($obj->regclose, 0, 23);
			if( $regclosedate == '' ) $regclosedate = '3000-01-01';
			$trialend = substr($obj->regclose, 0, 23);
			if( $trialend == '' ) $trialend = '3000-01-01';
			$stmt->execute(array(
				'uid' => $obj->uid,
				'title' => $obj->title,
				'regopen' => substr($obj->regopen, 0, 23),
				'regclose' => $regclosedate,
				'trialstart' => substr($obj->trialstart, 0, 23),
				'trialend' => $trialend,
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
			// insert features
			$ret->features = 0;
			foreach( $obj->features as $key => $feature ) {
				$stmt = $this->dbh->prepare(
					"INSERT INTO
					`features`
					( `tid`, `fid`, `name` )
					VALUES
					( :tid, :fid, :name )"
				);
				$stmt->execute(array(
					'tid' => $ret->tid,
					'fid' => $feature->feature_id,
					'name' => $feature->feature_name
				));
				$ret->features++;
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
					'pre' => ($survey->survey_pre ? 1 : 0 ),
					'during' => ($survey->survey_during ? 1 : 0 ),
					'post' => ($survey->survey_post ? 1 : 0 ),
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
	 * Check `users` table by email and salt+pass=hash
	 * internal function
	 */
	public function getUserByLogin( $email, $pass ) {
		$retVal = false;
		// retrieve matching record
		$stmt = $this->dbh->prepare(
			"SELECT * FROM `users`
			WHERE `email`=:email");
		$stmt->execute(array(
			'email' => $email
		));
		$user = $stmt->fetch(\PDO::FETCH_OBJ);
		if( !$user ) {
			return $retVal;
		}
		// test match
		if( password_verify( $user->salt.$pass, $user->hash ) ) {
			$retVal = $user;
		}
		// return user or false
		return $retVal;
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
				`t`.`tid`, `t`.`title`,
				`t`.`regopen`, `t`.`regclose`,
				`t`.`trialstart`, `t`.`trialend`,
				`t`.`trialtype`, `t`.`timezone`,
				`t`.`created`, `t`.`updated`,
				`s`.`subjects`,
				`a`.`answers`
			FROM
				(
					SELECT
						`tid`, `title`,
						`regopen`, `regclose`,
						`trialstart`, `trialend`,
						`trialtype`, `timezone`,
						`created`, `updated`
					FROM
						`trials`
					WHERE
						`uid` = :uid
					ORDER BY `created` DESC
				) AS `t`
				LEFT JOIN
				(
					SELECT
						`tid`,
						count(id) AS `subjects`
					FROM
						`subjects`
					GROUP BY
						`tid`
				) AS `s`
				ON
					(`t`.`tid` = `s`.`tid`)
				LEFT JOIN
				(
					SELECT
						`tid`,
						COUNT(*) AS ANSWERS
					FROM
						`answers`
					GROUP BY
						`tid`
				) AS `a`
				ON
					(`t`.`tid` = `a`.`tid`)
			"
		);
		$stmt->execute(array(
			'uid' => $uid
		));
		$trials = $stmt->fetchAll(\PDO::FETCH_OBJ);
		// return
		return $trials;
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
		if( !$trial || !$trial->tid ) { return []; } // !! terminate if no trials match this user id !!
		unset( $trial->uid );
		// groups
		$stmt = $this->dbh->prepare(
			"SELECT
				`g`.`gid`, `g`.`name`, `g`.`size`, `g`.`size_n`,
				COUNT(`s`.`id`) AS `subjects`
			FROM
				`groups` AS `g`
			LEFT JOIN
				`subjects` AS `s`
				ON (`s`.`tid`=`g`.`tid` AND `s`.`group`=`g`.`gid`)
			WHERE
				`g`.`tid` = :tid
			GROUP BY
				`g`.`gid`
			ORDER BY
				`g`.`gid`;"
		);
		$stmt->execute(array(
			'tid' => $tid
		));
		$trial->groups = $stmt->fetchAll(\PDO::FETCH_OBJ);
		// surveys
		$stmt = $this->dbh->prepare(
			"SELECT
				`sid`, `name`, `groups`,
				`pre`, `during`, `post`,
				`interval`, `frequency`
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
		foreach( $surveys as $survey ) {
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
			// enrich questions with answer summaries
			foreach( $questions as $question ) {
				// determine if this is a one-off or time-series data:
				$stmt2 = $this->dbh->prepare(
					// "SELECT AVG(c) AS `avg`
					// FROM (
					// 	SELECT `uid`, COUNT(*) AS `c`
					// 	FROM `answers`
					// 	WHERE `tid` = :tid AND qid = :qid
					// 	GROUP BY `uid`
					// ) AS x;"
					"SELECT AVG(c) AS `avg`
					FROM (
						SELECT `uid`, COUNT(*) AS `c`
						FROM (
							SELECT `uid`,`timestamp`,COUNT(*) AS `c`
							FROM `answers`
							WHERE `tid` = :tid AND `qid` = :qid
							GROUP BY `timestamp`
						) AS `x`
						GROUP BY `uid`
					) AS x;"
				);
				$stmt2->execute(array(
					'tid' => $tid,
					'qid' => $question->qid
				));
				$question->avgresponse = round(floatval( $stmt2->fetch(\PDO::FETCH_OBJ)->avg ));

				// accumulate totals per question
				if( $question->avgresponse <= 1 ) {
					$stmt2 = $this->dbh->prepare(
						"SELECT
							`text`, COUNT(*) AS `count`
						FROM
							`answers`
						WHERE
							`tid` = :tid
							AND
							`qid` = :qid
						GROUP BY
							`qid`, `text`
						ORDER BY `text`;"
					);
					$stmt2->execute(array(
						'tid' => $tid,
						'qid' => $question->qid
					));
					$question->totals = $stmt2->fetchAll(\PDO::FETCH_OBJ);
				}
				// accumulate time-series data
				else {
					$stmt2 = $this->dbh->prepare(
						"SELECT
							`uid`, `text`, `timestamp`
						FROM
							`answers`
						WHERE
							`tid` = :tid
							AND
							`qid` = :qid
						ORDER BY
							`uid`, `timestamp`;"
					);
					$stmt2->execute(array(
						'tid' => $tid,
						'qid' => $question->qid
					));
					$question->answers = $stmt2->fetchAll(\PDO::FETCH_OBJ);
				}

			}
			// push questions+answers
			$survey->questions = $questions;
			array_push( $trial->surveys, $survey );
			// question answers:
			// select qid, text, count(*) as `count` from answers where tid='5858' group by `qid`, `text` order by `qid`;
		}
		// subjects // TODO: this info is present under `groups` - determine if this block is necessary
		$stmt = $this->dbh->prepare(
			"SELECT
				`s`.`group`, COUNT(`s`.`id`) AS `subjects`
			FROM
				`subjects` AS `s`
			WHERE
				`s`.`tid` = :tid
			GROUP BY
				`s`.`tid`,`s`.`group`;"
		);
		$stmt->execute(array(
			'tid' => $tid
		));
		$trial->subjects = $stmt->fetchAll(\PDO::FETCH_OBJ);
		// answers
		$stmt = $this->dbh->prepare(
			"SELECT
				`sid`, `qid`,
				COUNT(*) AS `answers`
			FROM
				`answers`
			WHERE
				`tid` = :tid
			GROUP BY
				`tid`, `sid`, `qid`;"
		);
		$stmt->execute(array(
			'tid' => $tid
		));
		$trial->answers = $stmt->fetchAll(\PDO::FETCH_OBJ);
		// return enriched object
		return $trial;
	}

	/**
	 * returns count of trials matching the tid
	 */
	public function validateTrial( $tid ) {
		$found = new \stdClass();
		$stmt = $this->dbh->prepare(
			"SELECT
				COUNT(`tid`) AS `found`
			FROM
				`trials`
			WHERE
				`tid` = :tid
			ORDER BY `created` DESC;"
		);
		$stmt->execute(array(
			'tid' => $tid
		));
		$found = $stmt->fetch(\PDO::FETCH_OBJ);
		// return count matching tid
		return $found;
	}

	/**
	 * Returns all the available surveys
	 * for a given trial for the requesting user
	 * depending on their group assignment
	 */
	public function getSubjectSurveys( $uid, $tid ) {
		// get surveys
		$stmt = $this->dbh->prepare(
			"SELECT * FROM (
				SELECT
					`s`.`tid`, `s`.`sid`, `s`.`name`, `s`.`time`, `s`.`intro`,
					`s`.`pre`, `s`.`post`, `s`.`during`, `s`.`interval`, `s`.`frequency`,
					COUNT(`a`.`uid`) AS `answers`,
					DATEDIFF( `t`.`trialend`, `t`.`trialstart` ) AS `dur_trial`,
					DATEDIFF( NOW(), `u`.`created` ) AS `dur_registration`
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
						IF( ( DATEDIFF( NOW(), `u`.`created` ) > DATEDIFF( `t`.`trialend`, `t`.`trialstart` ) ), `s`.`post` = 1, `s`.`post` = 0 )
						AND
						IF( NOW() > `t`.`trialstart` AND (NOW() < `t`.`trialend` OR `t`.`trialend` = 0 OR ( DATEDIFF( NOW(), `u`.`created` ) <= DATEDIFF( `t`.`trialend`, `t`.`trialstart` ) )), `s`.`during` = 1, 1 )
					)
				LEFT JOIN
					`answers` AS `a`
					ON (
						`u`.`tid` = `a`.`tid`
						AND
						`u`.`id` = `a`.`uid`
						AND
						`s`.`sid` = `a`.`sid`
						AND
							IF( `s`.`during` = 1,
								`a`.`timestamp`
								BETWEEN
								DATE_SUB( NOW(), INTERVAL `s`.`interval` DAY )
								AND
								NOW()
								,
								true
							)
					)
				WHERE
					`u`.`id` = :uid
				AND `u`.`tid` = :tid
				GROUP BY
					`s`.`tid`, `s`.`sid`, `a`.`uid`
			) AS `x`
			WHERE
				`x`.`answers` < 1;"
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
		foreach ($surveys as $survey) {
			$survey->sid = intval($survey->sid);
			$survey->pre = intval($survey->pre);
			$survey->post = intval($survey->post);
			$survey->during = intval($survey->during);
			$survey->interval = intval($survey->interval);
			$survey->answers = intval($survey->answers);
			$survey->intro = $survey->intro;
			foreach ($survey->questions as $question) {
				$question->qid = intval($question->qid);
			}
		}
		$ret = new \stdClass();
		$ret->surveys = $surveys;
		return $ret;
	}

	/**
	 *
	 */
	public function saveSurveyAnswers( $uid, $tid, $sid, $answers ) {
		$retval = new \stdClass();
		try {
			// $retval->answers = array();
			// $retval->params = array();
			foreach( $answers as $key => $answerAry ) {
				$answer = (object) $answerAry;
				$ary = [ $answer->answer ]; // start with one item in array
				if( strpos($answer->answer,"|") > -1 ) {
					// fill with pipe-delimited answers if applicable
					$ary = explode("|", $answer->answer);
				}
				$stmt = $this->dbh->prepare(
					"INSERT INTO
					`answers`
					(`tid`,`sid`,`qid`,`uid`,`text`)
					VALUES
					( :tid, :sid, :qid, :uid, :answer );"
				);
				foreach( $ary as $key => $text ) {
					$res = $stmt->execute(array(
						'tid' => $tid,
						'sid' => $sid,
						'qid' => $answer->qid,
						'uid' => $uid,
						'answer' => $text
					));
					/*
					array_push( $retval->answers, $res );
					array_push( $retval->params, array(
						"tid" => $tid,
						"sid" => $sid,
						"uid" => $uid,
						"qid" => $answer->qid,
						"answer" => $text,
					));
					*/
				}
			}
			$retval->status = true;
			return $retval;
		} catch( PDOException $e ) {
			$retval->status = false;
			return $retval;
		}
	}

	/**
	 *
	 */
	public function setSubjectNotifiationPreference( $uid, $opt ) {
		$output = new \stdClass();
		$output->setting = new \stdClass();
		if( strlen($uid) == 36 ) {
			if( $opt == null || is_numeric($opt) == false ) {
				$opt = 0;
			} else if ( $opt < 0 ) {
				$opt = 0;
			} else if ( $opt > 0 ) {
				$opt = 1;
			}
			try {
				$stmt = $this->dbh->prepare(
					"UPDATE `subjects`
					SET `f6e_opt` = :opt
					WHERE `id` = :uid;"
				);
				$res = $stmt->execute(array(
					'uid' => $uid,
					'opt' => $opt,
				));
				$output->status = 200;
				$output->setting->updated = 1;
				$output->setting->opt = $opt;
			} catch( PDOException $e ) {
				$output->status = $e->getCode();
				$output->error = $e->getMessage();
			}
		} else {
			// bad input
			$output->status = 500;
			$output->error = "Bad input.";
		}
		return $output;
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
