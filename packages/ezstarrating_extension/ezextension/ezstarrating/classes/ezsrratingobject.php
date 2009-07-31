<?php
//
// Definition of ezsrRatingObject class
//
// SOFTWARE NAME: eZ Star Rating
// SOFTWARE RELEASE: 2.0
// COPYRIGHT NOTICE: Copyright (C) 2008 Bruce Morrison, 2009 eZ Systems AS
// SOFTWARE LICENSE: GNU General Public License v2.0
// NOTICE: >
//   This program is free software; you can redistribute it and/or
//   modify it under the terms of version 2.0  of the GNU General
//   Public License as published by the Free Software Foundation.
//
//   This program is distributed in the hope that it will be useful,
//   but WITHOUT ANY WARRANTY; without even the implied warranty of
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//   GNU General Public License for more details.
//
//   You should have received a copy of version 2.0 of the GNU General
//   Public License along with this program; if not, write to the Free
//   Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
//   MA 02110-1301, USA.
//
//

class ezsrRatingObject extends eZPersistentObject
{
	 /**
     * Used by {@link ezsrRatingObject::userHasRated()} to cache value.
     * 
     * @var bool $currentUserHasRated
     */
	protected $currentUserHasRated = null;

     /**
     * Construct, use {@link ezsrRatingObject::create()} to create new objects.
     * 
     * @param array $row
     */
    protected function __construct( $row )
    {
        $this->eZPersistentObject( $row );
    }

    static function definition()
    {
        static $def = array( 'fields' => array(
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
                      //'has_rated' => 'userHasRated', (pr user: as in not cache safe)
                  ),
                  'increment_key' => 'id',
                  'class_name' => 'ezsrRatingObject',
                  'name' => 'ezstarrating' );
        return $def;
    }

    /* Function Attributes Methods */
    function getNumber()
    {
        $stats = self::stats( $this->attribute('contentobject_attribute_id') );
        $return = 0;
        if (isset($stats['count']))
            $return = $stats['count'];
        return $return;
    }

    function getAverage()
    {
        $stats = self::stats( $this->attribute('contentobject_attribute_id') );
        $return = 0;
        if (isset($stats['average']))
            $return = $stats['average'];
        return $return;
    }

    function getRoundedAverage()
    {
        $avg = $this->attribute('average');
        $rnd_avg = intval($avg * 2 + 0.5) / 2;
        return $rnd_avg;
    }

    function getSTD()
    {
        $stats = self::stats( $this->attribute('contentobject_attribute_id') );
        $return = 0;
        if (isset($stats['std']))
            $return = $stats['std'];
        return $return;
    }

    /**
     * Figgure out if current user has rated, since eZ Publish changes session id as of 4.1
     * on login / logout, a couple of things needs to be checked.
     * 1. Session variable 'ezsrRatedAttributeIdList' for list of attribute_id's
     * 2a. (annonymus user) check against session key
     * 2b. (logged in user) check against user id
     * 
     * @param int $contentobjecrId
     * @param int $contentobjectAttributeId (optional, check only by object id if set to 0)
     * @return bool
     */
    function userHasRated()
    {
        if ( $this->currentUserHasRated === null )
        {
            $http = eZHTTPTool::instance();
            if ( $http->hasSessionVariable('ezsrRatedAttributeIdList') )
            	$attributeIdList = explode( ',', $http->sessionVariable('ezsrRatedAttributeIdList') );
            else
                $attributeIdList = array();

            $contentobjectAttributeId = $this->attribute('contentobject_attribute_id');
            if ( in_array( $contentobjectAttributeId, $attributeIdList ) )
            {
            	$this->currentUserHasRated = true;
            }
            else
            {
                $sessionKey = $this->attribute('session_key');
                $userId = $this->attribute('user_id');
                if ( $userId == eZUser::anonymousId() )
                {
                    $cond = array( 'user_id' => $userId, // for table index
                                   'session_key' => $sessionKey,
                                   'contentobject_attribute_id' => $contentobjectAttributeId );
                }
                else
                {
                    $cond = array( 'user_id' => $userId,
                                   'contentobject_attribute_id' => $contentobjectAttributeId );
                }
                $this->currentUserHasRated = eZPersistentObject::count( self::definition(), $cond, 'id' ) != 0;
            }
        }    	
        return $this->currentUserHasRated;
    }

