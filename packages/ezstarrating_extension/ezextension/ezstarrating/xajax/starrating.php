<?php
include_once ('extension/starrating/classes/starrating.php');

function starrating( $contentobject_attribute_id, $version, $rate )
{
  $objResponse = new xajaxResponse();
  if (is_numeric($contentobject_attribute_id) and
      is_numeric($version) and
      is_numeric($rate) )
  {
    // Check to see if this person has voted before
    $votedBefore = starrating::fetchBySessionKey($contentobject_attribute_id);
    if (! $votedBefore)
    {
      
      $rating = starrating::rate($contentobject_attribute_id,$version,$rate);
      if ($rating)
      {
        $percent = $rating->attribute('rounded_average')/5*100;
        $objResponse->assign("total_$contentobject_attribute_id","innerHTML", $rating->attribute('number'));
        $objResponse->assign("average_$contentobject_attribute_id","innerHTML", $rating->attribute('rounded_average'));
        $objResponse->assign("rating_percent_$contentobject_attribute_id","style.width", $percent."%;");
      }
    }
    else
    {
      eZDebug::writeDebug( "User has previously voted", "starrating" );
    }
  }
  $objResponse->assign("has_rated_$contentobject_attribute_id","innerHTML", 'Thank you for your rating!');
  $objResponse->call("starrating_clear_$contentobject_attribute_id");
  for ($num = 1; $num <= 5; $num++)
  {
    $objResponse->remove("sr_".$contentobject_attribute_id."_".$num);
  }
  return $objResponse;
}

?>
