{literal}
(function( $ )
{
    $(document).ready( function()
    {
        $('ul.ezsr-star-rating').each( function(){
            var node = $( this );
            if ( !node.hasClass('ezsr-star-rating-disabled') )
                   node.addClass('ezsr-star-rating-enabled');
        });
        $('ul.ezsr-star-rating-enabled li a').click( _rate );
    });

    function _rate( e )
    {
        e.preventDefault();
        var args = $(this).attr('id').split('_');
        $('#ezsr_rating_' + args[1]).removeClass('ezsr-star-rating-enabled');
        $('li a', '#ezsr_rating_' + args[1]).unbind( 'click' );
        jQuery.ez( 'ezstarrating::rate::' + args[1] + '::' + args[2] + '::' + args[3], _callBack );
        return false;
    }

    function _callBack( data )
    {
        if ( data && data.content !== '' )
        {
            if ( data.content.rated )
            {
                if ( data.content.already_rated )
                    $('#ezsr_changed_rating_' + data.content.id).removeClass('hide');
                else
                    $('#ezsr_just_rated_' + data.content.id).removeClass('hide');
                $('#ezsr_rating_percent_' + data.content.id).css('width', (( data.content.stats.rounded_average / 5 ) * 100 ) + '%' );
                $('#ezsr_average_' + data.content.id).text( data.content.stats.rating_average );
                $('#ezsr_total_' + data.content.id).text( data.content.stats.rating_count );
            }
            else if ( data.content.already_rated )
                $('#ezsr_has_rated_' + data.content.id).removeClass('hide');
            //else alert('Invalid input variables, could not rate!');
        }
        else
        {
            // This shouldn't happen as we have already checked access in the template..
            // Unless this is inside a aggressive cache-block of course.
            alert( data.content.error_text );
        }
    }
})(jQuery);
{/literal}