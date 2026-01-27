let me = {}; 
let game_status = {}; 
let board = {};
// μεταβλητή για το χρονόμετρο 60''
let turnTimer = null; 


$(function() {
    $('#playBtn').click(function() { 
        $(this).hide(); $('#login-form').show();
    });
    $('#kseri_login').click(login_to_game);
    $('#rulesBtn').click(openRules);
    $('#closeRules').click(closeRulesFn);
    $('#resetBtn').click(resetGame);

    // gia theme swtich
  $('#toggle-theme-icon').click(function(e) {
    e.preventDefault();
    $('body').toggleClass('light-mode');
    
    // apothikeysi protimisis
    if($('body').hasClass('light-mode')) {
        localStorage.setItem('theme', 'light');
    } else {
        localStorage.setItem('theme', 'dark');
    }
});
});

function login_to_game() {
    let user = $('#username').val();
    let p = $('#player').val();
    if (user == '') { alert('Πρέπει να εισάγεις ένα όνομα χρήστη!'); return; }

    $.ajax({
        url: "kseri.php/player/" + p, 
        method: 'POST',
        dataType: "json",
        contentType: 'application/json',
        data: JSON.stringify({ username: user, player: p }),
        success: login_result,
        error: login_error
    });
}

function login_result(data) {
    if (data && data[0]) {
        me = data[0]; // Αποθήκευση στοιχείων και token παίκτη
        $('#my-name-val').text(me.username);
        $('#my-token-val').text(me.token); 
        
        $('#login-screen').hide();
        $('#board-screen').show();

        game_status_update(); // Έναρξη του polling
    }
}

function game_status_update() {
    $.ajax({
        url: "kseri.php/status/", 
        headers: {"App-Token": me.token},
        success: update_status,
        error: login_error
    });
}


function update_status(data) {
    if (!data || data.length === 0) return;
    
    let oldTurn = game_status.p_turn;
    game_status = data[0];

    // ενημερωση σκορ και ξερων
    let myScore = (me.player == 'A') ? game_status.score_A : game_status.score_B;
    let oppScore = (me.player == 'A') ? game_status.score_B : game_status.score_A;
    let myKseres = (me.player == 'A') ? game_status.kseres_A : game_status.kseres_B;
    let oppkseres = (me.player == 'A') ? game_status.kseres_B : game_status.kseres_A;
    
    $('#score-me').text(myScore);
    $('#score-opponent').text(oppScore);
    $('#kseres-me').text(myKseres);
    $('#kseres-opp').text(oppkseres);

    if (game_status.status == 'started') {
        if (game_status.p_turn !== oldTurn) {
            fill_board();
            if (game_status.p_turn == me.player) {
                startTimer(60);
                
            } else {
                stopTimer();
            }
        }
    } else if (game_status.status == 'ended') {
        stopTimer();
        handleEndOfGame(game_status.result);
        console.log("Το παιχνίδι τελείωσε!");
    }

    update_info();
    update_player_names();
    setTimeout(game_status_update, 4000); 
}

//Όταν το handle_end_game() στην PHP εκτελεστεί, ενημερώνει τη βάση με result='A', 'B' ή 'D'. json 
//μεταφει πληροφορια και μεσα απο js εμφανιζει το αντιστοιχο αποτελεσμα
function handleEndOfGame(result) {
    if (!result) return;
    if (result === 'D') {
        draw();
    } else if (result === me.player) {
        winner();
    } else {
        loser();
    }
}

// χρονομετρο 15 δευτερολεπτα για καθε παικτη στη σειρα του 
function startTimer(seconds) {
    clearInterval(turnTimer);
    let timeLeft = seconds;
    $('#timer-val').text(timeLeft + "s");

    turnTimer = setInterval(function() {
        timeLeft--;
        $('#timer-val').text(timeLeft + "s");
        
        if (timeLeft <= 0) {
            clearInterval(turnTimer);
            // Το polling θα ανιχνεύσει την αλλαγή turn από τον server
            game_status_update(); 
        }
    }, 1000);
}


function stopTimer() {
    clearInterval(turnTimer);
    $('#timer-val').text("-");
}

function fill_board() {
    $.ajax({
        url: "kseri.php/board/",
        headers: {"App-Token": me.token},
        success: draw_board,
        error: login_error
    });
}
// οπτικη απεικονιση τραπεζιου
function draw_board(data) {
    if (!Array.isArray(data)) return;
    board = data;
    
    $('#my_hand, #table, #opponent_hand').empty();

    let tableCount = 0; 
    let deckCount = 0;
    const suitMap = { 'Club': 'Clovers', 'Heart': 'Hearts', 'Spade': 'Pikes', 'Diamond': 'Tiles' };
    
    data.forEach(card => {
        if(card.pos === 'deck') {
            deckCount++;
            return;
        }

        let img = create_card_img(card, suitMap);

        if (card.pos == 'table') {

            let offsetL = tableCount * 18; 
            let offsetT = tableCount * 6;  
            
            img.css({
                'left': offsetL + 'px',
                'top': offsetT + 'px',
                'z-index': tableCount + 1 
            });
            
            $('#table').append(img);
            tableCount++;
        } 
        // δικες μου καρτες
        else if (card.pos == 'hand_' + me.player) {
            img.click(function() { 
                if(game_status.p_turn == me.player) {
                    playCard(card.card_id); 
                }
            });
            $('#my_hand').append(img);
        } 
        // καρτες αντιπαλου
        else if (card.pos.startsWith('hand_')) {
            img.attr('src', 'cards/back.png');
            $('#opponent_hand').append(img);
        }
    });

    $('#deck-count').text(deckCount);
}

