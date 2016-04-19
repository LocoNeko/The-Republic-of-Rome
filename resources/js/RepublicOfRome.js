// Connecting to the socket.io server :
// - The socket.io server is connected to a URL given as an attribute to the HTML tag 
// - The 'Update' event triggers a reloading of the window if the realms are the same (a gameId or "Lobby")
var socket = io.connect($("html").attr('ws_client'));
socket.on('Update', function(realm) {
    if ( realm === $("title").attr('realm') )
    {
        window.location.reload(true) ;
    }
});

function getReady(phase , subPhase)
{
    /**
     * Global function, that should always be loaded and ready
     */

    /**
     * SUBMIT WITH VERB
     */
    $('.submitWithVerb').click(function(e)
    {
        // Get the current json data from the data-json attribute in the body
        var json = JSON.parse($(document.body).attr('data-json')) ;
        var card = $(this).closest('.sprite') ;
        /*
         * If this is on a card, add the card's data-json to this one
         * Otherwise, collect all values with a global-postable class
         * TO DO : not all global-postable are done
         */
        if (card.length > 0)
        {
            $.extend(json , JSON.parse(card.attr('data-json'))) ;
        }       
        else
        {
            // Collect all global-postable, put them in the json
            $('.global-postable , .persuasionAddedBribe').each(function(i, obj) {
                json[obj.name] = obj.value ;
            });
        }
        
        // Get the verb from the button, put it in the json as well
        json['verb'] = $(this).attr('verb') ;

        // Store the json data in the 'data-json' attribute of the body
        $(document.body).attr('data-json' , JSON.stringify(json)) ;
        
        /*
         *  Finally, check if the button's json-data as a slider (can be extended to other functions)
         *  Sliders will by pass the normal submit
         */
        
        var dataJsonButton = $(this).attr('data-json');
        if (typeof dataJsonButton !== typeof undefined && dataJsonButton !== false)
        {
            var jsonButton = JSON.parse(dataJsonButton) ;
            if (jsonButton.action[0]==="slider")
            {
                displayModalSlider(jsonButton.action[1] , '' , jsonButton.action[2] , jsonButton.action[3] , jsonButton.action[4] , jsonButton.action[5]) ;
            }
            else if (jsonButton.action[0]==="fixedAmount")
            {
                json["amount"] = jsonButton.action[1] ;
                submitJSON(json) ;
            }
            else
            {
                alert("ERROR - Wrong action (should be 'slider')");
            }
        }
        else
        {
            submitJSON(json) ;
        }
    });
    
    /**
     * SUBMIT SLIDER
     * - Get the current json data from the data-json attribute in the body
     * - Adds to that data :
     * > The value of the slider
     * > The verb of the modal's submit button
     * - Submit that data
     */
    $('.slider-submit').click(function()
    {
        var modalID = $(this).closest('.modal').attr('id') ;
        $('#'+modalID).modal('hide') ;
        var json = JSON.parse($(document.body).attr('data-json')) ;
        json['value'] = $('#'+modalID+'_slider').slider('option' , 'value') ;
        json['verb'] = $(this).attr('verb') ;
        submitJSON(json) ;
    });

    /**
     *  Popover for Rome Current State
     */
    $('.globalRomeCurrentState').popover({
        container : 'body',
        html : true,
        content: function() {
            return $('.globalRomeCurrentState_content').html();
        }
    });

    /**
     * Popovers for Statesman information when hovering on a Sentor's name
     */
    $('.sprite-position-name').popover();

    /**
     * Phase-specific functions
     */
    if (phase==='Forum' && subPhase==='Persuasion')
    {
        prepareForumPersuasion() ;
    }
}

/*
 * ========================= GLOBAL FUNCTIONS =========================
 */

/**
 * Emits the socket.io notification and posts the data using the <b>Verb</b> passed within the data
 * @param {JSON} JSONdata The data being submitted
 * @returns {undefined}
 */
function submitJSON(json)
{
    //alert('I would normally submit : '+JSON.stringify(json));
    /*
     * Emit the Update event to socket.io
     * This requires a gameId in the "realm" attribute of the title
    */
    
    socket.emit('Update', $("title").attr('realm'));

    $.post(
       window.location.pathname + '/' + json['verb'] ,
       json ,
       function() {
           window.location.reload(true) ;
       } ,
       "json"
    );
    
}

/**
 * Pushes all <li> elements of this object into a JSON
 * @param {element} list
 * @returns {Array}
 */
function JSONifyList(list)
{
    var json ;
    if (list.attr('id')) {
        var listName = list.attr('id') ;
        json[listName] = [];
        // Go through each li tag and add it to the json [list name] array
        list.find('li').each(function() {
            json[listName].push($(this).text());
        });
    }
    return json ;
}


/**
 * Displays a Modal slider
 * @param modalID ID of the Modal to create
 * @param storedData Json data passed to the modal when it was opened
 * @param label Title of the modal
 * @param minValue Minimum value
 * @param maxValue Maximum value
 * @param postText Text to append when displaying
 */
