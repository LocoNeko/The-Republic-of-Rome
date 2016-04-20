$(document).ready(function(){
    $('.revenueRedistribution , .sprite-Senator').droppable( {
        tolerance: 'pointer',
        drop: function ( event, ui ) {
            // treasury is defined if you drag from party or Senator
            var maxAmount = ui.draggable.attr('treasury') ;
            var giver = ui.draggable.attr('item_name') ;
            var recipient = $(event.target).attr('item_name') ;
            $('#myModalLabel').text('Transfer from ' + giver + ' to ' + recipient );
            $('#redistributionAmount' ).val('1T.' );
            $('#redistributionSlider').slider({
                min: 1,
                max: maxAmount ,
                value: 1,
                slide: function( event, ui ) {
                    $('#redistributionAmount').val( ui.value + 'T.' );
                }
            });
            $('#redistributionModal').modal('show') ;
            // Update the elements that hold the card_id and/or party_id of giver & recipient
            if (ui.draggable.hasClass('sprite-Senator'))
            {
                $('#submitRedistribution').attr('from_senator' , ui.draggable.attr('card_id') ) ;
                $('#submitRedistribution').attr('from_party' , '' ) ;
            }
            else
            {
                $('#submitRedistribution').attr('from_senator' , '' ) ;
                $('#submitRedistribution').attr('from_party' , ui.draggable.attr('user_id') ) ;
            }
            if ($(event.target).hasClass('sprite-Senator'))
            {
                $('#submitRedistribution').attr('to_senator' , $(event.target).attr('card_id') ) ;
                $('#submitRedistribution').attr('to_party' , '' ) ;
            }
            else
            {
                $('#submitRedistribution').attr('to_senator' , '' ) ;
                $('#submitRedistribution').attr('to_party' , $(event.target).attr('user_id') ) ;
            }
        }
    });
    $('#submitRedistribution').click(function(){
        $('#redistributionModal').modal('hide') ;
        var json = {} ;
        json['fromSenator'] = $('#submitRedistribution').attr('from_senator') ;
        json['toSenator'] = $('#submitRedistribution').attr('to_senator') ;
        json['fromParty'] = $('#submitRedistribution').attr('from_party') ;
        json['toParty'] = $('#submitRedistribution').attr('to_party') ;
        json['amount'] = $('#redistributionSlider').slider('option' , 'value') ;

        /* Emit the Update event to socket.io
         * This requires a gameId in the "realm" attribute of the title
         */
        socket.emit('Update', $("title").attr('realm'));

        $.post(
            window.location.pathname + '/revenueRedistribute',
            json ,
            function( data ) {
                window.location.reload(true) ;
            } ,
            "json"
        );
    });
});