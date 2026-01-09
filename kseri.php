<?php
require_once "lib/dbconnect.php"; 
require_once "lib/board.php";
require_once "lib/game.php";
require_once "lib/users.php";

// αναγνώριση μεθόδου
$method = $_SERVER['REQUEST_METHOD'];
$request = explode('/', trim($_SERVER['PATH_INFO'],'/'));
$input = json_decode(file_get_contents('php://input'),true);

if($input==null) {
   $input=[]; 
}

if (isset($_SERVER['HTTP_APP_TOKEN'])) {
    $input['token']=$_SERVER['HTTP_APP_TOKEN'];
} else if (!isset($input['token'])) {
    $input['token']='';
}

switch ($r=array_shift($request)) {
    // endpoint board
    case 'board' : 
        switch ($b=array_shift($request)) {
            case '':
            case null: 
                handle_board($method, $input); 
                break;
            // endpoint board/card/ιδ -> καλω handle_card
            case 'card': 
                handle_card($method, $request[0], $input); 
                break;
            default: 
                header("HTTP/1.1 404 Not Found"); 
                break;
        }
        break;
        // endpoint /status
    case 'status': 
        handle_status($method); 
        break;
        // endpoint /player
    case 'player': 
        handle_player($method, $request, $input); 
        break;
        // endpoint /reset
    case 'reset':
        handle_reset($method); 
        break;
    default: 
        header("HTTP/1.1 404 Not Found"); 
        break;
}

function handle_board($method, $input) {
    // εμφάνισε τρέχουσα κατάσταση board
    if($method=='GET') {
         show_board(); 
    } 
    // κάνε reset το board
    else if ($method=='POST') {
         reset_board(); show_board();
    } 
    else { 
        header('HTTP/1.1 405 Method Not Allowed'); 
    }
}

function handle_reset($method) {
    // κάνε reset τα πάντα
    if($method=='POST') { 
        reset_game_all(); 
    } 
    else { 
        header('HTTP/1.1 405 Method Not Allowed'); 
    }
}

function handle_card($method, $card_id, $input) {
    // ο χρήστης παίζει τη συγκεκριμένη κάρτα
    if($method=='PUT') { 
        play_card($card_id, $input['token']); 
    } 
    else { 
        header('HTTP/1.1 405 Method Not Allowed'); 
    }
}

function handle_player($method, $p, $input) {
    $b=array_shift($p);
    if($b=='' || $b==null) {
        // endpoint /player
        if($method=='GET') {
            show_users(); 
        } 
        else {
            header("HTTP/1.1 400 Bad Request"); 
        }
        // endpoint /player/A ή /player/B
    } else if($b=='A' || $b=='B') {
        handle_user($method, $b, $input);
    } else { 
        header("HTTP/1.1 404 Not Found"); 
    }
}

function handle_status($method) {
    // επιστρέφει την κατάσταση παιχνιδιού
    if($method=='GET') { 
        show_status();
    } 
    else { 
        header('HTTP/1.1 405 Method Not Allowed');
     }
}
?>