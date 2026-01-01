<?php

require_once "lib/dbconnect.php"; 
require_once "lib/board.php";
require_once "lib/game.php";
require_once "lib/users.php";

// πάρε το HTTP method, το request και το input
$method = $_SERVER['REQUEST_METHOD'];
$request = explode('/', trim($_SERVER['PATH_INFO'],'/'));
$input = json_decode(file_get_contents('php://input'),true);

if($input==null) {
    $input=[];
}

// διαχείριση token από header ή input
if (isset($_SERVER['HTTP_APP_TOKEN'])) {
    $input['token']=$_SERVER['HTTP_APP_TOKEN'];
} else if (!isset($input['token'])) {
    $input['token']='';
}

// κεντρικός ρουτερ
switch ($r=array_shift($request)) {
    // αν το request είναι 'board'
    case 'board' : 
        switch ($b=array_shift($request)) {
            // αν δεν υπάρχει επιπλέον διαδρομή, δηλαδή /board
            case '':
            case null: 
                handle_board($method, $input);
                break;
            case 'card': 
                
                handle_card($method, $request[0], $input);
                break;
            default: 
                header("HTTP/1.1 404 Not Found");
                print "<h1>Page not found (404)</h1>";
                break;
        }
        break;
    case 'status': 
        if(sizeof($request)==0) {
            handle_status($method);
        } else {
            header("HTTP/1.1 404 Not Found");
            print "<h1>Page not found (404)</h1>";
        }
        break;
    case 'player': 
        handle_player($method, $request, $input);
        break;
    case 'reset':
        handle_reset($method);
        break;
    default:     
        header("HTTP/1.1 404 Not Found");
        print "<h1>Page not found (404)</h1>";
        exit;
}


function handle_board($method, $input) {
    if($method=='GET') {
        show_board();
    } else if ($method=='POST') {
        reset_board();
    } else {
        header('HTTP/1.1 405 Method Not Allowed');
        print "<h1>Method Not Allowed (405)</h1>";
    }
}

function handle_card($method, $card_id, $input) {
    if($method=='PUT') {
        play_card($card_id, $input['token']);
    } else {
        header('HTTP/1.1 405 Method Not Allowed');
        print "<h1>Method Not Allowed (405)</h1>";
    }
}

function handle_player($method, $p, $input) {
    switch ($b=array_shift($p)) {
        case '':
        case null: 
            if($method=='GET') {
                show_users();
            } else {
                header("HTTP/1.1 400 Bad Request"); 
                print json_encode(['errormesg'=>"Method $method not allowed here."]);
            }
            break;
        case 'A': 
        case 'B': 
            handle_user($method, $b, $input);
            break;
        default: 
            header("HTTP/1.1 404 Not Found");
            print json_encode(['errormesg'=>"Player $b not found."]);
            break;
    }
}

function handle_status($method) {
    if($method=='GET') {
        show_status();
    } else {
        header('HTTP/1.1 405 Method Not Allowed');
        print "<h1>Method Not Allowed (405)</h1>";
    }
}

function handle_reset($method) {
    if($method=='POST') {
        reset_game_all();
    } else {
        header('HTTP/1.1 405 Method Not Allowed');
        print "<h1>Method Not Allowed (405)</h1>";
    }
}

?>