<?php
// lib/board.php

// επιστροφη τρεχυυσας καταστασης board
function show_board() {
    global $mysqli;
    header('Content-type: application/json');
    print json_encode(read_board(), JSON_PRETTY_PRINT);
}
// αναγνωση φυλλων απο βαση, ωστε να συνδεσω πινακες board-deck για να λαβω suit, rank
function read_board() {
    global $mysqli;
    $sql = 'SELECT b.*, d.card_rank, d.card_suit 
            FROM board b 
            INNER JOIN deck d ON b.card_id = d.card_id 
            ORDER BY b.weight ASC';
    $st = $mysqli->prepare($sql);
    $st->execute();
    return $st->get_result()->fetch_all(MYSQLI_ASSOC);
}
// αρχικοποιηση παιχνιδιου. ανακατεμα τραπουλας και μοιρασμα 6 φυλλων σε καθε παικτη , 4 στο τραπεζι ανοιχτα
function reset_board() {
    global $mysqli;
    // επαναφορα φυλλων στη τραπουλα 
    $mysqli->query("UPDATE board SET pos='deck', weight=NULL");
    
    // ληψη id καρτων για ανακατεμα
    $res = $mysqli->query("select card_id from board");
    $cards = [];
    while($row = $res->fetch_assoc()) { $cards[] = $row['card_id']; }
    shuffle($cards); //ανακατεμα

    // μοιρασμα 6 φυλλων σε καθε παικτη
    for($i=0; $i<6; $i++) {
        $idA = array_pop($cards);
        $mysqli->query("update board set pos='hand_A' where card_id=$idA");
        $idB = array_pop($cards);
        $mysqli->query("update board set pos='hand_B' where card_id=$idB");
    }
    // 4 φυλλα στο τραπεζι
    for($i=1; $i<=4; $i++) {
        $idT = array_pop($cards);
        $mysqli->query("update board set pos='table', weight=$i where card_id=$idT");
    }
}

// οταν οι παικτες δεν εχουν αλλα φυλλα, τους ξανα μοιραζει απο 6 
function deal_cards() {
    global $mysqli;
    $res = $mysqli->query("SELECT COUNT(*) as c FROM board WHERE pos IN ('hand_A', 'hand_B')");
    if($res->fetch_assoc()['c'] > 0) return;

    // επιλογη τυχαιων 12 φυλλων απο το κλειστο ντεκ
    $res_deck = $mysqli->query("SELECT card_id FROM board WHERE pos='deck' ORDER BY RAND() LIMIT 12");
    $cards = [];
    while($r = $res_deck->fetch_assoc()) { $cards[] = $r['card_id']; }

    if(count($cards) == 12) {
        for($i=0; $i<6; $i++) {
            $idA = array_shift($cards);
            $mysqli->query("UPDATE board SET pos='hand_A' WHERE card_id=$idA");
            $idB = array_shift($cards);
            $mysqli->query("UPDATE board SET pos='hand_B' WHERE card_id=$idB");
        }
    }
}
// παιξιμο φυλλου
function play_card($card_id, $token) {
    global $mysqli;
    // ταυτοποιηση παικτη
    $current_p = current_player($token);
    if(!$current_p) { header("HTTP/1.1 401 Unauthorized"); exit; }

    // κατασταση τραπεζιου και φυλλου που παιχτηκε πριν
    $res_table = $mysqli->query("SELECT b.card_id, d.card_rank, d.card_suit FROM board b JOIN deck d ON b.card_id = d.card_id WHERE b.pos = 'table' ORDER BY b.weight ASC");
    $table_cards = $res_table->fetch_all(MYSQLI_ASSOC);
    // τελευταιο φυλλο που παιχτηκε στο τραπεζι( το πανω-πανω )
    $top_card = end($table_cards); 
    
    $played_res = $mysqli->query("SELECT card_rank, card_suit FROM deck WHERE card_id = $card_id");
    $played_card = $played_res->fetch_assoc();
    
    // σε ποια στοιβα θα πανε τα φυλλα που μαζεψε ο παικτης
    $pile = ($current_p == 'A') ? 'pile_A' : 'pile_B';
    $earned_points = 0;
    $earned_kseres = 0;
    $is_capture = false;

    // λογικη μαζεματος
    // αν ο χρηστης παιξει βαλε
    if ($played_card['card_rank'] == 'J') {
        if (count($table_cards) > 0) {
            $is_capture = true;
            // αν το τραπεζι εχει κατω μονο εναν βαλε -> ξερη βαλε (20 ποντοι)
            if (count($table_cards) === 1 && $top_card['card_rank'] == 'J') {
                $earned_points += 20; 
                $earned_kseres = 1;
            }
        }
        // αν το φυλλο ταιριαζει με το τελευταιο φυλλο στο τραπεζι
    } elseif ($top_card && $played_card['card_rank'] == $top_card['card_rank']) {
        $is_capture = true;
        // αν το τραπεζι εχει κατω μονο ενα φυλλο -> απλη ξερη (10 ποντοι)
        if (count($table_cards) === 1) {
            $earned_points += 10;
            $earned_kseres = 1;
        }
    }

    // υπολογισμος ποντων
    if ($is_capture) {
        $captured_cards = array_merge($table_cards, [['card_rank' => $played_card['card_rank'], 'card_suit' => $played_card['card_suit']]]);
        foreach ($captured_cards as $c) {
            // φιγουρες και ασσοι -> 1 ποντος
            if (in_array($c['card_rank'], ['A', 'K', 'Q', 'J', '10'])) $earned_points++;
            // 10 καρο -> 1 εξτρα ποντος, αρα 2 ποντοι
            if ($c['card_rank'] == '10' && $c['card_suit'] == 'Diamond') $earned_points++;
            // 2 σπαθι -> 1 εξτρα ποντος, αρα 2 ποντοι
            if ($c['card_rank'] == '2' && $c['card_suit'] == 'Club') $earned_points++;
        }

        // μεταφορα φυλλων στο pile του παικτη
        $mysqli->query("UPDATE board SET pos='$pile', weight=0 WHERE pos='table' OR card_id=$card_id");
        $mysqli->query("UPDATE players SET score = score + $earned_points, kseres = kseres + $earned_kseres WHERE player = '$current_p'");
    } else {
        // αν ο παικτης δε μαζεψε φυλλ, απλα παιζει ενα 
        drop_on_table($card_id);
    }

   // αλλαζω σειρα στον αντιπαλο
    $mysqli->query("UPDATE game_status SET p_turn = IF(p_turn='A','B','A'), last_change = NOW()");
    $mysqli->query("UPDATE players SET last_action=NOW() WHERE player='$current_p'");
    
    update_game_status(); 
    show_board();
}

// τοποθετω φυλλο στο τραπεζι και δινω το επομενο weight (δηλαδη τον αριθμο της καρτας στη στοιβα παιγμενων καρτων)
function drop_on_table($card_id) {
    global $mysqli;
    $mysqli->query("UPDATE board SET pos='table', weight=(SELECT IFNULL(MAX(weight),0)+1 FROM (SELECT weight FROM board WHERE pos='table') as x) WHERE card_id=$card_id");
}

?>