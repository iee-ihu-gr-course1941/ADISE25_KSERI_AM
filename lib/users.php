<?php

require_once "lib/game.php";

function handle_user($method, $p, $input) {
    if($method == 'GET') {
        show_user($p);
    } else if($method == 'PUT') { 
        set_user($p, $input);
    }
}

function show_user($p) {
    global $mysqli;
    $sql = 'select username, player from players where player=?';
    $st = $mysqli->prepare($sql);
    $st->bind_param('s', $p);
    $st->execute();
    $res = $st->get_result();
    header('Content-type: application/json');
    print json_encode($res->fetch_all(MYSQLI_ASSOC), JSON_PRETTY_PRINT);
}

function show_users() {
    global $mysqli;
    $sql = 'select username, player from players';
    $st = $mysqli->prepare($sql);
    $st->execute();
    $res = $st->get_result();
    header('Content-type: application/json');
    print json_encode($res->fetch_all(MYSQLI_ASSOC), JSON_PRETTY_PRINT);
}

function set_user($p, $input) {
    global $mysqli;
    
    if(!isset($input['username']) || $input['username'] == '') {
        header("HTTP/1.1 400 Bad Request");
        echo json_encode(['errormesg' => "No username given."]);
        exit;
    }
    
    $username = $mysqli->real_escape_string($input['username']);
    $player_id = $mysqli->real_escape_string($p);

    // 1. Έλεγχος αν η θέση είναι κενή (με απλό query για αποφυγή του mysql.proc error)
    $res = $mysqli->query("SELECT COUNT(*) as c FROM players WHERE player='$player_id' AND username IS NOT NULL");
    $r = $res->fetch_assoc();
    
    if($r['c'] > 0) {
        header("HTTP/1.1 400 Bad Request");
        echo json_encode(['errormesg' => "Player $p is already set."]);
        exit;
    }

    // 2. Δημιουργία ΝΕΟΥ Token (Χρήση mysqli->query αντί για prepare)
    // Αυτό παρακάμπτει το σφάλμα "Column count of mysql.proc is wrong"
    $token = md5($username . microtime() . rand());
    $sql = "UPDATE players 
            SET username='$username', token='$token', last_action=NOW() 
            WHERE player='$player_id'";
    
    if(!$mysqli->query($sql)) {
        header("HTTP/1.1 500 Internal Server Error");
        echo json_encode(['errormesg' => "Database Error: " . $mysqli->error]);
        exit;
    }

    // 3. Ενημέρωση status
    update_game_status();

    // 4. Επιστροφή των νέων στοιχείων
    $res = $mysqli->query("SELECT * FROM players WHERE player='$player_id'");
    $data = $res->fetch_all(MYSQLI_ASSOC);
    
    header('Content-type: application/json');
    echo json_encode($data); 
    exit;
}

// επιστρέφει τον παίκτη στον οποίο ανήκει το token
function current_player($token) {
    global $mysqli;
    if($token == null) { return(null); }
    $sql = 'select * from players where token=?';
    $st = $mysqli->prepare($sql);
    $st->bind_param('s', $token);
    $st->execute();
    $res = $st->get_result();
    if($row = $res->fetch_assoc()) {
        return($row['player']);
    }
    return(null);
}
