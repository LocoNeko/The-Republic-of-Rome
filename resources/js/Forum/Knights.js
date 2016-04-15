function displayModalSlider(modalID , label , minValue , maxValue)
{
    $('#'+modalID+'_label').text(label);
    $('#'+modalID+'_value').val(minValue);
    $('#'+modalID+'_slider').slider({
        min: minValue,
        max: maxValue ,
        value: minValue,
        slide: function( event, ui ) {
            $('#'+modalID+'_value').val( ui.value );
        }
    });
    $('#'+modalID).modal('show') ;
}

/*
<div style="display: block;" class="modal fade in" id="redistributionModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="false">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                <h4 class="modal-title" id="myModalLabel">Transfer from your party treasury to Party II</h4>
            </div>
            <div class="modal-body">
                <input id="redistributionAmount" style="border:0; font-weight:bold; font-size: 2em; background: white;" type="text">
                <div class="ui-slider ui-slider-horizontal ui-widget ui-widget-content ui-corner-all" id="redistributionSlider"><span style="left: 0%;" class="ui-slider-handle ui-state-default ui-corner-all" tabindex="0"></span></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="submitRedistribution" from_senator="" to_senator="" from_party="1" to_party="2">Transfer</button>
            </div>
        </div>
    </div>
</div>
*/