function displayModalSlider(modalID , storedData , label , minValue , maxValue , postText)
{
    var minValue = parseInt(minValue , 10);
    var maxValue = parseInt(maxValue , 10);
    $('#'+modalID+'_storedData').val(storedData) ;
    $('#'+modalID+'_label').text(label);
    $('#'+modalID+'_value').val(minValue+postText);
    $('#'+modalID+'_slider').slider({
        min: minValue,
        max: maxValue ,
        value: minValue,
        slide: function( event, ui ) {
            $('#'+modalID+'_value').val( ui.value + postText);
        }
    });
    $('#'+modalID).modal('show') ;
}

/*
 * ========================= FORUM - PERSUASION =========================
 */

function prepareForumPersuasion()
{
    persuasionUpdateBribeList() ;
    persuasionUpdateOdds() ;
    persuasionUpdateAddedBribe() ;

    $('.persuasionPersuaderList').on('change', function() {
        persuasionUpdateBribeList() ;
        persuasionUpdateOdds() ;
    });

    $('.persuasionTargetList').on('change', function() {
        persuasionUpdateOdds() ;
    });

    $('.persuasionBribe').on('change', function() {
        persuasionUpdateOdds() ;
    });

    $('.persuasionAddedBribe').on('change', function() {
        persuasionUpdateAddedBribe() ;
    });

    $('.persuasionCounterBribeAmount').on('change', function() {
        persuasionUpdateCounterBribe() ;
    });
}

/*
 * When the persuader is changed, update the list of talents that can be spent
 */

function persuasionUpdateBribeList()
{
    $('.persuasionBribe').find('option').remove() ;
    var maximum = parseInt($('.persuasionPersuaderList option:selected').attr('treasury') , 10) ;
    for ( i = 0; i <= maximum; i++ )
    {
        $('.persuasionBribe').append('<option value="'+i+'">'+i+'</option>');
    }
}

/*
 *  Update Odds based on the target, the persuader, the amount currently spent
 *  Enable PERSUADE button if target & persuader are valid
 */

function persuasionUpdateOdds()
{
    var target = $('.persuasionTargetList option:selected') ;
    var persuader = $('.persuasionPersuaderList option:selected') ;
    var bribe = parseInt($('.persuasionBribe option:selected').val() , 10) ;
    // If there are both a target and a persuader, update Odds and enable PERSUADE button
    if (target.attr('description')!=='NONE' && persuader.attr('description')!=='NONE')
    {
        var rollOdds = new Array();
        rollOdds[2] = 1/36; rollOdds[3] = 2/36; rollOdds[4] = 6/36; rollOdds[5] = 10/36; rollOdds[6] = 15/36; rollOdds[7] = 21/36; rollOdds[8] = 26/36; rollOdds[9] = 30/36;

        // Odds values
        var valueAgainst = parseInt(target.attr('loy') , 10) + parseInt(target.attr('treasury') , 10) ;
        var valueFor = parseInt(persuader.attr('ora') , 10) + parseInt(persuader.attr('inf') , 10) + bribe ;
        var valueTotal = valueFor - valueAgainst ;

        // Odds percentage
        if (valueTotal<2) { oddsPercentage = 0 ; }
        else if (valueTotal>9) { oddsPercentage = rollOdds[9] ; }
        else { oddsPercentage = rollOdds[valueTotal] ; }

        // Update odds text & enable PERSUADE button
        $('.persuasionOdds').val(valueFor + " - " + valueAgainst + " = " + valueTotal + " (" + parseInt(10000*oddsPercentage)/100 + "%)") ;
        $("[verb='persuasionPickTarget']").removeClass('disabled');
    }
    // If we lack either a target or a persuader, update Odds to 'N/A' and disable PERSUADE button
    else
    {
        $('.persuasionOdds').val('N/A');
        $("[verb='persuasionPickTarget']").addClass('disabled');
    }
}

/*
 * Enables and disables the "bribe more" button depending on the value of the persuasionAddedBribe select
 */

function persuasionUpdateAddedBribe()
{
    var addedBribe = parseInt($('.persuasionAddedBribe option:selected').val() , 10 ) ;
    if (addedBribe>0)
    {
        $("[verb='persuasionBribeMore']").removeClass('disabled');
    }
    else
    {
        $("[verb='persuasionBribeMore']").addClass('disabled');
    }
}

/*
 * Enables and disables the "counter bribe" button depending on the value of the persuasionCounterBribe select
 */

function persuasionUpdateCounterBribe()
{
    var counterBribe = parseInt($('.persuasionCounterBribeAmount option:selected').val() , 10 ) ;
    if (counterBribe>0)
    {
        $("[verb='persuasionCounterBribe']").removeClass('disabled');
    }
    else
    {
        $("[verb='persuasionCounterBribe']").addClass('disabled');
    }
}
