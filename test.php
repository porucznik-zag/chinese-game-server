<?php 
include('dbconfig.php');

$collectionPath = 'matches';

$documents = $firestore->collection($collectionPath)->documents();

foreach ($documents as $document) {
    $data = $document->data();

    print_r($data);
}
?>