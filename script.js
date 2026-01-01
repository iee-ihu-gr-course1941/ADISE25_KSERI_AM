let me = {}; 
let game_status = {}; 
let board = {};

$(function() {
    $('#playBtn').click(function() { $(this).hide(); $('#login-form').show(); });
    $('#kseri_login').click(login_to_game);
    $('#rulesBtn').click(openRules);
    $('#closeRules').click(closeRulesFn);
});


function login_to_game() {
    let user = $('#username').val();
    let p = $('#player').val();
    if (user == '') { alert('Πρέπει να εισάγεις ένα όνομα χρήστη!'); return; }

    $.ajax({
        url: "kseri.php/player/" + p, 
        method: 'PUT',
        dataType: "json",
        contentType: 'application/json',
        data: JSON.stringify({ username: user, player: p }),
        success: login_result,
        error: login_error
    });
}

function login_result(data) {
    if (data && data[0]) {
        me = data[0]; // αποθήκευση τοκεν παικτη
        $('#my-name-val').text(me.username);
        $('#my-token-val').text(me.token); 
        
        $('#login-screen').hide();
        $('#board-screen').show();

        game_status_update(); 
    }
}

function game_status_update() {
    $.ajax({
        url: "kseri.php/status/", 
        headers: {"App-Token": me.token},
        success: update_status 
    });
}

function update_status(data) {
    if (!data || data.length === 0) return;
    let new_status = data[0];

    // αν άλλαξε το status ή η σειρά, ανανέωσε το board
    if (game_status.status != new_status.status || game_status.p_turn != new_status.p_turn) {
        fill_board();
    }
    
    game_status = new_status;
    update_info();
    update_player_names(); 

    setTimeout(game_status_update, 4000); 
}

function fill_board() {
    $.ajax({
        url: "kseri.php/board/",
        headers: {"App-Token": me.token},
        success: draw_board
    });
}

function draw_board(data) {
    if (!Array.isArray(data)) return;
    board = data;
    $('#my_hand, #table, #opponent_hand').empty();

    // εμφανιση καρτων που απομενουν στο ντεκ
    let deckCards = data.filter(c => c.pos === 'deck').length;
    $('#deck-count').text(deckCards);

    const suitMap = { 'Club': 'Clovers', 'Heart': 'Hearts', 'Spade': 'Pikes', 'Diamond': 'Tiles' };
    
    data.forEach(card => {
        if(card.pos === 'deck') return;

        let img = create_card_img(card, suitMap);

        if (card.pos == 'hand_' + me.player) {
            $('#my_hand').append(img);
        } else if (card.pos == 'table') {
            $('#table').append(img);
        } else if (card.pos == 'hand_' + (me.player == 'A' ? 'B' : 'A')) {
            $(img).attr('src', 'cards/back.png');
            $('#opponent_hand').append(img);
        }
    });
}

function create_card_img(card, suitMap) {
    let s = suitMap[card.card_suit];
    let r = card.card_rank;
    if(r == 'J') r = 'Jack'; if(r == 'Q') r = 'Queen'; if(r == 'K') r = 'King';
    let fileName = s + "_" + r + "_black.png";
    return $('<img>').attr('src', 'cards/' + fileName).addClass('card_img').attr('data-id', card.card_id);
}

function update_info() {
    let status_msg = (game_status.status == 'started') ? "Σειρά: " + game_status.p_turn : "Αναμονή...";
    $('#game_info').html("Είσαι ο: " + me.player + "<br>" + status_msg);
    update_turn_ui(game_status.p_turn); // Κλήση της συνάρτησης
}

function update_turn_ui(turn) {
    $('.turn-indicator').remove();
    $('#my-name-val, #opponent-name-val').removeClass('active-turn');

    if (turn === me.player) {
        $('#my-name-val').addClass('active-turn').after('<span class="turn-indicator" style="color: #2ecc71;"> Σειρά σου!</span>');
    } else if (turn !== null) {
        $('#opponent-name-val').after('<span class="turn-indicator" style="color: #e74c3c;"> Σκέφτεται...</span>');
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
            success: function() { location.reload(); }
        });
    }
}

function login_error(data) {
    let x = data.responseJSON;
    alert("Σφάλμα: " + (x ? x.errormesg : "Πρόβλημα σύνδεσης"));
}

function openRules(){ $('#rules-modal').addClass('open'); }
function closeRulesFn(){ $('#rules-modal').removeClass('open'); }