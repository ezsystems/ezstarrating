<?php
//
// Definition of ezsrRatingObject class
//
// SOFTWARE NAME: eZ Star Rating
// SOFTWARE RELEASE: 2.x
// COPYRIGHT NOTICE: Copyright (C) 2008 Bruce Morrison, 2009-2014 eZ Systems AS
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

class ezsrRatingDataObject extends eZPersistentObject
{
     /**
     * Used by {@link ezsrRatingDataObject::userHasRated()} to cache value.
     *
     * @var bool $currentUserHasRated
     */
    protected $currentUserHasRated = null;

     /**
     * Construct, use {@link ezsrRatingDataObject::create()} to create new objects.
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
                      'required' => true,
                      'foreign_class' => 'eZUser',
                      'foreign_attribute' => 'contentobject_id',
                      'multiplicity' => '0..1' ),
                    'session_key' => array(
                      'name' => 'session_key',
                      'datatype' => 'string',
                      'default' => '',
                      'required' => true ),
                    'rating' => array(
                      'name' => 'rating',
                      'datatype' => 'integer',
                      'default' => 0,
                      'required' => true ),
                    'contentobject_id' => array(
                      'name' => 'contentobject_id',
                      'datatype' => 'integer',
                      'default' => 0,
                      'required' => true,
                      'foreign_class' => 'eZContentObject',
                      'foreign_attribute' => 'id',
                      'multiplicity' => '1..*' ),
                    'contentobject_attribute_id' => array(
                      'name' => 'contentobject_attribute_id',
                      'datatype' => 'integer',
                      'default' => 0,
                      'required' => true,
                      'foreign_class' => 'eZContentObjectAttribute',
                      'foreign_attribute' => 'id',
                      'multiplicity' => '1..*' ),
                  ),
                  'keys' => array( 'id' ),
                  'function_attributes' => array(
                      'average_rating' => 'getAverageRating'
                  ),
                  'increment_key' => 'id',
                  'class_name' => 'ezsrRatingDataObject',
                  'name' => 'ezstarrating_data' );
        return $def;
    }

    /**
     * Fetch Average Rating
     * Will create a unstored one if none could be fetched ( it will have rating_avrage and rating_count of 0 )
     *
     * @return ezsrRatingObject
     */
    function getAverageRating()
    {
        $avgRating = ezsrRatingObject::fetchByObjectId( $this->attribute('contentobject_id'), $this->attribute('contentobject_attribute_id') );
        if ( !$avgRating instanceof ezsrRatingObject )
        {
            $avgRating = ezsrRatingObject::create( array('contentobject_id' => $this->attribute('contentobject_id'),
                                                         'contentobject_attribute_id' => $this->attribute('contentobject_attribute_id') ) );
        }
        return $avgRating;
    }

    /**
     * Figgure out if current user has rated, since eZ Publish changes session id as of 4.1
     * on login / logout, a couple of things needs to be checked.
     * 1. Session variable 'ezsrRatedAttributeIdList' for list of attribute_id's
     * 2a. (annonymus user) check against session key
     * 2b. (logged in user) check against user id
     *
     * @param bool $returnRatedObject Return object if user has rated and [eZStarRating]AllowChangeRating=enabled
     * @return bool|ezsrRatingDataObject
     */
    function userHasRated( $returnRatedObject = false )
    {
        if ( $this->currentUserHasRated === null )
        {
            $ini = eZINI::instance();
            $useUserSession =  $ini->variable( 'eZStarRating', 'UseUserSession' ) === 'enabled';

            $http = eZHTTPTool::instance();
            if ( $http->hasSessionVariable( 'ezsrRatedAttributeIdList', $useUserSession ) )
                $attributeIdList = explode( ',', $http->sessionVariable('ezsrRatedAttributeIdList') );
            else
                $attributeIdList = array();

            $contentobjectAttributeId = $this->attribute('contentobject_attribute_id');
            if ( in_array( $contentobjectAttributeId, $attributeIdList ) && $useUserSession )
            {
                $this->currentUserHasRated = true;
            }

            $returnRatedObject = $returnRatedObject && $ini->variable( 'eZStarRating', 'AllowChangeRating' ) === 'enabled';
            if ( $this->currentUserHasRated === null || $returnRatedObject )
            {
                $sessionKey = $this->attribute('session_key');
                $userId = $this->attribute('user_id');
                if ( $userId == eZUser::anonymousId() )
                {
                    $cond = array( 'user_id' => $userId, // for table index
                                   'session_key' => $sessionKey,
                                   'contentobject_id' => $this->attribute('contentobject_id'), // for table index
                                   'contentobject_attribute_id' => $contentobjectAttributeId );
                }
                else
                {
                    $cond = array( 'user_id' => $userId,
                                   'contentobject_id' => $this->attribute('contentobject_id'), // for table index
                                   'contentobject_attribute_id' => $contentobjectAttributeId );
                }

                if ( $returnRatedObject )
                {
                    $this->currentUserHasRated = eZPersistentObject::fetchObject( self::definition(), null, $cond );
                    if ( $this->currentUserHasRated === null)
                        $this->currentUserHasRated = false;
                }
                else
                {
                    $this->currentUserHasRated = eZPersistentObject::count( self::definition(), $cond, 'id' ) != 0;
                }
            }
        }
        return $this->currentUserHasRated;
    }

