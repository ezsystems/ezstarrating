<?php
include_once ('kernel/common/template.php');
include_once ('kernel/common/eztemplatedesignresource.php');
include_once ('extension/starrating/classes/starrating.php');

$Module = $Params['Module'];

$http = eZHTTPTool::instance();
$ini = eZINI::instance();

$contentobject_attribute_id = false;
$version = false;
$rating = false;

if (isset($Params['ContentObjectAttributeID']) and is_numeric($Params['ContentObjectAttributeID']))
  $contentobject_attribute_id = $Params['ContentObjectAttributeID'];

if (isset($Params['version']) and is_numeric($Params['version']))
  $version = $Params['version'];

if (isset($Params['rating']) and is_numeric($Params['rating']))
  $rate = $Params['rating'];

if ($contentobject_attribute_id and
    $version and
    $rate)
{
  // Check to see if this person has voted before
  $votedBefore = starrating::fetchBySessionKey($contentobject_attribute_id);
  if (! $votedBefore)
  {
      $rating = starrating::rate($contentobject_attribute_id,$version,$rate);
  }
  else
  {
    eZDebug::writeDebug( "User has previously voted", "starrating" );
  }
}

// redircet back to where we just were 
$RedirectURI = '/';
if ( $http->hasSessionVariable( "LastAccessesURI" ) )
  $RedirectURI = $http->sessionVariable( "LastAccessesURI" );
return $Module->redirectTo($RedirectURI);
?>
