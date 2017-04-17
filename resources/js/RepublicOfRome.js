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
     * SUBMIT WITH VERB - The main submit function
     * -
     */
    $('.submitWithVerb').click(function(e)
    {
        // Get the current json data from the data-json attribute in the body
        var json = JSON.parse($(document.body).attr('data-json')) ;
        var card = $(this).closest('.sprite') ;
        /*
         * If this is on a card, add the card's data-json to this one
         * Otherwise, collect all values with a global-postable class
         */
        if (card.length > 0)
        {
            $.extend(json , JSON.parse(card.attr('data-json'))) ;
        }
        else
        {
            // Collect all global-postable, put them in the json
            $('.global-postable').each(function(i, obj) {
                json[obj.name] = obj.value ;
            });
        }

        // Get the verb from the button, put it in the json as well
        json['verb'] = $(this).attr('verb') ;

        // Store the json data in the 'data-json' attribute of the body
        $(document.body).attr('data-json' , JSON.stringify(json)) ;

        /*
         *  Finally, check if the button's json-data as an action (slider, fixedAmount...)
         *  - Sliders bypass the normal submit
         *  - fixedAmount add the amount to the json and submit it
         */

        var actionData = $(this).attr('data-json');
        if (typeof actionData !== typeof undefined && actionData !== false)
        {
            handleAction(json , actionData) ;
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

    /*
     * DRAGGABLE
     * - Applies to all elements with the .draggable class
     * - Draggable with a clone as a helper
     */
    $('.draggable').each(function() {
        $(this).draggable({
            zIndex: 999,
            scroll: true,
            start: function (event, ui) {
               $(this).data("startingScrollTop",window.pageYOffset);
            },
            drag: function(event,ui){
               var st = parseInt($(this).data("startingScrollTop"));
               ui.position.top -= st;
            },
            cursor: 'move' ,
            helper: 'clone' ,
            refreshPositions: true
        });
    });

    /**
     * DROPPABLE
     * - Applies to all elements with the .droppable class
     * - If the draggable has some data-json, it's put in a 'from' object in the json
     * - The verb always comes from the draggable
     * - The data-json of the droppable is added in a 'to' object in the json
     * - Finally : submit
     */
    $('.droppable').each( function() {
        $(this).droppable( {
            drop: function ( event, ui ) {
                // The json from the body
                var json = JSON.parse($(document.body).attr('data-json')) ;

                // json from the draggable ("from"), if any
                var dataJsonFrom = ui.draggable.attr('data-json');
                if (typeof dataJsonFrom !== typeof undefined && dataJsonFrom !== false && dataJsonFrom.length>0)
                {
                    json.from = JSON.parse(dataJsonFrom);
                    json['verb'] = json.from.verb ;
                }

                // json from the droppable ("to"), if any
                var dataJsonTo = $(event.target).attr('data-json')
                if (typeof dataJsonTo !== typeof undefined && dataJsonTo !== false && dataJsonTo.length>0)
                {
                    json.to = JSON.parse(dataJsonTo) ;
                }

                // Is there an action (slider, fixedAmount...) on the draggable ?
                if (typeof dataJsonFrom !== typeof undefined && dataJsonFrom !== false && dataJsonFrom.length>0 && json.from.action)
                {
                    // If there is an action, store the current json (with 'to' and 'from' in the data-json attribute and execute the action)
                    $(document.body).attr('data-json' , JSON.stringify(json)) ;
                    handleAction(json , JSON.stringify(json.from)) ;
                }
                else
                {
                    submitJSON(json) ;
                }
            }
        });
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
     * Popovers for Statesman information when hovering on a Senator's name
     */
    $('.sprite-position-name').popover();

    /**
     * Phase-specific functions
     */
    if (phase==='Forum' && subPhase==='Persuasion')
    {
        prepareForumPersuasion() ;
    }
    else if (phase==='Senate' && subPhase==='Governors')
    {
        prepareDynamicSection() ;
    }
    else if (phase==='Senate' && subPhase==='OtherBusiness')
    {
        prepareDynamicSection() ;
        prepareSenateOtherBusiness() ;
    }
}

/*
 * ========================= GLOBAL FUNCTIONS =========================
 */

/**
 * If there was some actionData passed (slider, fixedAmount...) handle it :
 * - slider : display the corresponding modal slider
 * - fixedAmount : add it to the json and submit
 * - Otherwise : ERROR
 * @param {json} json
 * @param {json} actionData
 * @returns {mixed}
 */
function handleAction(json , actionData)
{
    var jsonActionData = JSON.parse(actionData) ;
    if (jsonActionData.action[0]==="slider")
        {
            displayModalSlider(jsonActionData.action[1] , '' , jsonActionData.action[2] , jsonActionData.action[3] , jsonActionData.action[4] , jsonActionData.action[5]) ;
        }
        else if (jsonActionData.action[0]==="fixedAmount")
        {
            json["amount"] = jsonActionData.action[1] ;
            submitJSON(json) ;
        }
        else
        {
            alert("ERROR - Wrong action (should be 'slider' , 'fixedAmount' or 'noSubmit')");
        }
}

/**
 * Emits the socket.io notification and posts the data using the <b>Verb</b> passed within the data
 * @param {json} json The data being submitted
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

/*
 * ========================= SENATE =========================
 */

function prepareSenateOtherBusiness()
{
    // TO DO : The Twig template for otherBusiness must have a wrapper <div> of class .otherBusinessWrapperwith all showable/hideable sections.
    // The wrapper's class is otherBusinessWrapper

    // Remove global-postable class from all children within the wrapper
    $('.otherBusinessWrapper').find('.global-postable').removeClass('global-postable') ;

    // Hide all divs within the wrapper
    $('.otherBusinessWrapper').children().hide() ;

    // Depending on the item selected (type of otherBusiness), display the relevant section of the twig template
    // Populate said template inputs (drop-downs, radio buttons, etc) with data gathered from the json-data of each cards
    $('.otherBusinessList').on('change', function()
    {
        var $selectedValue = $(this).val();
        //    $('.otherBusinessWrapper').children().each(){ // OR SIMPLY :
        $('.otherBusinessSection').each(function(i){
            // If this otherBusinessSection has an id equal to the drop-down's select value, show it, otherwise hide it
            if ($selectedValue == $(this).attr('id'))
            {
                $(this).show();
                senateOtherBusinessPopulateSection($selectedValue);
            }
            else
            {
                $(this).hide();
            }
        });
    });
}

function prepareDynamicSection()
{
    $('.dynamicSectionAddButton').click(function () {
        var $boxToClone = $('.dynamicSection:first', '.dynamicSectionsWrapper');
        var $clonedBox = $boxToClone.clone();
        $clonedBox.find('.dynamicSectionRemoveButton').removeClass('disabled');
        $('.dynamicSectionLast').before($clonedBox);
        //alert ('There are now '+$('.dynamicSectionsWrapper').children().length+' boxes');
    });

    $('.dynamicSectionsWrapper').on('click', '.dynamicSectionRemoveButton', function (e) {
        if ($('.dynamicSection', '.dynamicSectionsWrapper').length > 1)
        {
            $(this).closest('.dynamicSection').fadeOut();
        }
    });
}

// This function goes through all data-json of all cards and populates otherBusinessSection accordingly
// The relevant otherBusinessSection to populate is determined by $otherBusinessType
function senateOtherBusinessPopulateSection($otherBusinessType)
{
    var jsonOtherBusiness = JSON.parse($('.otherBusinessWrapper').attr('data-json')) ;

    $('#otherBusinessSenatorSelect' + $otherBusinessType).find('option').remove() ;
    // Go through all Senators
    $('.sprite-Senator').each(function(i) {
        // Get the JSON data from the Senator Card and retrieve what is necessary : name, senatorID, list of otherBusiness
        var $json = $(this).data('json') ;
        var $senatorName = $json.name ;
        var $senatorID = $json.senatorID ;
        var $otherBusinessList = $json.otherBusiness ;
        // If the $otherBusinessType is in this Senator's $otherBusinessList, add his {value,text} to the #otherBusinessSelect{$otherBusinessType}
        if ($.inArray($otherBusinessType , $otherBusinessList) > -1) 
        {
            if ($otherBusinessType=='commander')
            {
                $senatorName+=' ('+$json.office+')';
            }
            // IDs in OtherBusiness_Proposal.twig have the format : #otherBusinessSenatorSelect{$otherBusinessType}
            $('#otherBusinessSenatorSelect' + $otherBusinessType).append($("<option></option>").attr("value",$senatorID).text($senatorName));
        }
    });

    $('#otherBusinessCardSelect' + $otherBusinessType).find('option').remove() ;
    // For concession and commander proposals, we need the cards as well, not just senators
    if ($otherBusinessType=='concession' || $otherBusinessType=='commander')
    {
        // Go through all Cards
        $('.sprite-Card').each(function(i) {
            // Get the JSON data from the Card and retrieve what is necessary : name, cardID, list of otherBusiness
            var $json = $(this).data('json') ;
            var $cardName = $json.name ;
            var $card_id = $json.card_id ;
            var $otherBusinessList = $json.otherBusiness ;
            // If the $otherBusinessType is in this Card's $otherBusinessList, add his {value,text} to the #otherBusinessSelect{$otherBusinessType}
            if ($.inArray($otherBusinessType , $otherBusinessList) > -1) 
            {
                // IDs in OtherBusiness_Proposal.twig have the format : #otherBusinessCardSelect{$otherBusinessType}
                $('#otherBusinessCardSelect' + $otherBusinessType).append($("<option></option>").attr("value",$card_id).text($cardName));
            }
        });
    
    }
    
    // Finally, we will need extra info for LandBills, Fleets, and Legions
    if ($otherBusinessType=='landBill')
    {
        var possibleLandBills  = jsonOtherBusiness.landBill ;
        for(var item in possibleLandBills)
        {
            $('#otherBusinessLandBillSelect')
                .append($("<option></option>")
                .attr("level",possibleLandBills[item].level)
                .attr("sign",possibleLandBills[item].sign)
                .text(possibleLandBills[item].description));
        }
    }
    
    if ($otherBusinessType=='recruit')
    {
        var fleetsData  = jsonOtherBusiness.fleets ;
        $.each( fleetsData, function( key, value ) {
            alert( "Fleet data - " + key + ": " + value );
        });
    }
    
    if ($otherBusinessType=='commander' || $otherBusinessType=='garrison')
    {
        // Fleets : drop down
        var fleetsData  = jsonOtherBusiness.fleets ;
        $.each( fleetsData, function( key, value ) {
            // Fleets in Rome can be sent. Create a drop down list with the following options : 1, 2, 3, ... to number of fleets
            if (key=='inRome')
            {
                for (i = 0; i < value ; i++) 
                {
                    $('#otherBusinessFleetsSelect').append( $("<option></option>").text(i) );
                }
            }
        });

        // Legions : regulars (drop down) and veterans (check boxes)
        var legionsData  = jsonOtherBusiness.legions ;
        $.each( legionsData, function( key, value ) {
            // Regulars in Rome can be sent. Create a drop down list with the following options : 1, 2, 3, ... to number of regulars
            if (key=='regularsInRome')
            {
                for (i = 0; i < value ; i++) 
                {
                    $('#otherBusinessRegularsSelect').append( $("<option></option>").text(i) );
                }
            }
            // Appending checkboxes for sending specific Veteran legions
            else if (key=='veterans')
            {
                $.each( value, function( legionID, legionData) {
                    // Only Veterans currently in Rome can be sent
                    if (legionData['otherLocation'] == 'Rome')
                    {
                        // TO DO : The title will not be helpful. It should say "Loyal to X"
                        $('<label class="checkbox-inline" title="'+legionData['loyalTo']+'"><input type="checkbox" id="'+legionID+'">'+legionData['name']+'</label>').appendTo('#otherBusinessVeteransCheckboxes') ;
                    }
                });
            }
        });
    }
}
