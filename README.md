-- περιγραφή API --

BASE URL που εχω τα αρχεια στον υπολογιστη μου 
http://localhost/ADISE25_KSERI_AM/kseri.php
ολα τα endpoints αναφερονται στο συγκεκριμενο url

Περιγραφή API

Methods

Board
Ανάγνωση Board

GET /board/

Επιστρέφει την τρέχουσα κατάσταση του board.

POST /board/




---------------- ΡΟΗ ΓΙΑ FRONT END --------------------
1. front -> χρηστης εισαγει το username του (σε καποιο input field ) και παταει εναρξη παιχνιδιου
back -> καλω API endpoint PUT /player/A ή /Β , το οποιο επιστρεφει το token του παικτη
2. front -> αποθηκευω το token μεσω js και το player_id 
3. js polling 
back -> GET /status . αναμονη αντιπαλου, ελεγχεται σε λουπα αν το status εγινε started
4. status = started 
back -> GET /board 
εναρξη παιχνιδιου , μοιραζονται τα αρχικα φυλλα κτλ.


----- ΚΑΝΟΝΕΣ ΓΙΑ ΝΑ ΛΕΙΤΟΥΡΓΗΣΕΙ ------
θα δημιουργησεις locally μεσω του localhost/phpmyadmin την βαση με ονομα 'kseri_db' . εκει θα κανεις import το αρχειο kseri_db.sql ή θα κανεις copy-paste ολες τις εντολες sql του αρχειου
ετσι θα εχεις την βαση την οποια χρησιμοποιουσα 






-------- DATABASE TABLES ------------
Database Tables
1. board -> contains information about the board of the game 

Attribute        Description                Values
card_id    card that is being moved 
pos        defines where the card is       deck,hand_A,hand_B,table,pile_A,pile_B
weight     position of card in the stack     

2. players

Attribute        Description                               Values
username         player's username                         varchar
player           defines if player is A or B               'A', 'B'
token            unique authentication token                 hex 
last_action      timestamp of the last action a player did  timestamp

3. deck

Attribute        Description                               Values
card_id          unique card id                          
card_rank        card rank                         A,2,3,4,5,6,7,8,9,10,J,Q,K
card_suit        card suit                         Club,Diamond,Heart,Spade

4. game_status

Attribute        Description                               Values
status       status of the game     not active, initialized, started,ended,aborded  
p_turn       which player's turn it is           'A', 'B'
result       which player won                    'A','B', 'D' (draw)
last_change      timestamp of last change               timestamp      