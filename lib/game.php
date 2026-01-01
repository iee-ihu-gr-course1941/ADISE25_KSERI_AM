<?php

// εμφανίζει την τρέχουσα κατάσταση του παιχνιδιού σε μορφή JSON
function show_status() {
    global $mysqli;
    update_game_status();
    $sql = 'select * from game_status';
    $st = $mysqli->prepare($sql);
    $st->execute();
    $res = $st->get_result();
    header('Content-type: application/json');
    print json_encode($res->fetch_all(MYSQLI_ASSOC), JSON_PRETTY_PRINT);
}
// επαναφέρει την κατάσταση του πίνακα 
function update_game_status() {
    global $mysqli;
    // ανάγνωση τρέχουσας κατάστασης
    $status = read_status();
    
    // έλεγχος για παίκτες που έχουν αποχωρήσει εδώ και 15 λεπτά
    $sql = 'select count(*) as aborted from players WHERE last_action < (NOW() - INTERVAL 15 MINUTE) AND username IS NOT NULL';
    $st3 = $mysqli->prepare($sql);
    $st3->execute();
    $res3 = $st3->get_result();
    $aborted = $res3->fetch_assoc()['aborted']; 
    // αν υπάρχουν τέτοιοι παίκτες, καθαρισμός παιχνιδιού
    if($aborted > 0) {
         $mysqli->query("CALL clean_game()");
    }

    // ελεγχος ενεργών παικτών
    $sql = 'select count(*) as c from players where username is not null';
    $st = $mysqli->prepare($sql);
    $st->execute();
    $res = $st->get_result();
    $active_players = $res->fetch_assoc()['c'];
    
    // ενημέρωση κατάστασης παιχνιδιού ανάλογα με τον αριθμό ενεργών παικτών
    $new_status = $status['status'];
    $new_turn = $status['p_turn'];

    // αν δεν υπάρχουν ενεργοί παίκτες
    if($active_players == 0) { 
        $new_status = 'not active'; 
    }
    // αν υπάρχει μόνο ένας ενεργός παίκτης
    else if($active_players == 1) { 
        $new_status = 'initialized'; 
    }
    // αν υπάρχουν δύο ενεργοί παίκτες και το παιχνίδι δεν έχει ήδη ξεκινήσει
    else if($active_players == 2 && ($status['status'] == 'not active' || $status['status'] == 'initialized')) {
        $new_status = 'started';
        $new_turn = 'A';
        reset_board();
    }

    // ενημέρωση της κατάστασης στη βαση
    $sql = 'update game_status set status=?, p_turn=?';
    $st = $mysqli->prepare($sql);
    $st->bind_param('ss', $new_status, $new_turn);
    $st->execute();
}

// διαβάζει την τρέχουσα κατάσταση του παιχνιδιού
function read_status() {
    global $mysqli;
    $sql = 'select * from game_status';
    $st = $mysqli->prepare($sql);
    $st->execute();
    $res = $st->get_result();
    return $res->fetch_assoc();
}


function reset_game_all() {
    global $mysqli;
    $mysqli->query("CALL clean_game()");
    header('Content-Type: application/json');
    print json_encode(['message' => 'Game reset successfully']);
}
?>