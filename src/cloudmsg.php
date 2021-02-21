<?php

require './vendor/autoload.php';

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Exception\Messaging\NotFound;


// output
echo "<pre>";
$linecounter = 0;
echo "000 : yyyy-mm-dd hh:mm:ss : xxxxxxxx-uuuu-iiii-dddd-xxxxxxxxxxxx : g :  tid : s : last_answer\n";
echo "---------------------------------------------------------------------------------------------\n";
echo "\n";
$linecounter++;

// firebase authentication
$factory = (new Factory)->withServiceAccount( __DIR__.'/../../conn/mehailo-20200620-7ce45a69fcdd.json' );
$messaging = $factory->createMessaging();

// connect to database
try {
  $options = [
    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
  ];
  $dbConnInfo = file_get_contents( __DIR__ . "/../../conn/dbconn.json");
  $obj = json_decode($dbConnInfo, true);
  $host = $obj['host'];
  $port = $obj['port'];
  $dbname = $obj['dbname'];
  $user = $obj['user'];
  $pass = $obj['pass'];
  $conn = "mysql:host=$host;dbname=$dbname;port=$port;charset=utf8mb4;";
  // instantiate
  $dbh = new \PDO( $conn, $user, $pass, $options );
} catch ( \Exception $err ) {
  return null;
}

// select all firebase tokens
// that have progress surveys
// where answers are later than the allowed interval
$sql = "SELECT `s`.`id` AS `subject`, `s`.`tid`, `s`.`group`, `f6e_token`,
               `v`.`sid`, `v`.`name` AS `survey_name`,
               `v`.`groups` AS `survey_groups`,
               `v`.`interval` AS `survey_interval`,
               `v`.`frequency` AS `survey_frequency`,
               `ans`.`answers_date`
          FROM `subjects` `s`
    INNER JOIN `surveys` `v`
               ON (`s`.`tid` = `v`.`tid`
                   AND `v`.`during` = 1
                   AND FIND_IN_SET(`s`.`group`,
                     SUBSTRING(`v`.`groups`, 2, LENGTH(`v`.`groups`)-2)
                   ) <> 0
               )
     LEFT JOIN (    SELECT `uid`, `tid`, `sid`, MAX(DATE(`timestamp`)) AS `answers_date`
                      FROM `answers`
                  GROUP BY `uid`, `tid`, `sid`
                ) AS `ans`
                ON (`s`.`id`=`ans`.`uid`
                    AND `s`.`tid`=`ans`.`tid`
                    AND `v`.`sid`=`ans`.`sid`
                )
          WHERE `f6e_token` IS NOT NULL
            AND DATEDIFF( NOW(), `ans`.`answers_date` ) > `v`.`interval`;";

$stmt = $dbh->prepare( $sql );
$stmt->execute();
$tokens = $stmt->fetchAll(\PDO::FETCH_OBJ);
foreach( $tokens as $key => $token ) {
  $log = new \stdClass();
  $log->timestamp = date('Y-m-d H:i:s', time());
  $log->uid = $token->subject;
  $log->group = $token->group;
  $log->tid = $token->tid;
  $log->sid = $token->sid;
  $log->last_answer = $token->answers_date;

  echo logformat($linecounter) . " : " . $log->timestamp . " : " . $log->uid . " : " . $log->group . " : " . $log->tid . " : " . $log->sid . " : " . $log->last_answer . "\n";

  $deviceToken = 'eg1wBZmeRkWeWYxHL7folx:APA91bEcNfH1ErH9fWfkO9kQWtbNjsMONx8BBSVrVrpAWpi7RuTAcP37hVokT74w8e4Se34EpvhNtOACz1g2E_70TYsAWb-2oHy5W6Zg_LSs31wylBMDrcePTz0fCTYBl0ZbxDfHMV-l'; //$token->f6e_token;
  $msgTitle = 'Mehailo Survey';
  $msgBody = 'Please take 30 seconds (really, we timed it) to fill out a survey and help our research!';
  $notification = Notification::create( $msgTitle, $msgBody );
  $data = ['activity' => 'SURVEYS'];

  $message = CloudMessage::withTarget( 'token', $deviceToken )
     ->withNotification( $notification ) // optional
     ->withData( $data ) // optional
  ;

  try {
    $reply = $messaging->send( $message );
    $log->response = "OK:" . $reply['name'];
  } catch ( Kreait\Firebase\Exception\Messaging\NotFound $e ) {
    $log->response = "Requested entity was not found.";
    // remove token from db
    $update_token_sql = "UPDATE `subjects` SET `f6e_token` = NULL WHERE `id` = :uid";
    $stmt = $dbh->prepare( $update_token_sql );
    $stmt->execute(array(
      'uid' => $log->uid
    ));
    $log->response = $log->response . " Removed.";
  } catch ( Exception $e ) {
    echo "ERROR: <br/>\n";
    $log->response = $e->getMessage();
    var_dump( $e );
  }

  echo logformat($linecounter) . " : " . $log->response . "\n";

  try {
    $sql = "INSERT INTO `f6e_logs` VALUES (
      :time, :uid, :group, :tid, :sid, :last_answer, :response
    )";
    $stmt = $dbh->prepare( $sql );
    $stmt->execute(array(
      'time' => $log->timestamp,
      'uid' => $log->uid,
      'group' => $log->group,
      'tid' => $log->tid,
      'sid' => $log->sid,
      'last_answer' => $log->last_answer,
      'response' => $log->response
    ));
  } catch ( Error $e ) {
    echo "Error while logging: " . $e->getMessage();
  }

  echo "\n";
  $linecounter++;

}

echo "\nFinished\n";

echo "</pre>";


function logformat( $n ) {
  $retval = "";
  if( $n < 100 ) $retval .= "0";
  if( $n < 10 ) $retval .= "0";
  return $retval . $n;
}
