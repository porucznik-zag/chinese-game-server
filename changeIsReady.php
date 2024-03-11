<?php 
include('dbconfig.php');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data["matchID"])) {
    $matchID = $data["matchID"];
    $playerID = $data["playerID"];
    $isReady = $data["isReady"];

    $collection = $firestore->collection('matches');
    $document = $collection->document($matchID);
    $match = $document->snapshot()->data();

    foreach ($match["players"] as &$player) {
        if ($player["id"] == $playerID) {
            $player["isReady"] = $isReady;
            break;        
        }
    }

    if(count($match["players"]) >= 2) {
        $isAllPlayersReady = true;

        foreach ($match["players"] as &$player) {
            if (!$player["isReady"]) {
                $isAllPlayersReady = false;
                break;        
            }
        }

        if($isAllPlayersReady) {
            $activePlayerID = $match["players"][0]["id"];

            $match["state"] = "ACTIVE";
            $match["active"] = $activePlayerID;
            $match['roll']['id'] = $activePlayerID;
            $match['roll']['timestamp'] = time() + 60;
        }
    }

    $document->update([
        [
            'path' => 'players', 
            'value' => $match["players"]
        ],
        [
            'path' => 'state', 
            'value' => $match["state"]
        ],
        [
            'path' => 'active', 
            'value' => $match["active"]
        ],
        [
            'path' => 'roll', 
            'value' => $match["roll"]
        ]
    ]);
}

?>