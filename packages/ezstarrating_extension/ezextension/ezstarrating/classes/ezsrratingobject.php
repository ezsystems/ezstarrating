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
        $stats = self::stats( $this->attribute('contentobject_id'), $this->attribute('contentobject_attribute_id') );
        $return = 0;
        if (isset($stats['count']))
            $return = $stats['count'];
        return $return;
    }

    function getAverage()
    {
        $stats = self::stats( $this->attribute('contentobject_id'), $this->attribute('contentobject_attribute_id') );
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
        $stats = self::stats( $this->attribute('contentobject_id'), $this->attribute('contentobject_attribute_id') );
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
                                   'contentobject_id' => $this->attribute('contentobject_id'), // for table index
                                   'contentobject_attribute_id' => $contentobjectAttributeId );
                }
                else
                {
                    $cond = array( 'user_id' => $userId,
                                   'contentobject_id' => $this->attribute('contentobject_id'), // for table index
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

    static function fetch( $id )
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
     *     rating (this is only requried if you plan to store the object)
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

    static function stats( $ContentObjectID, $ContentObjectAttributeID )
    {
        static $cachedStats = array( 0 => null );
    	if ( isset( $cachedStats[$ContentObjectID][$ContentObjectAttributeID] ) )
        {
            $return = $cachedStats[$ContentObjectID][$ContentObjectAttributeID];
        }
        else
        {
            $custom = array( array( 'operation' => 'count( id )',
                                    'name'      => 'count' ) ,
                             array( 'operation' => 'avg( rating )',
                                    'name'      => 'average' ),
                             array( 'operation' => 'std( rating )',
                                    'name'      => 'std' ));
            $cond = array( 'contentobject_id' => $ContentObjectID, // needed for table index
                           'contentobject_attribute_id' => $ContentObjectAttributeID );
            $return = self::fetchObjectList( self::definition(), array() ,$cond, null, null, false, false, $custom );

            if ( is_array( $return ) )
                $return = $return[0];

            if ( isset( $cachedStats[$ContentObjectID] ) )
            {
                $cachedStats[$ContentObjectID] = array();
            }

            $cachedStats[$ContentObjectID][$ContentObjectAttributeID] = $return;
        }
        return $return;
    }

    static function fetchNodeByRating( $params )
    {
         /*
         * Fetch top/bottom content (nodes) by rating++
         * NOTE: Uses LEFT JOIN to also include nodes that has not been rated yet, might lead to performance issues!
         * 
         * Parms:
         * sort_by (default: array(array('rating', false ),array('rating_count', false)) controlls sorting
         *     possible sortings are rating_count, rating, object_count, view_count, published and modified 
         *     possible direction are true (ASC) and false (DESC)
         *     Note: 'object_count' makes only sense when combined with group_by_owner
         * class_identifier (default: empty) limit fetch to a specific classes
         * offset  (default: 0) set offset on returned list
         * limit (default: 10) limit number of objects returned
         * group_by_owner (default: false) will give you result grouped by owner instead
         *                and the node of the owner (user object) is
         *                fetched intead
         * main_parent_node_id (default: none) Limit result based on parent main node id
         * main_parent_node_path (default: none) Alternative to above param, uses path string
         *                instead for recursive fetch, format $node.path_string: '/1/2/144/'
         * owner_main_parent_node_id (default: none) Limit result based on parent main 
         *                node id of owner ( main user group ) 
         * owner_main_parent_node_path (default: none) Alternative to above param, uses path string
         *                instead for recursive fetch, format $node.path_string: '/1/2/144/'
         * owner_id (default: none) filters by owner object id
         * as_object (default: true) make node objects or not (rating ) 
         * load_data_map (default: false) preload data_map 
         */

        $ret         = array();
        $whereSql    = array();
        $offset      = 0;
        $limit       = 10;
        $fromSql     = '';
        $asObject    = isset( $params['as_object'] ) ? $params['as_object'] : true;
        $loadDataMap = isset( $params['load_data_map'] ) ? $params['load_data_map'] : false;
        $selectSql   = 'ezcontentobject.*, node_tree.*,';
        $groupBySql  = 'GROUP BY ezcontentobject.id';
        $orderBySql  = 'ORDER BY rating DESC, rating_count DESC';// default sorting
        
        // WARNING: group_by_owner only works as intended if user is owner of him self..
        if ( isset( $params['group_by_owner'] ) && $params['group_by_owner'] )
        {
            // group by owner instead of content object and fetch users instead of content objects
            $selectSql  = 'ezcontentobject.*, owner_tree.*,';
            $groupBySql = 'GROUP BY ezcontentobject.owner_id';
        }
        
        if ( isset( $params['owner_main_parent_node_id'] ) and is_numeric( $params['owner_main_parent_node_id'] ) )
        {
            // filter by main parent node of owner (main user group)
            $parentNodeId = $params['owner_main_parent_node_id'];
            $whereSql[] = 'owner_tree.parent_node_id = ' . $parentNodeId;
        }
        else if ( isset( $params['owner_main_parent_node_path'] ) and is_string( $params['owner_main_parent_node_path'] ) )
        {
            // filter recursivly by main parent node id
            // supported format is /1/2/144/256/ ( $node.path_string )
            $parentNodePath = $params['owner_main_parent_node_path'];
            $whereSql[] = "owner_tree.path_string != '$parentNodePath'";
            $whereSql[] = "owner_tree.path_string like '$parentNodePath%'";
        }
        else if ( isset( $params['owner_id'] ) and is_numeric($params['owner_id']) )
        {
            // filter by owner_id ( user / contentobject id)
            $ownerId = $params['owner_id'];
            $whereSql[] = 'ezcontentobject.owner_id = ' . $ownerId;
        }
        
        if ( isset( $params['main_parent_node_id'] ) and is_numeric( $params['main_parent_node_id'] ) )
        {
            // filter by main parent node id
            $parentNodeId = $params['main_parent_node_id'];
            $whereSql[] = 'node_tree.parent_node_id = ' . $parentNodeId;
        }
        else if ( isset( $params['main_parent_node_path'] ) and is_string( $params['main_parent_node_path'] ) )
        {
            // filter recursivly by main parent node id
            // supported format is /1/2/144/256/ ( $node.path_string )
            $parentNodePath = $params['main_parent_node_path'];
            $whereSql[] = "node_tree.path_string != '$parentNodePath'";
            $whereSql[] = "node_tree.path_string like '$parentNodePath%'";
        }
        
        if ( isset( $params['class_identifier'] ) )
        {
            // filter by class id
            $classID = array();
            $classIdentifier = $params['class_identifier'];
            if ( !is_array( $classIdentifier )) $classIdentifier = array( $classIdentifier );
            
            foreach ( $classIdentifier as $id )
            {
                $classID[] = is_string( $id ) ? eZContentObjectTreeNode::classIDByIdentifier( $id ) : $id;
            }
            if ( $classID )
            {
                $whereSql[] = 'ezcontentobject.contentclass_id in (' . implode( ',', $classID ) . ')';
            }
        }

        if ( isset( $params['limit'] ))
        {
            $limit = (int) $params['limit'];
        }

        if ( isset( $params['offset'] ))
        {
            $offset = (int) $params['offset'];
        }
        
        if ( isset( $params['sort_by'] ) && is_array( $params['sort_by'] ) )
        {
            $orderBySql = 'ORDER BY ';
            $orderArr = is_string( $params['sort_by'][0] ) ? array( $params['sort_by'] ) : $params['sort_by'];
            foreach( $orderArr as $key => $order )
            {
                if ( $key !== 0 ) $orderBySql .= ',';
                $direction = isset( $order[1] ) ? $order[1] : false;
                switch( $order[0] )
                {
                    case 'rating':
                    {
                        $orderBySql .= 'rating ' . ( $direction ? 'ASC' : 'DESC');
                    }break;
                    case 'rating_count':
                    {
                        $orderBySql .= 'rating_count ' . ( $direction ? 'ASC' : 'DESC');
                    }break;
                    case 'object_count':
                    {
                        $orderBySql .= 'object_count ' . ( $direction ? 'ASC' : 'DESC');
                    }break;
                    case 'published':
                    {
                        $orderBySql .= 'ezcontentobject.published ' . ( $direction ? 'ASC' : 'DESC');
                    }break;
                    case 'modified':
                    {
                        $orderBySql .= 'ezcontentobject.modified ' . ( $direction ? 'ASC' : 'DESC');
                    }break;
                    case 'view_count':
                    {
                        // notice: will only fetch nodes that HAVE a entry in the ezview_counter table!!!
                        $selectSql  .= 'ezview_counter.count as view_count,';
                        $fromSql    .= 'ezview_counter,';
                        $whereSql[]  = 'node_tree.node_id = ezview_counter.node_id';
                        $orderBySql .= 'view_count ' . ( $direction ? 'ASC' : 'DESC');                        
                    }break;
                }
            }
        }

        $whereSql = $whereSql ? ' AND ' . implode( $whereSql, ' AND '): '';

        $db  = eZDB::instance();
        $sql = "SELECT
                             $selectSql
                             AVG( ezstarrating.rating  ) as rating,
                             COUNT( ezstarrating.rating  ) as rating_count,
                             COUNT( ezstarrating.id ) as object_count,
                             ezcontentclass.serialized_name_list as class_serialized_name_list,
                             ezcontentclass.identifier as class_identifier,
                             ezcontentclass.is_container as is_container
                            FROM
                             ezcontentobject_tree node_tree,
                             ezcontentobject_tree owner_tree,
                             ezcontentclass,
                             $fromSql
                             ezcontentobject
                            LEFT JOIN ezstarrating
                             ON ezstarrating.contentobject_id = ezcontentobject.id
                            WHERE
                             ezcontentobject.id = node_tree.contentobject_id AND
                             node_tree.node_id = node_tree.main_node_id AND
                             ezcontentobject.owner_id = owner_tree.contentobject_id AND
                             owner_tree.node_id = owner_tree.main_node_id AND
                             ezcontentclass.version=0 AND
                             ezcontentclass.id = ezcontentobject.contentclass_id
                             $whereSql
                            $groupBySql
                            $orderBySql";

        $ret = $db->arrayQuery( $sql, array( 'offset' => $offset, 'limit' => $limit ) );
        unset($db);

        if ( isset( $ret[0] ) && is_array( $ret ) )
        {
            if ( $asObject )
            {
                $ret = eZContentObjectTreeNode::makeObjectsArray( $ret );
                if ( $loadDataMap )
                    eZContentObject::fillNodeListAttributes( $ret );
            }
            else
            {
                //$ret = $ret;
            }
            
        }
        else if ( $ret === false )
        {
            eZDebug::writeError( 'The ezstarrating table seems to be missing,
                          contact your administrator', __METHOD__ );
            $ret = array();
        }
        else
        {
            $ret = array();
        }
        return $ret;
    }
}

?>
