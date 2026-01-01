<?php


function show_board() {
    global $mysqli;
    $sql = 'select b.*, d.card_rank, d.card_suit from board b inner join deck d on b.card_id = d.card_id order by b.weight asc';
    $st = $mysqli->prepare($sql);
    $st->execute();
    $res = $st->get_result();
    header('Content-type: application/json');
    print json_encode($res->fetch_all(MYSQLI_ASSOC), JSON_PRETTY_PRINT);
}

function reset_board() {
    global $mysqli;

    $mysqli->query("UPDATE board SET pos='deck', weight=NULL");
    
    $res = $mysqli->query("select card_id from board where pos='deck'");
    $cards = [];
    while($row = $res->fetch_assoc()) { $cards[] = $row['card_id']; }
    shuffle($cards);

    for($i=0; $i<6; $i++) {
        $id = array_pop($cards);
        $mysqli->query("update board set pos='hand_A' where card_id=$id");
    }
    for($i=0; $i<6; $i++) {
        $id = array_pop($cards);
        $mysqli->query("update board set pos='hand_B' where card_id=$id");
    }
    for($i=1; $i<=4; $i++) {
        $id = array_pop($cards);
        $mysqli->query("update board set pos='table', weight=$i where card_id=$id");
    }

    $mysqli->query("update game_status set status='started', p_turn='A'");
}


function play_card($card_id, $token) {
    global $mysqli;
    // μέσω του τοκεν ταυτοποιώ ποίος παίκτης παίζει
    $current_p = current_player($token);
    if($current_p == null) {
        header("HTTP/1.1 401 Unauthorized");
        exit;
    }

    // βρίσκω ποιο φυλλο βρισκεται στην κορυφη της τραπουλας που παιζεται
    $res_top = $mysqli->query("select b.card_id, d.card_rank from board b join deck d on b.card_id = d.card_id where b.pos = 'table' order by b.weight desc limit 1");
    $top_card = $res_top->fetch_assoc();
    
    $res_played = $mysqli->query("select card_rank from deck where card_id = $card_id");
    $played_card = $res_played->fetch_assoc();

    $pile = ($current_p == 'A') ? 'pile_A' : 'pile_B';

    if ($top_card && ($played_card['card_rank'] == $top_card['card_rank'] || $played_card['card_rank'] == 'J')) {
        
        $mysqli->query("update board set pos='$pile', weight=0 where pos='table' or card_id=$card_id");
        $mysqli->query("update game_status set p_turn = if(p_turn='A','B','A')");
    } else {
       
        $st = $mysqli->prepare('call move_card(?, ?)');
        $st->bind_param('is', $card_id, $current_p);
        $st->execute();
    }

    $mysqli->query("update players set last_action=now() where player='$current_p'");
    show_board();
}
?>