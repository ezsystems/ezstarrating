{def $rating = $attribute.content}

<ul id="ezsr_rating_{$attribute.id}" class="ezsr-star-rating" style="display: none;" >
   <li id="ezsr_rating_percent_{$attribute.id}" class="ezsr-current-rating" style="width:{$rating.rounded_average|div(5)|mul(100)}%;"><span>Currently {$rating.rounded_average|wash}/5 Stars.</span></li>
   {for 1 to 5 as $num}
       <li><a href="JavaScript:void(0);" id="ezsr_{$attribute.id}_{$attribute.version}_{$num}" title="Rate {$num} stars out of 5" class="ezsr-stars-{$num}" rel="nofollow" onfocus="this.blur();">{$num}</a></li>
   {/for}
</ul>

Rating: <strong><span id="average_{$attribute.id}">{$rating.rounded_average|wash}</span></strong>/5 (<span id="total_{$attribute.id}">{$rating.number|wash}</span> votes cast)
<p id="ezsr_just_rated_{$attribute.id}" class="ezsr_just_rated" style="display: none;">Thank you for your rating!</p>
<p id="ezsr_has_rated_{$attribute.id}" class="ezsr_has_rated" style="display: none;">You have already rated this page, you can only it rate once!</p>

{run-once}
{if fetch( 'user', 'has_access_to', hash( 'module', 'ezjscore', 'function', 'call_ezstarrating_rate' ))}
{ezscript('ezjsc::yui3')}
<script type="text/javascript">
{literal}
YUI( YUI3_config ).use('node', 'event', 'io-ez', function( Y )
{
    Y.on( "domready", function( e )
    {
    	Y.all('ul.ezsr-star-rating').setStyle('display', '');
    	Y.all('ul.ezsr-star-rating li a').on( 'click', _rate );
    });

    function _rate( e )
    {
    	e.preventDefault();
        var args = e.currentTarget.getAttribute('id').split('_');
        Y.all('#ezsr_rating_' + args[1]).setStyle('display', 'none');
        Y.io.ez( 'ezstarrating::rate::' + args[1] + '::' + args[2] + '::' + args[3], { on : { success: _callBack } } );
    }

    function _callBack( id, o )
    {

        if ( o.responseJSON && o.responseJSON.content !== '' )
        {
            var data = o.responseJSON.content;
            if ( data.rated  )
            	Y.all('#ezsr_just_rated_' + data.id).setStyle('display', '');
            else if ( data.already_rated  )
                Y.all('#ezsr_has_rated_' + data.id).setStyle('display', '');
        }
        else
        {
            // TODO: this shouldn't happen as we have alrteady checked access..
            alert( o.responseJSON.error_text );
        }
    }
});
{/literal}
</script>
{/if}
{/run-once}

