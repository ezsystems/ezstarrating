/**
 * eZ Star Rating : Rating extension for eZ Publish 4.x
 * Created on     : <02-Nov-2009 00:00:00 ar>
 * 
 * This piece of code depends on YUI 3.0 and eZJSCore ( Y.io.ez() plugin ).
 *
 * @copyright Copyright (c) 1999-2014 eZ Systems AS
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2.0
 * @package eZ Starating extension for eZ Publish
 *
 */
YUI( YUI3_config ).use('node', 'event', 'io-ez', function( Y )
{
    Y.on( "domready", function( e )
    {
        Y.all('ul.ezsr-star-rating').each( function( node ){
            if ( !node.hasClass('ezsr-star-rating-disabled') )
                   node.addClass('ezsr-star-rating-enabled');
        } );
        Y.all('ul.ezsr-star-rating-enabled li a').on( 'click', _rate );
    });

    function _rate( e )
    {
        e.preventDefault();
        var args = e.currentTarget.getAttribute('id').split('_');
        Y.all('#ezsr_rating_' + args[1]).removeClass('ezsr-star-rating-enabled');
        Y.all('#ezsr_rating_' + args[1] + ' li a').detach( 'click', _rate );
        Y.io.ez( 'ezstarrating::rate::' + args[1] + '::' + args[2] + '::' + args[3], { on : { success: _callBack } } );
    }

    function _callBack( id, o )
    {
        if ( o.responseJSON && o.responseJSON.content !== '' )
        {
            var data = o.responseJSON.content;
            if ( data.rated  )
            {
                if ( data.already_rated )
                    Y.all('#ezsr_changed_rating_' + data.id).removeClass('hide');
                else
                    Y.all('#ezsr_just_rated_' + data.id).removeClass('hide');
                Y.all('#ezsr_rating_percent_' + data.id).setStyle('width', (( data.stats.rounded_average / 5 ) * 100 ) + '%' );
                Y.all('#ezsr_average_' + data.id).setContent( data.stats.rating_average );
                Y.all('#ezsr_total_' + data.id).setContent( data.stats.rating_count );
            }
            else if ( data.already_rated  )
                Y.all('#ezsr_has_rated_' + data.id).removeClass('hide');
            //else alert('Invalid input variables, could not rate!');
        }
        else
        {
            // This shouldn't happen as we have already checked access in the template..
            // Unless this is inside a aggressive cache-block of course.
            alert( o.responseJSON.error_text );
        }
    }
});