<?php

require './vendor/autoload.php';

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Exception\Messaging\NotFound;


// output
echo "<pre>";

// firebase authentication
$factory = (new Factory)->withServiceAccount( __DIR__.'/../../conn/mehailo21-firebase-adminsdk-wkujx-0d1f21edf1.json' );
$messaging = $factory->createMessaging();

// connect to database
try {
  $options = [
    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
  ];
  $dbConnInfo = file_get_contents( __DIR__ . "/../../conn/RCTdbconn.json");
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

// get some stats
//// subjects
$sql = "SELECT COUNT(*) AS `count` FROM `subjects`;";
$stmt = $dbh->prepare( $sql );
$stmt->execute();
$row = $stmt->fetch(\PDO::FETCH_OBJ);
$count_subj = $row->count;
//// answers
$sql = "SELECT COUNT(*) AS `count` FROM `answers`;";
$stmt = $dbh->prepare( $sql );
$stmt->execute();
$row = $stmt->fetch(\PDO::FETCH_OBJ);
$count_ans = $row->count;

// now for mehailo

try {
  $options = [
    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
  ];
  $dbConnInfo = file_get_contents( __DIR__ . "/../../conn/MHLdbconn.json");
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

$sql = "SELECT COUNT(*) AS `count` FROM `users`;";
$stmt = $dbh->prepare( $sql );
$stmt->execute();
$row = $stmt->fetch(\PDO::FETCH_OBJ);
$count_users = $row->count;

$sql = "SELECT COUNT(*) AS `count` FROM `activities` AS `a` LEFT JOIN `users` AS `u` ON (`u`.`id`=`a`.`uid`) WHERE DATE(ADDTIME(`timestamp`,`u`.`offset`)) = DATE(ADDTIME(NOW(),'-04:00:00'));";
$stmt = $dbh->prepare( $sql );
$stmt->execute();
$row = $stmt->fetch(\PDO::FETCH_OBJ);
$count_activities = $row->count;

$sql = "SELECT COUNT(*) AS `count` FROM `achievements` WHERE `date` = DATE(ADDTIME(NOW(),'-4:00:00')) AND `complete` = 100;";
$stmt = $dbh->prepare( $sql );
$stmt->execute();
$row = $stmt->fetch(\PDO::FETCH_OBJ);
$count_completed_achievements = $row->count;

$sql = "SELECT COUNT(*) AS `count` FROM `achievements` WHERE `date` = DATE(ADDTIME(NOW(),'-4:00:00'));";
$stmt = $dbh->prepare( $sql );
$stmt->execute();
$row = $stmt->fetch(\PDO::FETCH_OBJ);
$count_achievements = $row->count;

// // // SENDING NOTIFICATION

// select all firebase tokens
// that have progress surveys
// where answers are later than the allowed interval
$deviceToken = 'e3oQ3yY6TZqyMcWztOghau:APA91bG13cRbGB9HATyaLMlKfYIQKYYckajQW0sQ0_nyt5-eradk6MT3oZarNMXELBNVlpPxOVrR0PSMXsxb2T7d-BQ-W92eO6HSr0ZZmtxKXyGSz4I0bwApAhE5E4owPH9GzG077yXP';
$msgTitle = 'Health Check';
$msgBody = '';

$ch = curl_init();
curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
  'Content-Type: application/json',
  'Accept: application/json'
));
// RCTrials
curl_setopt( $ch, CURLOPT_URL, "https://rctrials.interfacemaster.ca/api/rct" );
$response = curl_exec( $ch );
$json = json_decode( $response );
$msgBody .= $json->hello . " v" . $json->version . " [" . $json->status . "]" ;
$msgBody .= " " . $count_subj . " usr | " . $count_ans . " ans";
$msgBody .= " ~ ";
// MEHAILO
curl_setopt( $ch, CURLOPT_URL, "https://mehailo.interfacemaster.ca/api/mhl" );
$response = curl_exec( $ch );
$json = json_decode( $response );
$msgBody .= $json->hello . " v" . $json->version . " [" . $json->status . "]" ;
$msgBody .= " " . $count_users . " usr | " . $count_activities . " act | " . $count_completed_achievements . "/" . $count_achievements . " ach";
// done
curl_close($ch);

$notification = Notification::create( $msgTitle, $msgBody );
$data = ['activity' => 'SURVEYS'];

$message = CloudMessage::withTarget( 'token', $deviceToken )
   ->withNotification( $notification ) // optional
   ->withData( $data ) // optional
;

try {
  $reply = $messaging->send( $message );
  echo "OK:" . $reply['name'];
} catch ( Kreait\Firebase\Exception\Messaging\NotFound $e ) {
  echo "Requested entity was not found.\n$deviceToken";
} catch ( Exception $e ) {
  echo "ERROR: <br/>\n";
  echo $e->getMessage();
  var_dump( $e );
}


echo "\nFinished\n";

echo "</pre>";
