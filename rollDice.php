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
    $playerColor = getPlayerColor($match, $playerID);
    
    $response = [
        'result' => "FAIL",
        'reason' => "An error has occured."
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
    elseif($match['roll']['rolled']) {
        $response['reason'] = "You can't roll the dice twice.";
    }
    else {
        $sides = [1, 2, 3, 4, 5, 6];
        shuffle($sides);
        $sides[4] = 4; // predefined value of rollResult
        $rollResult = $sides[4];


        $availableMoves = [];
        $test = [];

        $pawnsAtHome = getPawnsAtHome($match, $playerColor);
        if (count($pawnsAtHome) > 0 && ($rollResult == 1 || $rollResult == 6)) {
            foreach ($pawnsAtHome  as &$pawn) {
                array_push($availableMoves, [
                    'id' => $pawn["id"],
                    'x' => START_SPACES[$playerColor]['x'],
                    'y' => START_SPACES[$playerColor]['y'],
                ]);
            }
        }

        foreach ($match['pawns'] as &$pawn) {
            $indexOfPawn = getIndexOfPawnSpace($pawn['x'], $pawn['y'], $pawn['color']);

            if (!$pawn['isAtHome'] && $pawn['color'] == $playerColor) {
                $loopIndexOfPawn = $indexOfPawn;

                array_push($test, [
                    'x' => $pawn['x'],
                    'y' => $pawn['y'],
                    'index' => $loopIndexOfPawn,
                ]);
                
                $isMovePossible = true;
                $isAtEnd = $pawn['isAtEnd'];
                $endSpaces = null;

                if ($isAtEnd) {
                    $endSpaces = END_SPACES[$pawn['color']];
                }

                for ($i=0; $i < $rollResult; $i++) { 
                    if ($isAtEnd) {
                        if ($loopIndexOfPawn == count($endSpaces) - 2 && $i < $rollResult - 1) {
                            $isMovePossible = false;
                            break;
                        }
                        elseif ($loopIndexOfPawn+1 >= count($endSpaces) - getAmountFinishedPawns($match, $pawn['color'])) {
                            $isMovePossible = false;
                            break;
                        }
                        elseif ($i == $rollResult - 1 && getPawnAtSpace($match, $endSpaces[$loopIndexOfPawn+1]['x'], $endSpaces[$loopIndexOfPawn+1]['y']) != null) {
                            $isMovePossible = false;
                            break;
                        }
                        else {
                            $loopIndexOfPawn += 1;
                        }
                    }
                    else {
                        if (isset(SPACES[$loopIndexOfPawn]['color']) && SPACES[$loopIndexOfPawn]['color'] == $pawn['color']) {
                            $isAtEnd = true;
                            $endSpaces = SPACES[$loopIndexOfPawn]['endspaces'];
                            $loopIndexOfPawn = 0;

                            if ($i == $rollResult - 1 && getPawnAtSpace($match, $endSpaces[$loopIndexOfPawn]['x'], $endSpaces[$loopIndexOfPawn]['y']) != null) {
                                $isMovePossible = false;
                                break;
                            }
                        }
                        else {
                            if ($loopIndexOfPawn == count(SPACES) - 1) {
                                $loopIndexOfPawn = 0;
                            }
                            // elseif ($i == $rollResult - 1 && getPawnAtSpace($match, SPACES[$loopIndexOfPawn + 1]['x'], SPACES[$loopIndexOfPawn + 1]['y']) != null) {
                            //     $isMovePossible = false;
                            //     break;
                            // }
                            else {
                                $loopIndexOfPawn = $loopIndexOfPawn + 1;
                            }
                        }
                    }
                } 
                
                if ($isMovePossible) {
                    if ($isAtEnd) {
                        array_push($availableMoves, [
                            'id' => $pawn["id"],
                            'x' => $endSpaces[$loopIndexOfPawn]['x'],
                            'y' => $endSpaces[$loopIndexOfPawn]['y'],
                        ]);
                    }
                    else {
                        array_push($availableMoves, [
                            'id' => $pawn["id"],
                            'x' => SPACES[$loopIndexOfPawn]['x'],
                            'y' => SPACES[$loopIndexOfPawn]['y'],
                        ]);
                    }

                }
            }
        }


        $timestamp = time() + 60;
        $match["roll"]['availableMoves'] = $availableMoves;
        $match["roll"]['sides'] = $sides;
        $match["roll"]['rolled'] = true;

        $document->update([
            [
                'path' => 'roll', 
                'value' => $match["roll"]
            ]
        ]);



        $response = [
            'result' => "SUCCESS",
            'match' => $match,
            'test' => $test,
        ];
    }
    

    echo json_encode($response);
}

?>