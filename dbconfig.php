<?php 

require 'vendor/autoload.php';

use Google\Cloud\Firestore\FirestoreClient;

$serviceAccountFilePath = __DIR__ . '/chinesegame-73423-firebase-adminsdk-72dax-fb60a82d98.json';

$firestore = new FirestoreClient([
    'keyFilePath' => $serviceAccountFilePath,
]);


?>