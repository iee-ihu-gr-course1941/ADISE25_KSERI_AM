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
    // διαβάζω τρέχουσα κατάσταση 
    $status = read_status();
    
    // αν ενας παικτης δεν ειναι ενεργος για 15 λεπτα -> abord game
    $sql = 'SELECT count(*) as aborted FROM players WHERE last_action < (NOW() - INTERVAL 15 MINUTE) AND username IS NOT NULL';
    $res_ab = $mysqli->query($sql)->fetch_assoc();
    if($res_ab['aborted'] > 0) { 
        $mysqli->query("CALL clean_game()"); 
        return; 
    }

   // μέτρηση ενεργών παικτών στο τραπεζι
    $sql = 'SELECT count(*) as c FROM players WHERE username IS NOT NULL';
    $res_active = $mysqli->query($sql)->fetch_assoc();
    $active_players = $res_active['c'];
    
    $new_status = $status['status'];
    $new_turn = $status['p_turn'];

    //διαχειριση καταστασης.
    // αν υπαρχει ενας ενεργος παικτης 
    if($active_players == 0) { 
        $new_status = 'not active'; 
    }
    // αν υπαρχει ενας ενεργος παικτης
    else if($active_players == 1) { 
        $new_status = 'initialized'; 
    }
    // αν υπαρχουν 2 ενεργοι παικτες
    else if($active_players == 2 && ($status['status'] == 'not active' || $status['status'] == 'initialized')) {
        // μολις συνδεθουν και οι 2 παικτες, μοιρασε τα φυλλα
        $new_status = 'started';
        $new_turn = 'A';
        reset_board(); // Αρχικό μοίρασμα φύλλων
        $mysqli->query("UPDATE game_status SET last_change = NOW()"); // Αρχικοποίηση χρόνου για τον παίκτη Α
    }

    // αν το παιχνιδι εχει αρχισει
    if($new_status == 'started') {
        
        // αν ενας παικτης στη σειρα του δε παιξει για 15 δευτερολεπτα, εμταβιβασε τη σειρα του στον αλλο παικτη
        $mysqli->query("UPDATE game_status SET p_turn = IF(p_turn='A','B','A'), last_change = NOW() 
                        WHERE status='started' AND last_change < (NOW() - INTERVAL 15 SECOND)");
        
        $res_turn = $mysqli->query("SELECT p_turn FROM game_status");
        $new_turn = $res_turn->fetch_assoc()['p_turn'];

        // οταν τελειωσουν τα φυλλα των παικτων, μοιρασε τους καινουρια
        deal_cards();

        // αν το κλειστο ντεκ τελειωσει -> τελειωνει το παιχνιδι
        $res_end = $mysqli->query("SELECT COUNT(*) as c FROM board WHERE pos IN ('deck', 'hand_A', 'hand_B')");
        if($res_end->fetch_assoc()['c'] == 0) {
            $new_status = 'ended';
            // υπολογισμος τελικων ποντων
            handle_end_game(); 
            return;
        }
    }

    // τελικη ενημερωση βασης
    $sql = 'UPDATE game_status SET status=?, p_turn=?';
    $st = $mysqli->prepare($sql);
    $st->bind_param('ss', $new_status, $new_turn);
    $st->execute();
}
// υπολογισμος τελικου σκορ
function handle_end_game() {
    global $mysqli;
    
    // +3 ποντοι οποιος εχει τα περισσοτερα φυλλα -> 
    $res = $mysqli->query("SELECT pos, COUNT(*) as count FROM board WHERE pos IN ('pile_A', 'pile_B') GROUP BY pos");
    $counts = ['pile_A' => 0, 'pile_B' => 0];
    while($row = $res->fetch_assoc()) { $counts[$row['pos']] = $row['count']; }
    
    if ($counts['pile_A'] > $counts['pile_B']) {
        $mysqli->query("UPDATE players SET score = score + 3 WHERE player = 'A'");
    } elseif ($counts['pile_B'] > $counts['pile_A']) {
        $mysqli->query("UPDATE players SET score = score + 3 WHERE player = 'B'");
    }

    // συγκριση τελικων σκορ και ορισμος νικητη
    $res_scores = $mysqli->query("SELECT player, score FROM players");
    $final_scores = [];
    while($s = $res_scores->fetch_assoc()) { $final_scores[$s['player']] = $s['score']; }
    
    $winner = 'D';
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
    $mysqli->query("CALL clean_game()");
    header('Content-Type: application/json');
    print json_encode(['message' => 'Game reset successfully']);
}