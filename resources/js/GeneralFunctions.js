// any submit of a form with the 'json-submit' property will result is submitting data in json to the current route + '/verb'
// The json data will also include the data of <li> elements from any sortable list (class "sortable-list")
$(function()
{
    $('.json-submit').submit(function(e)
    {
        //prevent Default functionality
        e.preventDefault();

        // Add FORM data to a json; variable made of [name] => value
        var data = $(this).serializeArray();
        var json = {};
        $.each(data, function()
        {
            json[this.name] = this.value || '';
        });

        // Add sortable lists data to the json variable in the format [list name] => json array
        // For each sortable list with an id
        $('.sortable-list').each(function()
        {
            if ($(this).attr('id'))
            {
                var listName = $(this).attr('id') ;
                json[listName] = [];
                // Go through each li tag and add it to the json [list name] array
                $(this).find('li').each(function()
                {
                    json[listName].push($(this).text());
                });
            }
        });

        /* Emit the Update event to socket.io
         * This requires a gameId in the "realm" attribute of the title
         */
        socket.emit('Update', $("title").attr('realm'));

        $.post(
            window.location.pathname + '/' + $(this).attr('verb') ,
            json ,
            function( data )
            {
                window.location.reload(true) ;
            } ,
            "json"
        );
    });
});
