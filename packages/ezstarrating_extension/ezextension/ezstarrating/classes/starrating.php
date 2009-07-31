<?php
include_once( "lib/ezdb/classes/ezdb.php" );
include_once( 'kernel/classes/ezcontentobject.php' );
include_once( 'kernel/classes/ezpersistentobject.php' );

class starrating extends eZPersistentObject
{

  function starrating(&$row)
  {
    $this->eZPersistentObject( $row );
  }

  static function definition()
  {
    return array( 'fields' => array(
                    'id' => array(
                      'name' => 'id',
                      'datatype' => 'integer',
                      'default' => 0,
                      'required' => true ),
                    'created_at' => array(
                      'name' => 'created_at',
                      'datatype' => 'integer',
                      'default' => 0,
                      'required' => true ),
                    'user_id' => array(
                      'name' => 'user_id',
                      'datatype' => 'integer',
                      'default' => 0,
                      'required' => true ),
                    'rating' => array(
                      'name' => 'rating',
                      'datatype' => 'integer',
                      'default' => 0,
                      'required' => true ),
                    'session_key' => array(
                      'name' => 'session_key',
                      'datatype' => 'string',
                      'default' => '',
                      'required' => true ),
                    'contentobject_id' => array(
                      'name' => 'contentobject_id',
                      'datatype' => 'integer',
                      'default' => 0,
                      'required' => true ),
                    'contentobject_attribute_id' => array(
                      'name' => 'contentobject_attribute_id',
                      'datatype' => 'integer',
                      'default' => 0,
                      'required' => true ),
                  ),
                  'keys' => array( 'id' ),
                  'function_attributes' => array(
                    'number' => 'getNumber',
                    'average' => 'getAverage',
                    'rounded_average' => 'getRoundedAverage',
                    'std_deviation' => 'getSTD',
                    'has_rated' => 'getHasRated',
                  ),
                  'increment_key' => 'id',
                  'class_name' => 'starrating',
                  'name' => 'starrating' );
  }

  /* Function Attributes Methods */

  function getNumber()
  {
    $stats = starrating::stats($this->attribute('contentobject_attribute_id'));
    $return = 0;
    if (isset($stats['count']))
      $return = $stats['count'];
    return $return;
  }

  function getAverage()
  {
    $stats = starrating::stats($this->attribute('contentobject_attribute_id'));
    $return = 0;
    if (isset($stats['average']))
      $return = $stats['average'];
    return $return;
  }

  function getRoundedAverage()
  {
    $avg = $this->attribute('average');
    $rnd_avg = intval($avg * 2 + 0.5)/2;
    return $rnd_avg;
  }

  function getSTD()
  {
    $stats = starrating::stats($this->attribute('contentobject_attribute_id'));
    $return = 0;
    if (isset($stats['std']))
      $return = $stats['std'];
    return $return;
  }

  function getHasRated()
  {
    $return = starrating::fetchBySessionKey($this->attribute('contentobject_attribute_id'));
//    $return = false;
    return $return;
  }

  function store( $fieldFilters = null )
  {
    $this->setAttribute('created_at', time());
    eZPersistentObject::store( $fieldFilters );
  }

  static function &removeAll($id)
  {
    $cond=array("contentobject_attribute_id" => $id);
    eZPersistentObject::removeObject(starrating::definition(),$cond);
  }

  static function &fetch($id)
  {
    $cond=array("id" => $id);
    $return = eZPersistentObject::fetchObject(starrating::definition(),null,$cond);
    return $return;
  }

 static function rate($contentobject_attribute_id,$version,$rate)
 {
    $rating = false;
    if (is_numeric( $contentobject_attribute_id ) and
        is_numeric( $version ) and
        is_numeric( $rate) and 
        $rate <= 5 and $rate >= 1 )
    {
      $contentobject_attribute = eZContentObjectAttribute::fetch($contentobject_attribute_id,$version);
      if ( $contentobject_attribute )
      {
        $contentobject_id = $contentobject_attribute->attribute("contentobject_id");
        $row = array ('contentobject_attribute_id' => $contentobject_attribute_id,
                      'version'                    => $version,
                      'contentobject_id'           => $contentobject_id,
                      'rating'                     => $rate);

        $rating = starrating::create($row);
        $rating->store();

        // clear the cache for all nodes associated with this object
        eZContentCacheManager::clearContentCacheIfNeeded( $contentobject_id, true, false );
      }
    }
    return $rating;
 }
 

  static function &create($row=array())
  {
    include_once ("lib/ezutils/classes/ezhttptool.php");
    $http = eZHTTPTool::instance();
    $session_key = $http->getSessionKey();

    $user = eZUser::currentUser();
    $userID = $user->id();

    $row['session_key'] =  $session_key;
    $row['user_id']     =  $userID;

    $object = new starrating($row);
    return $object;
  }

  static function fetchBySessionKey($ContentObjectAttributeID)
  {
    include_once ("lib/ezutils/classes/ezhttptool.php");
    $http = eZHTTPTool::instance();
    $session_key = $http->getSessionKey();
    $cond=array('session_key' => $session_key,
                'contentobject_attribute_id' => $ContentObjectAttributeID);
    $object = starrating::fetchObjectList(starrating::definition(),null,$cond);
    return count($object);
  }

  static function stats($ContentObjectAttributeID)
  {
    if (isset($GLOBALS['ratings']['stats'][$ContentObjectAttributeID]))
    {
      $return =& $GLOBALS['ratings']['stats'][$ContentObjectAttributeID];
    }
    else
    {
      $custom = array( array( 'operation' => 'count( id )',
                              'name'      => 'count' ) ,
                       array( 'operation' => 'avg( rating )',
                              'name'      => 'average' ),
                       array( 'operation' => 'std( rating )',
                              'name'      => 'std' ));
      $cond=array( 'contentobject_attribute_id' => $ContentObjectAttributeID );
      $return = starrating::fetchObjectList(starrating::definition(), array() ,$cond, null, null, false, false, $custom );
      if (is_array($return))
        $return = $return[0];
      $GLOBALS['ratings']['stats'][$ContentObjectAttributeID] =& $return;
    }
    return $return;
  }


}

?>
