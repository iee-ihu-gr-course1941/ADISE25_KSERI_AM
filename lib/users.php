
<?php

require_once("lib/dbconnect.php");


function handle_user($method, $p, $input) {
    // επιστροφή στοιχείων παίκτη
    if($method == 'GET') {
        show_user($p); 
    // εγγραφή ή ενημέρωση στοιχείων παίκτη
    } else if($method == 'POST') { 
        set_user($p, $input);
    }
}
// εμφάνιση στοιχείων ενός συγκεκριμένου παίκτη (π.χ 'Α')
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

// εμφάνιση στοιχείων όλων των παικτών
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
    // δόθηκε username?
    if(!isset($input['username']) || $input['username'] == '') {
        header("HTTP/1.1 400 Bad Request");
        print json_encode(['errormesg' => "No username given."]);
        exit;
    }
    $username = $input['username'];
    global $mysqli;

    // έλεγχος αν υπάρχει ήδη ενεργός παίκτης στη θέση Α ή Β
    $sql = 'select count(*) as c
            from players
            where player=?
            and username is not null';
    $st = $mysqli->prepare($sql);
    $st->bind_param('s', $p);
    $st->execute();
    $res = $st->get_result();
    $r = $res->fetch_all(MYSQLI_ASSOC);

    // σφάλμα αν υπάρχει
    if($r[0]['c'] > 0) {
        header("HTTP/1.1 400 Bad Request");
        print json_encode(['errormesg' => "Player $p is already set. Please select another player."]);
        exit;
    }

    // αν δεν υπάρχει ήδη εγγεγραμένος χρήστης στη θέση Α ή Β,
    // τοτε ορίζω το username, δημιουργώ το τοκεν με τον μδ5
    $sql = '
        insert into players (player, username, token, last_action)
        values (?, ?, md5(concat(?, now())), now())
    ';

    $st = $mysqli->prepare($sql);
    $st->bind_param('sss', $p, $username, $username);
    $st->execute();

    // ενημερωση καταστασης παιχνιδιου
    update_game_status();

    // επιστροφη νεων στοιχειων παικτη σε json
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
    if($token == null) {
         return(null);
    }
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