    function store( $fieldFilters = null )
    {
        $this->setAttribute( 'created_at', time() );
        if ( $this->attribute( 'user_id' ) == eZUser::currentUserID() )
        {
            // Store attribute id in session to avoid multiple ratings by same user even if he logs out (gets new session key)
        	$http = eZHTTPTool::instance();
            $attributeIdList = $this->attribute( 'contentobject_attribute_id' );
            if ( $http->hasSessionVariable('ezsrRatedAttributeIdList') )
            {
                $attributeIdList = $http->sessionVariable('ezsrRatedAttributeIdList') . ',' . $attributeIdList;
            }
            $http->setSessionVariable('ezsrRatedAttributeIdList', $attributeIdList );
        }
        eZPersistentObject::store( $fieldFilters );
    }

    static function removeAll( $contentobjectAttributeId )
    {
        $cond = array( 'contentobject_attribute_id' => $contentobjectAttributeId );
        eZPersistentObject::removeObject( self::definition(), $cond );
    }

    static function fetch($id)
    {
        $cond = array( 'id' => $id );
        $return = eZPersistentObject::fetchObject( self::definition(), null, $cond );
        return $return;
    }

    /**
     * Create a ezsrRatingObject and store it.
     * 
     * @param int $contentobjectAttributeId
     * @param int $version
     * @param int $rate (between 1 and 5)
     * @param bool $onlyStoreIfUserNotAlreadyRated
     * @return null|ezsrRatingObject
     */
    static function rate( $contentobjectAttributeId, $version, $rate, $onlyStoreIfUserNotAlreadyRated = true )
    {
        $rating = null;
        if ( is_numeric( $contentobjectAttributeId ) &&
             is_numeric( $version ) &&
             is_numeric( $rate) &&
             $rate <= 5 && $rate >= 1 )
        {
            $contentobjectAttribute = eZContentObjectAttribute::fetch( $contentobjectAttributeId, $version );
            if ( $contentobjectAttribute instanceof eZContentObjectAttribute  )
            {
                $contentobjectId = $contentobjectAttribute->attribute('contentobject_id');
                $row = array ('contentobject_attribute_id' => $contentobjectAttributeId,
                              'contentobject_id'           => $contentobjectId,
                              'rating'                     => $rate);

                $rating = self::create( $row );
                if ( !$onlyStoreIfUserNotAlreadyRated || !$rating->userHasRated() )
                {
                    $rating->store();
                    // clear the cache for all nodes associated with this object
                    eZContentCacheManager::clearContentCacheIfNeeded( $contentobjectId, true, false );
                }
            }
        }
        return $rating;
    }

    /**
     * Create a ezsrRatingObject by definition data (but do not store it, thats up to you!)
     * NOTE: you have to provide the following attributes:
     *     contentobject_id
     *     contentobject_attribute_id
     *     rating
     * 
     * @param array $row
     * @return ezsrRatingObject
     */
    static function create( $row = array() )
    {
        if ( !isset( $row['session_key'] ) )
        {
            $http = eZHTTPTool::instance();
            $row['session_key'] = $http->getSessionKey();
        }

        if ( !isset( $row['user_id'] ) )
        {
            $row['user_id'] = eZUser::currentUserID();
        }

        $object = new self( $row );
        return $object;
    }

    static function fetchBySessionKey( $ContentObjectAttributeID )
    {
        $http = eZHTTPTool::instance();
        $session_key = $http->getSessionKey();
        $cond = array('session_key' => $session_key,
                      'contentobject_attribute_id' => $ContentObjectAttributeID);
        $object = self::fetchObjectList( self::definition(), null, $cond );
        return count($object);
    }

    static function stats( $ContentObjectAttributeID )
    {
        static $cachedStats = array( 0 => null );
    	if ( isset( $cachedStats[ $ContentObjectAttributeID ] ) )
        {
            $return = $cachedStats[$ContentObjectAttributeID];
        }
        else
        {
            $custom = array( array( 'operation' => 'count( id )',
                                    'name'      => 'count' ) ,
                             array( 'operation' => 'avg( rating )',
                                    'name'      => 'average' ),
                             array( 'operation' => 'std( rating )',
                                    'name'      => 'std' ));
            $cond = array( 'contentobject_attribute_id' => $ContentObjectAttributeID );
            $return = self::fetchObjectList( self::definition(), array() ,$cond, null, null, false, false, $custom );

            if ( is_array( $return ) )
                $return = $return[0];

            $cachedStats[$ContentObjectAttributeID] = $return;
        }
        return $return;
    }
}

?>
