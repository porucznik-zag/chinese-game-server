<?php 
include('dbconfig.php');
include('utils.php');
include('constants.php');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data["matchID"])) {
    $matchID = $data["matchID"];
    $playerID = $data["id"];
    
    $collection = $firestore->collection('matches');
    $document = $collection->document($matchID);
    $match = $document->snapshot()->data();

    $player = getPlayer($match, $playerID);
    
    $response = [
        'result' => "FAIL",
        'reason' => "An error has occured.",
    ];

    if ($match == null) {
        $response['reason'] = "There is no match with this ID.";
    }
    elseif ($match["state"] != "ACTIVE") {
        $response['reason'] = "Match isn't active.";
    }
    elseif ($player == null) {
        $response['reason'] = "Incorrect player ID.";
    }
    elseif ($playerID != $match["active"]) {
        $response['reason'] = "It isn't your turn.";
    }
    elseif($match['roll']['timestamp'] - time() <= 0) {
        $response['reason'] = "It isn't your turn.";
    }
    else {
        $activePlayerIndex = getPlayerIndex($match, $playerID);
        $nextPlayerIndex = getNextPlayerIndex($match, $activePlayerIndex);
        $nextPlayerID = $match['players'][$nextPlayerIndex]['id'];
        
        $match['active'] = $nextPlayerID;
        $match["roll"] = [
            'id' => $nextPlayerID,
            'availableMoves' => [],
            'sides' => [],
            'timestamp' => time() + 60,
            'rolled' => false,
        ];

        $document->update([
            [
                'path' => 'active', 
                'value' => $match["active"]
            ],
            [
                'path' => 'roll', 
                'value' => $match["roll"]
            ]
        ]);

        $response = [
            'result' => "SUCCESS",
            'match' => $match,
            'matchID' => $matchID,
            'playerID' => $playerID,
        ];
    }
    

    echo json_encode($response);
}

?>