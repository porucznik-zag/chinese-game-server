<?php 
include('dbconfig.php');
include('utils.php');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data["matchID"])) {
    $matchID = $data["matchID"];

    $collection = $firestore->collection('matches');
    $document = $collection->document($matchID);
    $match = $document->snapshot()->data();

    if($match['roll']['timestamp'] != null && $match['roll']['timestamp'] - time() <= 0) {
        $activePlayerIndex = getPlayerIndex($match, $match['active']);
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
            ],
        ]);
    }

    echo json_encode([
        'match' => $match
    ]);
}

?>