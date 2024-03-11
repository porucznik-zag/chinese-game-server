<?php 


function getPlayer($match, $playerID) {
    if($match == null) {
        return null;
    }
    foreach ($match["players"] as &$player) {
        if ($player["id"] == $playerID) {
            return $player;
        }
    }
    return null;
}

function getPlayerIndex($match, $playerID) {
    if($match == null) {
        return null;
    }
    for ($i=0; $i < count($match["players"]); $i++) { 
        if ($match["players"][$i]["id"] == $playerID) {
            return $i;
        }
    }
    return null;
}

function getPlayerColor($match, $playerID) {
    if($match == null) {
        return null;
    }
    foreach ($match["players"] as &$player) {
        if ($player["id"] == $playerID) {
            return $player["color"];
        }
    }
    return null;
}

function getPawnsAtHome($match, $color) {
    $pawns = [];
    if($match == null) {
        return $pawns;
    }
    foreach ($match["pawns"] as &$pawn) {
        if ($pawn["color"] == $color && $pawn["isAtHome"]) {
            array_push($pawns, $pawn);
        }
    }
    return $pawns;
}

function getAvailableMoveForPawn($match, $pawnID) {
    if($match == null) {
        return null;
    }
    foreach ($match["roll"]["availableMoves"] as &$move) {
        if ($move["id"] == $pawnID) {
            return $move;
        }
    }
    return null;
}

function &getPawn(&$match, $pawnID) {
    $pawn = null;
    if($match == null) {
        return $pawn;
    }
    for ($i=0; $i < count($match["pawns"]); $i++) { 
        if ($match["pawns"][$i]["id"] == $pawnID) {
            $pawn =& $match["pawns"][$i];
            break;
        }
    }
    return $pawn;
}

function getPawnIndex($match, $pawnID) {
    if($match == null) {
        return null;
    }
    for ($i=0; $i < count($match["pawns"]); $i++) { 
        if ($match["pawns"][$i]["id"] == $pawnID) {
            return $i;
        }
    }
    return null;
}

function checkIfIsEndSpace($x, $y) {
    foreach (ALL_END_SPACES as $space) {
        if($space['x'] == $x && $space['y'] == $y) {
            return true;
        }
    }
    return false;
}

function getPawnsToCapture($match, $x, $y, $color) {
    $pawns = [];

    if ($match == null) {
        return $pawns;
    }

    foreach ($match["pawns"] as $pawn) {
        if ($pawn["color"] != $color && $pawn["x"] == $x && $pawn["y"] == $y) {
            array_push($pawns, $pawn);
        }
    }

    return $pawns;
}

function getFreeSpacesAtHome($match, $color) {
    if ($match == null) {
        return [];
    }
    
    $freeSpacesAtHome = HOME_SPACES[$color];
    $pawnsAtHome = getPawnsAtHome($match, $color);

    foreach ($pawnsAtHome as $pawn) {
        $x = $pawn['x'];
        $y = $pawn['y'];
        for ($i=0; $i < count($freeSpacesAtHome); $i++) { 
            if ($freeSpacesAtHome[$i]['x'] == $x && $freeSpacesAtHome[$i]['y'] == $y) {
                array_splice($freeSpacesAtHome, $i, 1);
            }
        }
    }

    return $freeSpacesAtHome;
}

function movePawn(&$match, $pawnID, $x, $y) {
    if ($match == null) {
        return false;
    }

    $pawnIndex = getPawnIndex($match, $pawnID);
    $pawn = $match["pawns"][$pawnIndex];
    array_splice($match["pawns"], $pawnIndex, 1);
    
    $pawn['x'] = $x;
    $pawn['y'] = $y;
    $pawn['isAtHome'] = false;
    $pawn['isAtEnd'] = checkIfIsEndSpace($x, $y);

    array_push($match["pawns"], $pawn);

    $pawnsToCapture = getPawnsToCapture($match, $pawn['x'], $pawn['y'], $pawn['color']);

    foreach ($pawnsToCapture as $pawnToCapture) {
        $freeSpacesAtHome = getFreeSpacesAtHome($match, $pawnToCapture['color']);
        $randomSpaceAtHomeIndex = array_rand($freeSpacesAtHome);
        $randomSpaceAtHome = $freeSpacesAtHome[$randomSpaceAtHomeIndex];

        $pawnToCaptureIndex = getPawnIndex($match, $pawnToCapture['id']);

        $match['pawns'][$pawnToCaptureIndex]['x'] = $randomSpaceAtHome['x'];
        $match['pawns'][$pawnToCaptureIndex]['y'] = $randomSpaceAtHome['y'];
        $match['pawns'][$pawnToCaptureIndex]['isAtHome'] = true;
    }

    return true;
}

function getNextPlayerIndex($match, $activePlayerIndex) {
    $nextPlayerIndex = $activePlayerIndex;

    for ($i=0; $i < count($match['players']); $i++) { 
        if ($nextPlayerIndex == count($match['players']) - 1) {
            $nextPlayerIndex = 0;
        }
        else {
            $nextPlayerIndex += 1;
        }

        if(count($match['winners']) == 0) {
            break;
        }

        foreach ($match["winners"] as $winner) {
            if($winner != $match['players'][$nextPlayerIndex]['id']) {
                break 2;
            }
        }
    }

    return $nextPlayerIndex;
}

function getIndexOfPawnSpace($x, $y, $color) {
    for ($i=0; $i < count(SPACES); $i++) { 
        $space = SPACES[$i];
        if ($space['x'] == $x && $space['y'] == $y) {
            return $i;
        }
    }

    for ($i=0; $i < count(END_SPACES[$color]); $i++) { 
        $endSpace = END_SPACES[$color][$i];
        if ($endSpace['x'] == $x && $endSpace['y'] == $y) {
            return $i;
        }
    }

    return null;
}

function getPawnsAtEnd($match, $color) {
    $pawns = [];

    if($match == null) {
        return $pawns;
    }

    foreach ($match["pawns"] as &$pawn) {
        if ($pawn["color"] == $color && $pawn["isAtEnd"]) {
            array_push($pawns, $pawn);
        }
    }

    return $pawns;
}

function getAmountFinishedPawns($match, $color) {
    $amount = 0;

    if($match == null) {
        return $amount;
    }
    
    $pawnsAtEnd = getPawnsAtEnd($match, $color);

    for ($i = count(END_SPACES[$color]) - 1; $i >= 0; $i--) { 
        $endSpace = END_SPACES[$color][$i];
        $isFinished = false;

        foreach ($pawnsAtEnd as $pawn) {
            if($pawn['x'] == $endSpace['x'] && $pawn['y'] == $endSpace['y']) {
                $isFinished = true;
                break;
            }
        }

        if ($isFinished) {
            $amount += 1;
        }
        else {
            break;
        }
    }

    return $amount;
}

function &getPawnAtSpace(&$match, $x, $y) {
    $pawn = null;

    if($match == null) {
        return $pawn;
    }

    for ($i=0; $i < count($match["pawns"]); $i++) { 
        if ($match["pawns"][$i]["x"] == $x && $match["pawns"][$i]["y"] == $y) {
            $pawn =& $match["pawns"][$i];
            break;
        }
    }

    return $pawn;
}


?>