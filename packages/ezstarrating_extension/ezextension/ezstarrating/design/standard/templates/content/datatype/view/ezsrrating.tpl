{def $rating = $attribute.content}

<ul id="ezsr_rating_{$attribute.id}" class="ezsr-star-rating">
   <li id="ezsr_rating_percent_{$attribute.id}" class="ezsr-current-rating" style="width:{$rating.rounded_average|div(5)|mul(100)}%;">{'Currently %current_rating og 5 Stars.'|i18n('extension/ezstarrating/datatype', '', hash( '%current_rating', concat('<span>', $rating.rounded_average|wash, '</span>') ))}</li>
   {for 1 to 5 as $num}
       <li><a href="JavaScript:void(0);" id="ezsr_{$attribute.id}_{$attribute.version}_{$num}" title="{'Rate %rating stars out of 5'|i18n('extension/ezstarrating/datatype', '', hash( '%rating', $num ))}" class="ezsr-stars-{$num}" rel="nofollow" onfocus="this.blur();">{$num}</a></li>
   {/for}
</ul>

{'Rating: %current_rating/5'|i18n('extension/ezstarrating/datatype', '', hash( '%current_rating', concat('<span id="ezsr_average_', $attribute.id, '" class="ezsr-average-rating">', $rating.rounded_average|wash, '</span>') ))}
({'%rating_count votes casted'|i18n('extension/ezstarrating/datatype', '', hash( '%rating_count', concat('<span id="ezsr_total_', $attribute.id, '">', $rating.number|wash, '</span>') ))}) 
<p id="ezsr_just_rated_{$attribute.id}" class="ezsr-just-rated hide">{'Thank you for rating!'|i18n('extension/ezstarrating/datatype', 'When rating')}</p>
<p id="ezsr_has_rated_{$attribute.id}" class="ezsr-has-rated hide">{'You have already rated this page, you can only rate it once!'|i18n('extension/ezstarrating/datatype', 'When rating')}</p>

{run-once}
{if fetch( 'user', 'has_access_to', hash( 'module', 'ezjscore', 'function', 'call_ezstarrating_rate' ))}
{ezscript('ezjsc::yui3')}
<script type="text/javascript">
{literal}
if ( YUI3_config.modules === undefined ) YUI3_config.modules = {};

YUI3_config.modules['ezsr-star-rating-css'] = {
    type: 'css',
    fullpath: {/literal}{"stylesheets/star_rating.css"|ezdesign()}{literal}
};

YUI( YUI3_config ).use('node', 'event', 'io-ez', 'ezsr-star-rating-css', function( Y )
{
    Y.on( "domready", function( e )
    {
        Y.all('ul.ezsr-star-rating').addClass('ezsr-star-rating-enabled');
        Y.all('ul.ezsr-star-rating li a').on( 'click', _rate );
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
                Y.all('#ezsr_just_rated_' + data.id).removeClass('hide');
                Y.all('#ezsr_rating_percent_' + data.id).setStyle('width', (( data.stats.rounded_average / 5 ) * 100 ) + '%' );
                Y.all('#ezsr_average_' + data.id).setContent( data.stats.rounded_average );
                Y.all('#ezsr_total_' + data.id).setContent( data.stats.number );
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
{/literal}
</script>
{/if}
{/run-once}
