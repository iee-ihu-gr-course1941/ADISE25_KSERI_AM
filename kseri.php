<?php

ob_start(); // Ξεκινάει την αποθήκευση του output στη μνήμη
require_once "lib/dbconnect.php"; 
require_once "lib/board.php";
require_once "lib/game.php";
require_once "lib/users.php";
ob_clean(); // Διαγράφει οτιδήποτε "βρώμικο" (όπως το 2ms1) βγήκε από τα παραπάνω αρχεία
ini_set('display_errors', 1);
error_reporting(E_ALL);
// kseri.php - Στην κορυφή του αρχείου
// kseri.php - Στην κορυφή, μετά τα require
$method = $_SERVER['REQUEST_METHOD'];
$request_path = $_SERVER['PATH_INFO'] ?? '';
$request = explode('/', trim($request_path, '/'));

// Χρησιμοποιούμε έναν πιο σίγουρο έλεγχο για το reset
if ($method === 'POST' && (isset($request[0]) && $request[0] === 'reset')) {
    global $mysqli;

    try {
        // 1. Καθαρισμός Βάσης - Χρησιμοποιούμε IS NOT NULL για να μην σκάει αν είναι ήδη NULL
        $mysqli->query("UPDATE players SET username=NULL, token=NULL");
        $mysqli->query("UPDATE board SET pos='deck', weight=NULL");
        $mysqli->query("UPDATE game_status SET status='not active', p_turn='A', result=NULL");

        // 2. Καθαρισμός Session
        if (session_status() === PHP_SESSION_NONE) session_start();
        session_unset();
        session_destroy();

        header('Content-Type: application/json');
        echo json_encode(["status" => "success"]);
        exit;
    } catch (Exception $e) {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(["errormesg" => $e->getMessage()]);
        exit;
    }
}

// 2. Προετοιμασία εισόδου για τον Router
$input = json_decode(file_get_contents('php://input'), true);
if ($input == null) $input = [];

if (isset($_SERVER['HTTP_APP_TOKEN'])) {
    $input['token'] = $_SERVER['HTTP_APP_TOKEN'];
} elseif (!isset($input['token'])) {
    $input['token'] = '';
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
        handle_player('PUT', $request, $input);
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

