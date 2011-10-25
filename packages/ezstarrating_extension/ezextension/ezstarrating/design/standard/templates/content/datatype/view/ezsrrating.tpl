{def $rating = $attribute.content
     $can_view_results = fetch( 'user', 'has_access_to', hash( 'module', 'starrating',
                                                               'function', 'view_results' ) )
     $current_rating = $rating.rounded_average|div(5)|mul(100)}

{if $can_view_results|not}
    {set $current_rating = 0}
{/if}

<ul id="ezsr_rating_{$attribute.id}" class="ezsr-star-rating">
   <li id="ezsr_rating_percent_{$attribute.id}" class="ezsr-current-rating" style="width:{$current_rating}%;">{'Currently %current_rating out of 5 Stars.'|i18n('extension/ezstarrating/datatype', '', hash( '%current_rating', concat('<span>', $current_rating|wash, '</span>') ))}</li>
   {for 1 to 5 as $num}
       <li><a href="JavaScript:void(0);" id="ezsr_{$attribute.id}_{$attribute.version}_{$num}" title="{'Rate %rating stars out of 5'|i18n('extension/ezstarrating/datatype', '', hash( '%rating', $num ))}" class="ezsr-stars-{$num}" rel="nofollow" onfocus="this.blur();">{$num}</a></li>
   {/for}
</ul>

{if $can_view_results}
    {'Rating: %current_rating/5'|i18n('extension/ezstarrating/datatype', '', hash( '%current_rating', concat('<span id="ezsr_average_', $attribute.id, '" class="ezsr-average-rating">', $rating.rating_average|wash, '</span>') ))}
    ({'%rating_count votes cast'|i18n('extension/ezstarrating/datatype', '', hash( '%rating_count', concat('<span id="ezsr_total_', $attribute.id, '">', $rating.rating_count|wash, '</span>') ))})
{/if}
    {if $attribute.data_int} {'disabled'|i18n('extension/ezstarrating/datatype')}.{/if}
<p id="ezsr_just_rated_{$attribute.id}" class="ezsr-just-rated hide">{'Thank you for rating!'|i18n('extension/ezstarrating/datatype', 'When rating')}</p>
<p id="ezsr_has_rated_{$attribute.id}" class="ezsr-has-rated hide">{'You have already rated this page, you can only rate it once!'|i18n('extension/ezstarrating/datatype', 'When rating')}</p>
<p id="ezsr_changed_rating_{$attribute.id}" class="ezsr-changed-rating hide">{'Your rating has been changed, thanks for rating!'|i18n('extension/ezstarrating/datatype', 'When rating')}</p>

{run-once}
{ezcss_require( 'star_rating.css' )}
{* Enable rating code if not disabled on attribute and user has access to rate! *}
{if and( $attribute.data_int|not, has_access_to_limitation( 'ezjscore', 'call', hash( 'FunctionList', 'ezstarrating_rate' ) ))}
    {*
       eZStarRating supports both yui3.0 and jQuery as decided by ezjscore.ini[eZJSCore]PreferredLibrary
       For the JavaScript code look in: design/standard/javascript/ezstarrating_*.js

       (This dual approach is not something you need to do in your extensions, but currently a service done on official extensions for now!)
    *}
    {def $preferred_lib = ezini('eZJSCore', 'PreferredLibrary', 'ezjscore.ini')}
    {if array( 'yui3', 'jquery' )|contains( $preferred_lib )|not()}
        {* Prefer jQuery if something else is used globally, since it's smaller then yui3. *}
        {set $preferred_lib = 'jquery'}
    {/if}
    {ezscript_require( array( concat( 'ezjsc::', $preferred_lib ), concat( 'ezjsc::', $preferred_lib, 'io' ), concat( 'ezstarrating_', $preferred_lib, '.js' ) ) )}
{else}
    {if fetch( 'user', 'current_user' ).is_logged_in}
        <p id="ezsr_no_permission_{$attribute.id}" class="ezsr-no-permission">{"You don't have access to rate this page."|i18n( 'extension/ezstarrating/datatype' )}</p>
    {else}
        {if ezmodule( 'user/register' )}
            <p id="ezsr_no_permission_{$attribute.id}" class="ezsr-no-permission">{'%login_link_startLog in%login_link_end or %create_link_startcreate a user account%create_link_end to rate this page.'|i18n( 'extension/ezstarrating/datatype', , hash( '%login_link_start', concat( '<a href="', '/user/login'|ezurl('no'), '">' ), '%login_link_end', '</a>', '%create_link_start', concat( '<a href="', "/user/register"|ezurl('no'), '">' ), '%create_link_end', '</a>' ) )}</p>
        {else}
            <p id="ezsr_no_permission_{$attribute.id}" class="ezsr-no-permission">{'%login_link_startLog in%login_link_end to rate this page.'|i18n( 'extension/ezstarrating/datatype', , hash( '%login_link_start', concat( '<a href="', '/user/login'|ezurl('no'), '">' ), '%login_link_end', '</a>' ) )}</p>
        {/if}
    {/if}
{/if}
{/run-once}
{undef $rating}