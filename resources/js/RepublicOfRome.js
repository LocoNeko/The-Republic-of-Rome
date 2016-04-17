function getReady(phase , subPhase)
{
    /**
     * Global function, that should always be loaded and ready
     */

    /**
     * SUBMIT SLIDER
     * - Get the stored JSON data from the slider's modal
     * - Adds to that data :
     * > The value of the slider
     * > The verb of the modal's submit button
     * - TO DO : submit that data
     */
    $('.slider-submit').click(function()
    {
        var modalID = $(this).closest('.modal').attr('id') ;
        $('#'+modalID).modal('hide') ;
        var json = JSON.parse($('#'+modalID+'_storedData').val()) ;
        json['value'] = $('#'+modalID+'_slider').slider('option' , 'value') ;
        json['verb'] = $(this).attr('verb') ;
        // TO DO : submit the data
        alert (JSON.stringify(json));
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
    if (phase=='Forum' && subPhase=='Persuasion')
    {
        prepareForumPersuasion() ;
    }
    if (phase=='Forum' && subPhase=='knights')
    {
        prepareForumKnights() ;
    }
}

/*
 * ========================= GENERAL - SLIDERS =========================
 */

/**
 *
 * @param modalID ID of the Modal to create
 * @param storedData Json data passed to the modal when it was opened
 * @param label Title of the modal
 * @param minValue Minimum value
 * @param maxValue Maximum value
 * @param postText Text to append when displaying
 */
function displayModalSlider(modalID , storedData , label , minValue , maxValue , postText)
{
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
        $("[verb='persuasionPickTarget']").find("[verb='persuasionPickTarget']").removeClass('disabled');
    }
    // If we lack either a target or a persuader, update Odds to 'N/A' and disable PERSUADE button
    else
    {
        $('.persuasionOdds').val('N/A');
        $("[verb='persuasionPickTarget']").find("[verb='persuasionPickTarget']").addClass('disabled');
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
 * ========================= FORUM - KNIGHTS =========================
 */

function prepareForumKnights()
{
    $('.forumKnightsAttract').click(function(e)
    {
        // TO DO : Find a way to get the data on the card as a Json, to pass it on
        // As a test, it's simply {'senatorID' : senatorID} here
        var senatorID = $(this).closest('.sprite-Senator').attr('senatorID') ;
        var name = $(this).closest('.sprite-Senator').attr('name') ;
        var treasury = $(this).closest('.sprite-Senator').attr('treasury') ;
        //displayModalSlider(senatorID+'KnightsAttractModal' , 'Attracting a knight for '+name , 0 , treasury) ;
        displayModalSlider('KnightsAttractModal' , JSON.stringify({"senatorID" : senatorID}) , name + ' spends talents' , 0 , treasury , ' T');
    });

    $('.forumKnightsPressure').click(function(e)
    {
        // TO DO : Find a way to get the data on the card as a Json, to pass it on
        // As a test, it's simply {'senatorID' : senatorID} here
        var senatorID = $(this).closest('.sprite-Senator').attr('senatorID') ;
        var name = $(this).closest('.sprite-Senator').attr('name') ;
        var knights = $(this).closest('.sprite-Senator').attr('knights') ;
        //displayModalSlider(senatorID+'KnightsAttractModal' , 'Attracting a knight for '+name , 0 , treasury) ;
        displayModalSlider('KnightsPressureModal' , JSON.stringify({"senatorID" : senatorID}) ,  'Number of knights pressured by '+name , 0 , knights , '');
    });
}
