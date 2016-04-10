/*
 * When picking the Persuasion target pick, elements with the following classes are available :
 * - persuasionTargetList (select)
 * - persuasionPersuaderList (select)
 * - persuasionBribe (select)
 * - persuasionCard (select)
 */
$(function() {
    persuasionUpdateBribeList() ;
    persuasionUpdateOdds() ;

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
  
});


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
        $("[verb='persuasionPickTarget']").find('button').removeClass('disabled');
    }
    // If we lack either a target or a persuader, update Odds to 'N/A' and disable PERSUADE button
    else
    {
        $('.persuasionOdds').val('N/A');
        $("[verb='persuasionPickTarget']").find('button').addClass('disabled');
    }
}