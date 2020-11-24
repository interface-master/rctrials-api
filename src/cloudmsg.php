<?php

require './vendor/autoload.php';

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;


$factory = (new Factory)->withServiceAccount( __DIR__.'/../../conn/mehailo-20200620-7ce45a69fcdd.json' );
$messaging = $factory->createMessaging();

$deviceToken = 'dpVIu_ZU9Ek:APA91bFQDoXIBsDhuGiMmb08xIyov1y32_JYGC8ky-qMo-Zk6DFKSBuCtI8XNFN54KyeUsTOQo2Z0CYwV76CerkR4t98dIMSNNI5vu2NsAvH7ClbIDPEbihEbov5fRhWu6wJ99zWrAek';
$notification = Notification::create( 'Title', 'Body' );
$data = ['key' => 'value'];

$message = CloudMessage::withTarget( 'token', $deviceToken )
    ->withNotification( $notification ) // optional
    ->withData( $data ) // optional
;

var_dump( $message );

$messaging->send( $message );

echo "\n\nDone.";