    /**
     * Override store function to add some custom logic for setting create time and
     * store contentobject_attribute_id in session to avoid several ratings from same user.
     *
     * @param array $fieldFilters
     */
    function store( $fieldFilters = null )
    {
        if (
            $this->attribute( 'user_id' ) == eZUser::currentUserID()
            && eZINI::instance()->variable( 'eZStarRating', 'UseUserSession' ) === 'enabled'
        )
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

    /**
     * Remove rating data by content object id and optionally attribute id.
     *
     * @param int $contentobjectID
     * @param int $contentobjectAttributeId
     */
    static function removeByObjectId( $contentobjectID, $contentobjectAttributeId = null )
    {
        $cond = array( 'contentobject_id' => $contentobjectID );
        if ( $contentobjectAttributeId !== null )
        {
            $cond['contentobject_attribute_id'] = $contentobjectAttributeId;
        }
        eZPersistentObject::removeObject( self::definition(), $cond );
    }

    /**
     * Fetch rating by rating id!
     *
     * @param int $id
     * @return null|ezsrRatingDataObject
     */
    static function fetch( $id )
    {
        $cond = array( 'id' => $id );
        $return = eZPersistentObject::fetchObject( self::definition(), null, $cond );
        return $return;
    }

    /**
     * Fetch rating data by content object id and optionally attribute id!
     *
     * @param int $contentobjectID
     * @param int $contentobjectAttributeId
     * @return null|ezsrRatingDataObject
     */
    static function fetchByObjectId( $contentobjectID, $contentobjectAttributeId = null )
    {
        $cond = array( 'contentobject_id' => $contentobjectID );
        if ( $contentobjectAttributeId !== null )
        {
            $cond['contentobject_attribute_id'] = $contentobjectAttributeId;
        }
        $return = eZPersistentObject::fetchObjectList( self::definition(), null, $cond );
        return $return;
    }

    /**
     * Create a ezsrRatingDataObject and store it.
     * Note: No access checks or input validation is done other then on rating
     *
     * @param int $contentobjectId
     * @param int $contentobjectAttributeId
     * @param int $rate (above 0 and  bellow or equal 5)
     * @param bool $onlyStoreIfUserNotAlreadyRated
     * @return null|ezsrRatingDataObject
     */
    static function rate( $contentobjectId, $contentobjectAttributeId, $rate )
    {
        $rating = null;
        if ( is_numeric( $contentobjectId ) &&
             is_numeric( $contentobjectAttributeId ) &&
             is_numeric( $rate) &&
             $rate <= 5 && $rate > 0 )
        {
            $row = array ('contentobject_attribute_id' => $contentobjectAttributeId,
                          'contentobject_id'           => $contentobjectId,
                          'rating'                     => $rate);

            $rating = self::create( $row );
            if ( !$rating->userHasRated() )
            {
                $rating->store();
                $avgRating = $rating->getAverageRating();
                $avgRating->updateFromRatingData();
                $avgRating->store();
                // clear the cache for all nodes associated with this object
                eZContentCacheManager::clearContentCacheIfNeeded( $contentobjectId, true, false );
            }
        }
        return $rating;
    }

    /**
     * Create a ezsrRatingDataObject by definition data (but do not store it, thats up to you!)
     * NOTE: you have to provide the following attributes:
     *     contentobject_id
     *     contentobject_attribute_id
     *     rating (this is only requried if you plan to store the object)
     *
     * @param array $row
     * @return ezsrRatingDataObject
     */
    static function create( $row = array() )
    {
        if ( !isset( $row['session_key'] ) )
        {
            $http = eZHTTPTool::instance();

            if (
                eZINI::instance()->variable( 'eZStarRating', 'UseUserSession' ) === 'enabled'
                && !eZSession::hasStarted()
            )
            {
                // Creates a session for anonymous
                eZSession::start();
            }

            $row['session_key'] = $http->sessionID();
        }

        if ( !isset( $row['user_id'] ) )
        {
            $row['user_id'] = eZUser::currentUserID();
        }

        if ( !isset( $row['created_at'] ) )
        {
            $row['created_at'] = time();
        }

        if ( !isset( $row['contentobject_id'] ) )
        {
            eZDebug::writeError( 'Missing \'contentobject_id\' parameter!', __METHOD__ );
        }

        if ( !isset( $row['contentobject_attribute_id'] ) )
        {
            eZDebug::writeError( 'Missing \'contentobject_attribute_id\' parameter!', __METHOD__ );
        }

        $object = new self( $row );
        return $object;
    }

    /**
     * Fetch rating ( avrage + total + raw rating data ) by conditions
     *
     * @param array $params (see inline doc for possible conditions)
     * @return array Array of rating data
     */
    static function fetchByConds( $params )
    {
        /**
         * Conditions possible in $param (array hash):
         *   int 'contentobject_id'
         *   int 'contentobject_attribute_id'
         *   int 'user_id'
         *   string 'session_key'
         *   bool 'as_object' By default: true
         *
         *   Conditions can be combined as you wish,
         */
        $conds = array();
        $limit = null;
        $sorts = array();
        $asObject = isset( $params['as_object'] ) ? $params['as_object'] : true;

        if ( isset( $params['contentobject_id'] ) )
        {
            $conds['contentobject_id'] = $params['contentobject_id'];
        }
        else if ( isset( $params['object_id'] ) )// Alias
        {
            $conds['contentobject_id'] = $params['object_id'];
        }

        if ( isset( $params['contentobject_attribute_id'] ) )
        {
            if ( !isset( $conds['contentobject_id'] ) ) $conds['contentobject_id'] = array( '!=', 0 );// to make sure index is used
            $conds['contentobject_attribute_id'] = $params['contentobject_attribute_id'];
        }

        if ( isset( $params['user_id'] ) )
        {
            $conds['user_id'] = $params['user_id'];
        }

        if ( isset( $params['session_key'] ) )
        {
            if ( !isset( $conds['user_id'] ) ) $conds['user_id'] = array( '!=', 0 );// to make sure index is used
            $conds['session_key'] = $params['session_key'];
        }

        if ( isset( $params['limit'] ) )
        {
            $limit = array( 'length' => $params['limit'], 'offset' => (isset( $params['offset'] ) ? $params['offset'] : 0 ) );
        }
        else if ( isset( $params['offset'] ) )
        {
            $limit = array( 'offset' =>  $params['offset'] );
        }

        if ( isset( $params['sort_by'] ) )
        {
            if ( isset( $params['sort_by'][1] ) && is_array( $params['sort_by'] ) )
                $sorts = array( $params['sort_by'][0] => ( $params['sort_by'][1] ? 'asc' : 'desc' ) );
            else
                $sorts[ $params['sort_by'] ] = 'asc';
        }

        $rows = eZPersistentObject::fetchObjectList( self::definition(), null, $conds, $sorts, $limit, $asObject );

        if ( $rows === null )
        {
            eZDebug::writeError( 'The ezstarrating table seems to be missing,
                                  contact your administrator', __METHOD__ );
            return false;
        }
        return $rows;
    }
}

?>