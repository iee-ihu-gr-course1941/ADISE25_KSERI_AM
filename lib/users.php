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
    if(!isset($input['username']) || $input['username'] == '') {
        header("HTTP/1.1 400 Bad Request");
        print json_encode(['errormesg' => "No username given."]);
        exit;
    }
    $username = $input['username'];
    global $mysqli;

    $sql = 'select count(*) as c 
            from players 
            where player=? 
            and username is not null';
    $st = $mysqli->prepare($sql);
    $st->bind_param('s', $p);
    $st->execute();
    $res = $st->get_result();
    $r = $res->fetch_all(MYSQLI_ASSOC);
    
    if($r[0]['c'] > 0) {
        header("HTTP/1.1 400 Bad Request");
        print json_encode(['errormesg' => "Player $p is already set. Please select another player."]);
        exit;
    }

    // Κρατάμε το last_action=NOW() για να μην σε πετάει το timeout της βάσης σου
    $sql = 'update players 
            set username=?, token=md5(CONCAT(?, NOW())), last_action=NOW()
            where player=?';
    $st2 = $mysqli->prepare($sql);
    $st2->bind_param('sss', $username, $username, $p);
    $st2->execute();

    update_game_status();

    $sql = 'select * from players where player=?';
    $st = $mysqli->prepare($sql);
    $st->bind_param('s', $p);
    $st->execute();
    $res = $st->get_result();
    header('Content-type: application/json');
    print json_encode($res->fetch_all(MYSQLI_ASSOC), JSON_PRETTY_PRINT);
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
?>