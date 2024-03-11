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
    $pawnID = $data["pawnID"];
    
    $collection = $firestore->collection('matches');
    $document = $collection->document($matchID);
    $match = $document->snapshot()->data();

    $player = getPlayer($match, $playerID);
    $playerColor = getPlayerColor($match, $playerID);
    $pawnIndex = getPawnIndex($match, $pawnID);
    // $pawn =& getPawn($match, $pawnID);
    $move = getAvailableMoveForPawn($match, $pawnID);
    
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
    elseif ($pawnIndex === null) {
        $response['reason'] = "Incorrect pawn ID.";
    }
    // elseif ($pawn == null) {
    //     $response['reason'] = "Incorrect pawn ID.";
    // }
    elseif ($playerID != $match["active"]) {
        $response['reason'] = "It isn't your turn.";
    }
    elseif ($move == null) {
        $response['reason'] = "Move for this pawn isn't available.";
    }
    elseif($match['roll']['timestamp'] - time() <= 0) {
        $response['reason'] = "It isn't your turn.";
    }
    else {
        $moveResult = movePawn($match, $pawnID, $move['x'], $move['y']);

        if ($moveResult) {

            if (getAmountFinishedPawns($match, $playerColor) == 4) {
                array_push($match['winners'], $playerID);
                if (count($match['winners']) == count($match['players']) - 1) {
                    foreach ($match['players'] as $p) {
                        $contains = false;

                        foreach ($match["winners"] as $winner) {
                            if($winner == $p['id']) {
                                $contains = true;
                                break;
                            }
                        }

                        if (!$contains) {
                            array_push($match['winners'], $p['id']);
                            break;
                        }
                    }
                    
                    $match['state'] = "FINISHED";
                }
            }

            if ($match['state'] == "ACTIVE") {
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
            }
            else {
                $match['active'] = null;
                $match["roll"] = [
                    'id' => null,
                    'availableMoves' => [],
                    'sides' => [],
                    'timestamp' => null,
                    'rolled' => false,
                ];
            }
            
    
            $document->update([
                [
                    'path' => 'active', 
                    'value' => $match["active"]
                ],
                [
                    'path' => 'roll', 
                    'value' => $match["roll"]
                ],
                [
                    'path' => 'pawns', 
                    'value' => $match["pawns"]
                ],
                [
                    'path' => 'winners', 
                    'value' => $match["winners"]
                ],
                [
                    'path' => 'state', 
                    'value' => $match["state"]
                ]
            ]);
    
            $response = [
                'result' => "SUCCESS",
                'match' => $match,
            ];
        }
    }
    

    echo json_encode($response);
}

?>