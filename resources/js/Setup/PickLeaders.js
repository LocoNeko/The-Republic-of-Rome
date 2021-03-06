/*
 * For 'PickLeaders' :
 * A (droppable) Senator card needs to have these attributes :
 * - card_id
 * The draggables must provide  a verb
 */

$(document).ready(function(){
    $('.sprite-Senator').each( function() {
        $(this).droppable( {
            drop: function ( event, ui ) {
                var json = {} ;

                // Verb - The verb must be on the draggable but if it's not, get it from the droppable
                var verb = ui.draggable.attr('verb') ;

                json['dropOn'] = $(event.target).attr('card_id') ;
                json['dragFrom'] = ui.draggable.attr('card_id') ;

                /* Emit the Update event to socket.io
                 * This requires a gameId (or "Lobby") which should be in the "realm" attribute of the title tag
                 */
                socket.emit( 'Update', $("title").attr('realm') );

                $.post(
                    window.location.pathname + '/' + verb ,
                    json ,
                    function( data ) {
                        window.location.reload(true) ;
                    } ,
                    "json"
                );

            }
        });
    });
});   