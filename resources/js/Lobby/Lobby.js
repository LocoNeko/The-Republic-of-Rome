/*
 * JS for the Lobby
 */

/*
 * in Lobby/Create.twig :
 * - Move a variant left (from unplayed to played)
 */

$('#move_left').click(function() {
    $('.variants').append($('.variants_not_played .list-group-item'));
});

/*
 * in Lobby/Create.twig :
 * - Move a variant right (from played to unplayed)
 */

$('#move_right').click(function() {
    $('.variants_not_played').append($('.variants .list-group-item'));
});

/*
 * in Lobby/List.twig :
 * - Tooltips on number of players and variants
 */

$(function () {
    $('.LobbyListNbPlayers , .LobbyListVariants').tooltip() ;
}) ;

/*
 * in Lobby/List.twig :
 * - dataTable() for the List of Games
 */

$(document).ready(function(){
    $('#listGames').dataTable();
});
