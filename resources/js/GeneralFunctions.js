/* jquery code for jqueryUI to handle :
 * - Sortable lists
 * - Resizable (like the footer)
 * - Draggable cards
 * - Droppable card Slots
 * - Droppable Senator cards
 */
$(document).ready(function(){
    $('.sortable-list').sortable({
        connectWith: '.sortable-list',
        placeholder: 'sortable-list-placeholder'
    });

    /* Draggable with a clone as a helper :
     * - All Senators (class sprite-Senator)
     * - All cards with the draggable class
     */
    $('.sprite-Senator , .sprite-Card , .draggable').each(function() {
        $(this).draggable({
            zIndex: 500,
            scroll:true,
            start: function (event, ui) {
               $(this).data("startingScrollTop",window.pageYOffset);
            },
            drag: function(event,ui){
               var st = parseInt($(this).data("startingScrollTop"));
               ui.position.top -= st;
            },
            cursor: 'move' ,
            helper: 'clone'
        });
    });

    // Popover
    // - Rome Current State
    $('#RomeCurrentState').popover({ 
        container : 'body',
        html : true,
        content: function() {
            return $('#RomeCurrentState_content').html();
        }
    });

    $('.sprite-position-name').popover();

    // jQueryUI resizable
    // - Footer
    $( "#footer" ).resizable({
        handles: 'n' ,
        alsoResizeReverse: "#main-area"
    });

});

// Connecting to the socket.io server :
// - The socket.io server is connected to a URL given as an attribute to the HTML tag 
// - The 'Update' event triggers a reloading of the window if the realms are the same (a gameId or "Lobby")
var socket = io.connect($("html").attr('ws_client'));
socket.on('Update', function(realm){
    if ( realm === $("title").attr('realm') ) {
        window.location.reload(true) ;
    }
});

// any submit of a form with the 'json-submit' property will result is submitting data in json to the current route + '/verb'
// The json data will also include the data of <li> elements from any sortable list (class "sortable-list")
$(function() {
    $('.json-submit').submit(function(e){
        //prevent Default functionality
        e.preventDefault();

        // Add FORM data to a json; variable made of [name] => value
        var data = $(this).serializeArray();
        var json = {};
        $.each(data, function() {
            json[this.name] = this.value || '';
        });
        // All forms should include a user_id, pass it in the json as well
        json['user_id'] = $(this).attr('user_id');

        // Add sortable lists data to the json variable in the format [list name] => json array
        // For each sortable list with an id
        $('.sortable-list').each(function() {
            if ($(this).attr('id')) {
                var listName = $(this).attr('id') ;
                json[listName] = [];
                // Go through each li tag and add it to the json [list name] array
                $(this).find('li').each(function() {
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
            function( data ) {
                window.location.reload(true) ;
            } ,
            "json"
        );
    });

    // An element with the submitWithVerb class sets its parent form's verb to its own verb
    $('.submitWithVerb').click(function(e){
        $(this).closest('form').attr('verb' , $(this).attr('verb')) ;
    });

});

// Add a AlsoResize reverse function
$.ui.plugin.add("resizable", "alsoResizeReverse", {
    start: function() {
        var that = $(this).resizable( "instance" ),
            o = that.options;

        $(o.alsoResizeReverse).each(function() {
            var el = $(this);
            el.data("ui-resizable-alsoresizeReverse", {
                height: parseInt(el.height(), 10), top: parseInt(el.css("top"), 10)
            });
        });
    },

    resize: function(event, ui) {
        var that = $(this).resizable( "instance" ),
            o = that.options,
            os = that.originalSize,
            op = that.originalPosition,
            delta = {
                height: (that.size.height - os.height) || 0,
                top: (that.position.top - op.top) || 0
            };

        $(o.alsoResizeReverse).each(function() {
            var el = $(this), start = $(this).data("ui-resizable-alsoresize-reverse"), style = {},
                css = el.parents(ui.originalElement[0]).length ?
                    [ "height" ] :
                    [ "height", "top" ];

            $.each(css, function(i, prop) {
                var sum = (start[prop] || 0) - (delta[prop] || 0);
                if (sum && sum >= 0) {
                    style[prop] = sum || null;
                }
            });

            el.css(style);
        });
    },

    stop: function() {
        $(this).removeData("resizable-alsoresize-reverse");
    }
});
