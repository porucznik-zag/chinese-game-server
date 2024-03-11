<?php 
include('dbconfig.php');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data["matchID"])) {
    $collection = $firestore->collection('matches');
    $matchID = $data["matchID"];
    $match = $collection->document($matchID)->snapshot()->data();
    
    $response = [
        'result' => "FAIL"
    ];

    if ($match != null && $match["state"] != "FINISHED") {
        $response["result"] = "SUCCESS";
        $response["match"] = $match;
    }


    echo json_encode($response);
}

?>