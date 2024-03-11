<?php 
namespace ChineseGameServer;

include 'constants.php';
include('dbconfig.php');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data["id"])) {
    $collection = $firestore->collection('matches');
    $matchID = null;
    $match = null;

    foreach ($collection->documents() as $doc) {
        $docData = $doc->data();
        if ($docData["state"] == "WAITING_FOR_PLAYERS") {
            $matchID = $doc->id();
            break;        
        }
    }


    if ($matchID == null) {
        $availableColors = ['BLUE', 'GREEN', 'RED', 'YELLOW'];

        $randomIndex = array_rand($availableColors);
        $randomColor = $availableColors[$randomIndex];
        array_splice($availableColors, $randomIndex, 1);    

        $match = [
            'state' => 'WAITING_FOR_PLAYERS',
            'active' => null,
            'availableColors' => $availableColors,
            'players' => [
                [
                    'id' => $data["id"],
                    'name' => $data["name"],
                    'color' => $randomColor,
                    'isReady' => false,
                ]
            ],
            'pawns' => [
                [
                    'id' => $randomColor . "1",
                    'x' => HOME_SPACES[$randomColor][0]['x'],
                    'y' => HOME_SPACES[$randomColor][0]['y'],
                    'color' => $randomColor,
                    'isAtHome' => true,
                    'isAtEnd' => false,
                ],
                [
                    'id' => $randomColor . "2",
                    'x' => HOME_SPACES[$randomColor][1]['x'],
                    'y' => HOME_SPACES[$randomColor][1]['y'],
                    'color' => $randomColor,
                    'isAtHome' => true,
                    'isAtEnd' => false,
                ],
                [
                    'id' => $randomColor . "3",
                    'x' => HOME_SPACES[$randomColor][2]['x'],
                    'y' => HOME_SPACES[$randomColor][2]['y'],
                    'color' => $randomColor,
                    'isAtHome' => true,
                    'isAtEnd' => false,
                ],
                [
                    'id' => $randomColor . "4",
                    'x' => HOME_SPACES[$randomColor][3]['x'],
                    'y' => HOME_SPACES[$randomColor][3]['y'],
                    'color' => $randomColor,
                    'isAtHome' => true,
                    'isAtEnd' => false,
                ],
            ],
            'roll' => [
                'availableMoves' => [],
                'id' => null,
                'sides' => [],
                'timestamp' => null,
                'rolled' => false,
            ],
            'winners' => []
        ];
        
        $document = $collection->add($match);
        $matchID = $document->id();
    }
    else {
        $document = $collection->document($matchID);
        $match = $document->snapshot()->data();
        $matchID = $document->id();
        
        $availableColors = &$match["availableColors"];
        $randomIndex = array_rand($availableColors);
        $randomColor = $availableColors[$randomIndex];
        array_splice($availableColors, $randomIndex, 1);
        
        
        $newPlayer = [
            'id' => $data["id"],
            'name' => $data["name"],
            'color' => $randomColor,
            'isReady' => false,
        ];
        array_push($match["players"], $newPlayer);

        $newPawns = [
            [
                'id' => $randomColor . "1",
                'x' => HOME_SPACES[$randomColor][0]['x'],
                'y' => HOME_SPACES[$randomColor][0]['y'],
                'color' => $randomColor,
                'isAtHome' => true,
                'isAtEnd' => false,
            ],
            [
                'id' => $randomColor . "2",
                'x' => HOME_SPACES[$randomColor][1]['x'],
                'y' => HOME_SPACES[$randomColor][1]['y'],
                'color' => $randomColor,
                'isAtHome' => true,
                'isAtEnd' => false,
            ],
            [
                'id' => $randomColor . "3",
                'x' => HOME_SPACES[$randomColor][2]['x'],
                'y' => HOME_SPACES[$randomColor][2]['y'],
                'color' => $randomColor,
                'isAtHome' => true,
                'isAtEnd' => false,
            ],
            [
                'id' => $randomColor . "4",
                'x' => HOME_SPACES[$randomColor][3]['x'],
                'y' => HOME_SPACES[$randomColor][3]['y'],
                'color' => $randomColor,
                'isAtHome' => true,
                'isAtEnd' => false,
            ],
        ];
        foreach ($newPawns as $pawn) {
            array_push($match["pawns"], $pawn);
        }

        if(count($match["players"]) == 4) {
            $activePlayerID = $match["players"][0]["id"];

            $match["state"] = "ACTIVE";
            $match["active"] = $activePlayerID;
            $match['roll']['id'] = $activePlayerID;
            $match['roll']['timestamp'] = time() + 60;

            foreach ($match["players"] as &$player) {
                $player["isReady"] = true;
            }
        }


        $document->update([
            [
                'path' => 'availableColors', 
                'value' => $match["availableColors"]
            ],
            [
                'path' => 'players', 
                'value' => $match["players"]
            ],
            [
                'path' => 'pawns', 
                'value' => $match["pawns"]
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
    
    echo json_encode([
        'matchID' => $matchID,
        'match' => $match
    ]);


}


?>