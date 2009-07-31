{def $rating=$attribute.content
     $rating_url=concat('/starrating/collect/',$attribute.id,'/',$attribute.version,'/')}

<ul class="star-rating">
    <li id="rating_percent_{$attribute.id}" class="current-rating" style="width:{$rating.rounded_average|div(5)|mul(100)}%;">Currently {$rating.rounded_average|wash}/5 Stars.</li>
    {if $rating.has_rated|not}
        {for 1 to 5 as $num}
            <li><a href={concat($rating_url,$num)|ezurl} id="sr_{$attribute.id}_{$num}" title="Rate {$num} stars out of 5" class="stars-{$num}" rel="nofollow" onfocus="this.blur();">{$num}</a></li>

        {/for}
    {/if}
</ul>

Rating: <strong><span id="average_{$attribute.id}">{$rating.rounded_average|wash}</span></strong>/5 (<span id="total_{$attribute.id}">{$rating.number|wash}</span> votes cast)
<p id="has_rated_{$attribute.id}">{if $rating.has_rated}Thank you for your rating!{/if}</p>
{if $rating.has_rated|not}
    {def $hasXajaxAccess=fetch('user','has_access_to',hash('module','xajax','function','all'))}
    {if $hasXajaxAccess}
        {run-once}{xajax_javascript()}{/run-once}
        <script type="text/javascript">

        function starrating_init_{$attribute.id}() {ldelim}
        {for 1 to 5 as $num}
           YAHOO.util.Event.addListener("sr_{$attribute.id}_{$num}", "click", function(e){ldelim}
             YAHOO.util.Event.preventDefault(e);
             xajax_starrating({$attribute.id}, {$attribute.version}, {$num});
        {rdelim});

        {/for}
         {rdelim}

         YAHOO.util.Event.onDOMReady(starrating_init_{$attribute.id});

         function starrating_clear_{$attribute.id}() {ldelim}
        {for 1 to 5 as $num}
           YAHOO.util.Event.removeListener("sr_{$attribute.id}_{$num}", "click");

        {/for}
         {rdelim}

        </script>
    {/if}
{/if}
