<?php

// the following mocks a trial over 10 days
// expected output:
// 1 pre-test per subject
// 10 during-tests per subject
// 1 post-test per subject

DEFINE("urlRoot", "http://localhost/");
// DEFINE("tid", '6f7a');
DEFINE("tid", 'c414');
DEFINE("sampleSize", 20);

// connect to database for some direct manipulation
$host = 'mysql';
$port = '3306';
$dbname = 'mrct';
$user = 'root';
$pass = 'rooot';
$conn = "mysql:host=$host;dbname=$dbname;port=$port;charset=utf8mb4;";
$dbh = new \PDO( $conn, $user, $pass );

// debugging
$day = 0;

// create 100 subjects by hitting the endpoint
echo "CREATING NEW USERS...\n";
$users = array();
for( $i = 0; $i < sampleSize; $i++ ) {
	echo "next user\n";
	$out = postCurl( urlRoot.'register/'.tid, array() );
	// decode the response and
	// extract the user's uid and
	// answer the pre-intervention surveys
	$obj = json_decode($out);
	$uid = $obj->uuid;
	array_push( $users, $uid );
	echo "uuid: $uid\n";
	$surveys = $obj->surveys;
	echo "surveys:\n";
	foreach( $surveys as $survey ) {
		echo "...answering ".$survey->tid.":".$survey->sid."\n";
		answerSurvey( $survey, $uid );
	}
	echo "done surveys;\n";
	// sleep(1);
}

// bucket participants into trial groups
echo "BUCKET SUBJECTS INTO GROUPS!!\n";
$response = bucketSubjectsIntoGroups();
if( $response ) {
	echo "successfully bucketed\n";
}

// loop over `n` days checking for and answering surveys
$N = 65;
for( $i = 0; $i < $N; $i++ ) {
	echo "ADVANCE A DAY AND RUN SURVEYS (n times)\n";
	advanceOneDay();
	$day++;
	foreach( $users as $user ) {
		checkForSurveys( $user );
	}
	// sleep(1);
}
rollbackDays( $N );




/**
 * sends a curl request and returns the result
 * $url : string - where to send the post
 * $body : object with key=>value pairs
 */
function postCurl( $url, $body ) {
	// echo "......sending curl to ".$url." with:\n";
	// var_dump( $body );
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($body));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$server_output = curl_exec($ch);
	curl_close ($ch);
	return $server_output;
}
function getCurl( $url, $queryString ) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url."?".http_build_query($queryString));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$server_output = curl_exec($ch);
	curl_close ($ch);
	return $server_output;
}

/**
 * takes in a survey
 * and returns an array of answers
 */
function answerSurvey( $survey, $uuid ) {
	global $day;
	$answers = array();
	$tid = $survey->tid;
	$sid = $survey->sid;
	echo "...".$tid.":".$sid.":".$uuid."\n";
	foreach( $survey->questions as $question ) {
		if( $question->type == 'text' ) {
			// answer random lorem lipsum
			$ans = "d[$day] X";
		} else {
			$opts = explode( '|', $question->options );
			$rnd = random_int( 0, sizeof($opts)-1 );
			$ans = $opts[$rnd];
		}
		$answer = array(
			'qid' => $question->qid,
			'answer' => $ans
		);
		echo "...pushing answer for question ".$question->qid."\n";
		array_push( $answers, $answer );
	}
	// post to
	// /trial/$tid/survey/$sid
	$data = array(
		'uuid' => $uuid,
		'answers' => json_encode( $answers )
	);
	$response = postCurl( urlRoot."trial/$tid/survey/$sid", $data );
	var_dump( $response );
}
/**
 * checks for surveys for a given user and answers them
 */
function checkForSurveys( $uid ) {
	$url = urlRoot."trial/".tid."/surveys";
	$out = json_decode( getCurl( $url, array('uuid'=>$uid) ) );
	if( sizeof($out) > 0 ) {
		foreach( $out as $survey ) {
			echo "...answering ".$survey->tid.":".$survey->sid."\n";
			answerSurvey( $survey, $uid );
		}
	}
}

/**
 * connects to the database
 * then calls the procedure to bucket user into groups
 */
function bucketSubjectsIntoGroups() {
	global $dbh;
	$stmt = $dbh->prepare(
		"CALL bucket_subjects_into_groups(:tid);"
	);
	$out = $stmt->execute(array(
		'tid' => tid
	));
	return $out;
}

/**
 * decrements all dates for the trial
 * to create the appearance of it being one day later
 */
function advanceOneDay() {
	global $dbh;
	$stmt = $dbh->prepare(
		"UPDATE
			`trials`
		SET
			`regopen` = DATE_SUB( DATE_SUB( `regopen`, INTERVAL 1 DAY ), INTERVAL 5 SECOND ),
			`regclose` = DATE_SUB( DATE_SUB( `regclose`, INTERVAL 1 DAY ), INTERVAL 5 SECOND ),
			`trialstart` = DATE_SUB( DATE_SUB( `trialstart`, INTERVAL 1 DAY ), INTERVAL 5 SECOND ),
			`trialend` = DATE_SUB( DATE_SUB( `trialend`, INTERVAL 1 DAY ), INTERVAL 5 SECOND )
		WHERE
			`tid` = :tid;

		UPDATE
			`answers`
		SET
			`timestamp` = DATE_SUB( DATE_SUB( `timestamp`, INTERVAL 1 DAY ), INTERVAL 5 SECOND )
		WHERE
			`tid` = :tid;"
	);
	$out = $stmt->execute(array(
		'tid' => tid
	));
	return $out;
}
function rollbackDays( $N ) {
	global $dbh;
	$stmt = $dbh->prepare(
		"UPDATE
			`trials`
		SET
			`regopen` = DATE_ADD( `regopen`, INTERVAL :n DAY ),
			`regclose` = DATE_ADD( `regclose`, INTERVAL :n DAY ),
			`trialstart` = DATE_ADD( `trialstart`, INTERVAL :n DAY ),
			`trialend` = DATE_ADD( `trialend`, INTERVAL :n DAY )
		WHERE
			`tid` = :tid;"
	);
	$out = $stmt->execute(array(
		'tid' => tid,
		'n' => $N
	));
	return $out;
}