// εικονα για καθε φυλλο
function create_card_img(card, suitMap) {
    let s = suitMap[card.card_suit];
    let r = card.card_rank;
    
    if(r == 'J') r = 'Jack'; 
    if(r == 'Q') r = 'Queen'; 
    if(r == 'K') r = 'King';
    
    let fileName = s + "_" + r + "_black.png";
    return $('<img>')
        .attr('src', 'cards/' + fileName)
        .addClass('card_img')
        .attr('data-id', card.card_id);
}

function update_info() {
    let turnText = game_status.p_turn;
    if (turnText === null || turnText === undefined) {
        turnText = "Αναμονή...";
    }
    
    let status_msg = (game_status.status == 'started') ? "Σειρά: " + turnText : "Περιμένουμε παίκτη...";
    $('#game_info').html("Είσαι ο: " + me.player + "<br>" + status_msg);
    update_turn_ui(game_status.p_turn);
}

function update_turn_ui(turn) {
    $('.turn-indicator').remove();
    $('#my-name-val, #opponent-name-val').removeClass('active-turn');

    if (turn === me.player) {
        $('#my-name-val').addClass('active-turn')
            .after('<span class="turn-indicator" style="color: #2ecc71; font-weight: bold;"> (Σειρά σου!)</span>');
    } else if (turn !== null && game_status.status == 'started') {
        $('#opponent-name-val')
            .after('<span class="turn-indicator" style="color: #e74c3c;"> (Σκέφτεται...)</span>');
    }
}

function update_player_names() {
    $.ajax({
        url: "kseri.php/player",
        method: 'GET',
        success: function(players) {
            if (!me || !me.player) return;

            let opponent = players.find(p => p.player !== me.player);
            if (opponent && opponent.username !== null) {
                $('#opponent-name-val').text(opponent.username);
            } else {
                $('#opponent-name-val').text("Αναμονή παίκτη...");
            }
            
            let myself = players.find(p => p.player === me.player);
            if (myself && myself.username !== null) {
                $('#my-name-val').text(myself.username);
            }
        }
    });
}

function resetGame() {
    if (confirm("Θέλεις να κάνετε επαναφορά παιχνιδιού;")) {
        $.ajax({
            url: "kseri.php/reset",
            method: 'POST',
            headers: {"App-Token": me.token},
            success: function() { 
                alert("Το παιχνίδι επανήλθε!");
                location.reload(); 
            },
            error: login_error
        });
    }
}

// αποστολη κινησης παικτη
function playCard(cardId) {
    $.ajax({
        url: "kseri.php/board/card/" + cardId,
        method: 'PUT',
        headers: {"App-Token": me.token},
        success: function(data) {
            draw_board(data); // Άμεση ανανέωση board μετά την κίνηση
            game_status_update(); // Έλεγχος για αλλαγή σειράς
        },
        error: login_error
    });
}

function login_error(data) {
    let x = data.responseJSON;
    console.log("Server error:", data.responseText);
    if (x && x.errormesg) alert("Σφάλμα: " + x.errormesg);
}

function winner(){
    alert("Συγχαρητήρια νίκησες στην ξερή!");
    
    $.ajax({
        url: "kseri.php/reset",
        method: 'POST',
        headers: {"App-Token": me.token},
        success: function() { 
            alert("Το παιχνίδι επανήλθε!");
            location.reload(); 
        },
        error: login_error
    });
}

function loser(){
    alert("Δεν πειράζει την επόμενη φορά θα είσαι πιο τυχερός!");
    
    $.ajax({
        url: "kseri.php/reset",
        method: 'POST',
        headers: {"App-Token": me.token},
        success: function() { 
            alert("Το παιχνίδι επανήλθε!");
            location.reload(); 
        },
        error: login_error
    });
}

function draw(){
    alert("Ισοπαλία!");
    
    $.ajax({
        url: "kseri.php/reset",
        method: 'POST',
        headers: {"App-Token": me.token},
        success: function() { 
            alert("Το παιχνίδι επανήλθε!");
            location.reload(); 
        },
        error: login_error
    });
}



function openRules(){ $('#rules-modal').addClass('open'); }
function closeRulesFn(){ $('#rules-modal').removeClass('open'); }