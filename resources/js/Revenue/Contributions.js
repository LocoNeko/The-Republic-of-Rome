/*
 *  Bypass the default json-submit by checking the "Give to Rome" verb, in order to make the slider modal pop up before submitting
 *  Get all needed information (card_id , treasury) from the Senator card
*/
$(document).ready(function(){
    $("form[verb='Give to Rome']").submit(function(e){
        //prevent Default functionality
        e.preventDefault();
        // Get the card_id from the FORM object
        var card_id = $('[name="card_id"]').attr('value') ;
        // Get the treasury from the card object, found through its card_id
        var maxAmount = $('[card_id="'+card_id+'"]').attr('treasury') ;
        $('#contributionSlider').slider({
            min: 1,
            max: maxAmount ,
            value: 1,
            slide: function( event, ui ) {
                $('#contributionAmount').val( ui.value + 'T.' );
            }
        });
        $('#contributionModal').modal('show') ;
        $('#submitContribution').attr('from_senator' , card_id ) ;

    });
    $('#submitContribution').click(function(){
        $('#contributionModal').modal('hide') ;
        var json = {} ;
        json['fromSenator'] = $('#submitContribution').attr('from_senator') ;
        json['amount'] = $('#contributionSlider').slider('option' , 'value') ;

        /* Emit the Update event to socket.io
         * This requires a gameId in the "realm" attribute of the title
         */
        socket.emit('Update', $("title").attr('realm'));

        $.post(
            window.location.pathname + '/Contribute',
            json ,
            function( data ) {
                window.location.reload(true) ;
            } ,
            "json"
        );
    });

});