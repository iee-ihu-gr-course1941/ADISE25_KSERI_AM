<?php
// lib/game.php
require_once "board.php";

// επιστροφή τρέχουσας κατάστασης παιχνιδιού (σειρά πάικτη, σκορ, ξερές)
function show_status() {
    global $mysqli;
    update_game_status();
    
    $sql = 'SELECT gs.*, 
            (SELECT score FROM players WHERE player="A") as score_A, 
            (SELECT score FROM players WHERE player="B") as score_B,
            (SELECT kseres FROM players WHERE player="A") as kseres_A,
            (SELECT kseres FROM players WHERE player="B") as kseres_B
            FROM game_status gs';
    $res = $mysqli->query($sql);
    header('Content-type: application/json');
    print json_encode($res->fetch_all(MYSQLI_ASSOC), JSON_PRETTY_PRINT);
}

function update_game_status() {
    global $mysqli;
    $status = read_status();
    
    // αν κάποιος παίκτης λείπει εδώ και 15 λεπτα -> abort
    $sql = 'SELECT count(*) as aborted FROM players WHERE last_action < (NOW() - INTERVAL 15 MINUTE) AND username IS NOT NULL';
    $res_ab = $mysqli->query($sql)->fetch_assoc();
    if($res_ab['aborted'] > 0) { 
        $mysqli->query("UPDATE game_status SET status='aborted'"); 
        return; 
    }

    // καταμέτρηση παικτών
    $sql = 'SELECT count(*) as c FROM players WHERE username IS NOT NULL';
    $res_active = $mysqli->query($sql)->fetch_assoc();
    $active_players = $res_active['c'];
    
    $new_status = $status['status'];
    $new_turn = $status['p_turn'];

    // αν 0 ενεργοί παίκτες 
    if($active_players == 0) { 
        $new_status = 'not active';
    // αν 1 ενεργός παίκτης
    } else if($active_players == 1) { 
        $new_status = 'initialized'; 
    // αν 2 ενεργοί παίκτες
    } else if ($active_players == 2 && $status['status'] !== 'started' && $status['status'] !== 'ended') {
        
        $new_status = 'started';
        $new_turn = 'A';
        reset_board(); // αρχικό μοίρασμα
    }

    if($new_status == 'started') {
        // έλεγχος για timeout παίκτη 
        if ($status['last_change'] < date('Y-m-d H:i:s', strtotime('-15 seconds'))) {
             $new_turn = ($status['p_turn'] == 'A') ? 'B' : 'A';
        }

        deal_cards(); // αν άδειασαν τα χέρια, δώσε νέα

        // έλεγχος τέλους παιχνιδιού
        $res_end = $mysqli->query("SELECT COUNT(*) as c FROM board WHERE pos IN ('deck', 'hand_A', 'hand_B')");
        if($res_end->fetch_assoc()['c'] == 0) {
            handle_end_game(); 
            return; // Η handle_end_game θα κάνει το τελικό update
        }
    }
    //update
    $sql = 'UPDATE game_status SET status=?, p_turn=?, last_change = NOW()';
    $st = $mysqli->prepare($sql);
    $st->bind_param('ss', $new_status, $new_turn);
    $st->execute();
}
// υπολογισμος τελικου σκορ
function handle_end_game() {
    global $mysqli;

    $sql = "SELECT b.pos, d.card_rank, d.card_suit 
            FROM board b 
            JOIN deck d ON b.card_id = d.card_id 
            WHERE b.pos IN ('pile_A', 'pile_B')";
    
    $res = $mysqli->query($sql);
    
    $points = ['A' => 0, 'B' => 0];
    $counts = ['A' => 0, 'B' => 0];

    while($row = $res->fetch_assoc()) {
        $p = ($row['pos'] == 'pile_A') ? 'A' : 'B';
        $counts[$p]++; // μετρημα για το μπονους πλειοψηφίας (+3)

        //1 πόντος για κάθε A, K, Q, J, 10
        $figures = ['A', 'K', 'Q', 'J', '10'];
        if (in_array($row['card_rank'], $figures)) {
            $points[$p] += 1;
        }

        //+1 πόντος για το 10 Καρό
        if ($row['card_rank'] == '10' && $row['card_suit'] == 'Diamond') {
            $points[$p] += 1;
        }

        // +1 πόντος για το 2 Σπαθί
        if ($row['card_rank'] == '2' && $row['card_suit'] == 'Club') {
            $points[$p] += 1;
        }
    }

    // + 3 πόντοι για την πλειοψηφία των φύλλων
    if ($counts['A'] > $counts['B']) {
        $points['A'] += 3;
    } elseif ($counts['B'] > $counts['A']) {
        $points['B'] += 3;
    }

    // ενημερωση σκορ παικτων
    foreach ($points as $player => $p_sum) {
        if ($p_sum > 0) {
            $mysqli->query("UPDATE players SET score = score + $p_sum WHERE player = '$player'");
        }
    }

    // καθορισμός νικητή
    $res_scores = $mysqli->query("SELECT player, score FROM players");
    $final_scores = [];
    while($s = $res_scores->fetch_assoc()) { $final_scores[$s['player']] = $s['score']; }
    
    $winner = 'D'; // ισσοπαλία
    if ($final_scores['A'] > $final_scores['B']) $winner = 'A';
    elseif ($final_scores['B'] > $final_scores['A']) $winner = 'B';
    
    $mysqli->query("UPDATE game_status SET status='ended', result='$winner'");
}


// αναγνωση πινακα game_status
function read_status() {
    global $mysqli;
    return $mysqli->query("select * from game_status")->fetch_assoc();
}

// ολικο reset , ωστε να ξεκινησει καινουριο παιχνιδι
function reset_game_all() {
    global $mysqli;
    
    $mysqli->query("DELETE FROM players");
   
    $mysqli->query("UPDATE game_status SET status='not active', p_turn=NULL, result=NULL");
    
   
    $mysqli->query("UPDATE board SET pos='deck', weight=NULL");

    header('Content-Type: application/json');
    echo json_encode(['message' => 'Game and players reset successfully']);

}
