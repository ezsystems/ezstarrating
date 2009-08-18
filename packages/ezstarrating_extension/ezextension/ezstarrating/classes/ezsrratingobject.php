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
        if ( isset( $stats['count'] ) )
            $return = $stats['count'];
        return $return;
    }

    function getAverage()
    {
        $stats = self::stats( $this->attribute('contentobject_id'), $this->attribute('contentobject_attribute_id') );
        $return = 0;
        if ( isset( $stats['average'] ) )
            $return = $stats['average'];
        return $return;
    }

    function getRoundedAverage()
    {
        $avg = $this->getAverage();
        $rnd_avg = intval($avg * 2 + 0.5) / 2;
        return $rnd_avg;
    }

    function getSTD()
    {
        $stats = self::stats( $this->attribute('contentobject_id'), $this->attribute('contentobject_attribute_id') );
        $return = 0;
        if ( isset( $stats['std'] ) )
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

            
            $ini = eZINI::instance();
            $contentobjectAttributeId = $this->attribute('contentobject_attribute_id');
            if ( in_array( $contentobjectAttributeId, $attributeIdList ) && $ini->variable( 'eZStarRating', 'UseUserSession' ) === 'enabled' )
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

    /**
     * Override store function to add some custom logic for setting create time and 
     * store contentobject_attribute_id in session to avoid several ratings from same user.
     * 
     * @param array $fieldFilters
     */
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

    /**
     * Remove ratings by content object attribute id.
     * 
     * @param int $contentobjectAttributeId
     */
    static function removeAll( $contentobjectAttributeId )
    {
        $cond = array( 'contentobject_attribute_id' => $contentobjectAttributeId );
        eZPersistentObject::removeObject( self::definition(), $cond );
    }

    /**
     * Fetch rating by rating id!
     * 
     * @param int $id
     * @return null|ezsrRatingObject
     */
    static function fetch( $id )
    {
        $cond = array( 'id' => $id );
        $return = eZPersistentObject::fetchObject( self::definition(), null, $cond );
        return $return;
    }

    /**
     * Create a ezsrRatingObject and store it.
     * Note: Access check against content object is not done in this function, make sure you check at least can_read on object first!
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
            if ( $contentobjectAttribute instanceof eZContentObjectAttribute
              && $contentobjectAttribute->attribute('data_type_string') === ezsrRatingType::DATA_TYPE_STRING  )
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

    /**
     * Fetch and cache rating stats pr attribute id.
     * 
     * @param int $ContentObjectID
     * @param int $ContentObjectAttributeID
     * @return array (with count, average and std values)
     */
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

            if ( isset( $return[0]['std'] ) )
            {
                $return = $return[0];
            }

            if ( !isset( $cachedStats[$ContentObjectID] ) )
            {
                $cachedStats[$ContentObjectID] = array();
            }

            $cachedStats[$ContentObjectID][$ContentObjectAttributeID] = $return;
        }
        return $return;
    }

    /**
     * Fetch top/bottom content (nodes) by rating++
     * 
     * @param array $params (see inline doc for details)
     * @return array Returs array of nodes (either objects or raw db output based on as_object param)
     */
    static function fetchNodeByRating( $params )
    {
        /**
         * Works like fetch list/tree, execept:
         * 1. Attribute filter is not supported (because of dependancy on normal sort_by param)
         * 2. Supported sorting: rating, rating_count, object_count, published, modified and view_count.
         * 3. parent_node_id only works for list fetch, if you want tree fetch use 
         *    parent_node_path (format is like $node.path_string, as in '/1/2/144/256/').
         * 4. depth and depth_operator are not supported (so parent_node_path gives you unlimited depth).
         * 5. There are additional advance params to see rating, rating_count, object_count pr user / group
         *    see group_by_owner, owner_parent_node_id, owner_parent_node_path and owner_id.
         * 6. param 'include_not_rated' when set to true will use left join to also include unrated content
         */

        $ret         = array();
        $whereSql    = array();
        $offset      = false;
        $limit       = false;
        $fromSql     = '';
        $asObject         = isset( $params['as_object'] )          ? $params['as_object']          : true;
        $loadDataMap      = isset( $params['load_data_map'] )      ? $params['load_data_map']      : false;
        $mainNodeOnly     = isset( $params['main_node_only'] )     ? $params['main_node_only']     : false;
        $ignoreVisibility = isset( $params['ignore_visibility'] )  ? $params['ignore_visibility']  : false;
        $classFilterType  = isset( $params['class_filter_type'] )  ? $params['class_filter_type']  : false;
        $classFilterArray = isset( $params['class_filter_array'] ) ? $params['class_filter_array'] : false;
        $includeNotRated  = isset( $params['include_not_rated'] )  ? $params['include_not_rated']  : false;
        $selectSql   = 'ezcontentobject.*, ezcontentobject_tree.*,';
        $groupBySql  = 'GROUP BY ezcontentobject_tree.node_id';
        $orderBySql  = 'ORDER BY rating DESC, rating_count DESC';// default sorting
        
        // WARNING: group_by_owner only works as intended if user is owner of him self..
        if ( isset( $params['group_by_owner'] ) && $params['group_by_owner'] )
        {
            // group by owner instead of content object and fetch users instead of content objects
            $selectSql  = 'ezcontentobject.*, owner_tree.*,';
            $groupBySql = 'GROUP BY owner_tree.node_id';
        }
        
        if ( isset( $params['owner_parent_node_id'] ) and is_numeric( $params['owner_parent_node_id'] ) )
        {
            // filter by parent node of owner (main user group)
            $parentNodeId = $params['owner_parent_node_id'];
            $whereSql[] = 'owner_tree.parent_node_id = ' . $parentNodeId;
        }
        else if ( isset( $params['owner_parent_node_path'] ) and is_string( $params['owner_parent_node_path'] ) )
        {
            // filter recursivly by parent node id
            // supported format is /1/2/144/256/ ( $node.path_string )
            $parentNodePath = $params['owner_parent_node_path'];
            $whereSql[] = "owner_tree.path_string != '$parentNodePath'";
            $whereSql[] = "owner_tree.path_string like '$parentNodePath%'";
        }
        else if ( isset( $params['owner_id'] ) and is_numeric($params['owner_id']) )
        {
            // filter by owner_id ( user / contentobject id)
            $ownerId = $params['owner_id'];
            $whereSql[] = 'ezcontentobject.owner_id = ' . $ownerId;
        }
        
        if ( isset( $params['parent_node_id'] ) and is_numeric( $params['parent_node_id'] ) )
        {
            // filter by main parent node id
            $parentNodeId = $params['parent_node_id'];
            $whereSql[] = 'ezcontentobject_tree.parent_node_id = ' . $parentNodeId;
        }
        else if ( isset( $params['parent_node_path'] ) and is_string( $params['parent_node_path'] ) )
        {
            // filter recursivly by main parent node id
            // supported format is /1/2/144/256/ ( $node.path_string )
            $parentNodePath = $params['parent_node_path'];
            $whereSql[] = "ezcontentobject_tree.path_string != '$parentNodePath'";
            $whereSql[] = "ezcontentobject_tree.path_string like '$parentNodePath%'";
        }

        $classCondition = eZContentObjectTreeNode::createClassFilteringSQLString( $classFilterType, $classFilterArray );
        if ( $classCondition === false )
        {
            eZDebug::writeNotice( "Class filter returned false", __MEHOD__ );
            return null;
        }

        if ( isset( $params['limit'] ))
        {
            $limit = (int) $params['limit'];
        }
        
        if ( isset( $params['offset'] ))
        {
            $offset = (int) $params['offset'];
        }
        
        if ( $includeNotRated )
        {
        	$ratingFromSql = 'LEFT JOIN ezstarrating
                             ON ezstarrating.contentobject_id = ezcontentobject.id';
        	$ratingWhereSql = '';
        }
        else
        {
            $ratingFromSql = ', ezstarrating';
            $ratingWhereSql = 'ezstarrating.contentobject_id = ezcontentobject.id AND';
        }
        
        if ( isset( $params['sort_by'] ) && is_array( $params['sort_by'] ) )
        {
            $orderBySql = 'ORDER BY ';
            $orderArr = is_string( $params['sort_by'][0] ) ? array( $params['sort_by'] ) : $params['sort_by'];
            foreach( $orderArr as $key => $order )
            {
                $orderBySqlPart = false;
                $direction = isset( $order[1] ) ? $order[1] : false;
                switch( $order[0] )
                {
                    case 'rating':
                    {
                        $orderBySqlPart = 'rating ' . ( $direction ? 'ASC' : 'DESC');
                    }break;
                    case 'rating_count':
                    {
                        $orderBySqlPart = 'rating_count ' . ( $direction ? 'ASC' : 'DESC');
                    }break;
                    case 'object_count':
                    {
                        $orderBySqlPart = 'object_count ' . ( $direction ? 'ASC' : 'DESC');
                    }break;
                    case 'published':
                    {
                        $orderBySqlPart = 'ezcontentobject.published ' . ( $direction ? 'ASC' : 'DESC');
                    }break;
                    case 'modified':
                    {
                        $orderBySqlPart = 'ezcontentobject.modified ' . ( $direction ? 'ASC' : 'DESC');
                    }break;
                    case 'view_count':
                    {
                        // notice: will only fetch nodes that HAVE a entry in the ezview_counter table!!!
                        $selectSql  .= 'ezview_counter.count as view_count,';
                        $fromSql    .= ', ezview_counter';
                        $whereSql[]  = 'ezcontentobject_tree.node_id = ezview_counter.node_id';
                        $orderBySqlPart = 'view_count ' . ( $direction ? 'ASC' : 'DESC');                        
                    }break;
                    default:
                    {
                        if ( isset( $params['extended_attribute_filter'] ) )// allow custom sort types
                        {
                            $orderBySqlPart = $order[0] . ' ' . ( $direction ? 'ASC' : 'DESC');
                        }
                        else
                        {
                            eZDebug::writeError( "Unsuported sort type '$order[0]', for fetch_by_starrating().", __METHOD__ );
                        }
                    }break;
                }
                if ( $orderBySqlPart )
                {
                    if ( $key !== 0 ) $orderBySql .= ',';
                    $orderBySql .= $orderBySqlPart;
                }
            }
        }

        $whereSql = $whereSql ? implode( $whereSql, ' AND ') . ' AND ': '';

        $extendedAttributeFilter = eZContentObjectTreeNode::createExtendedAttributeFilterSQLStrings( $params['extended_attribute_filter'] );

        $limitation = ( isset( $params['limitation']  ) && is_array( $params['limitation']  ) ) ? $params['limitation']: false;
        $limitationList = eZContentObjectTreeNode::getLimitationList( $limitation );
        $sqlPermissionChecking = eZContentObjectTreeNode::createPermissionCheckingSQL( $limitationList );

        $languageFilter = ' AND ' . eZContentLanguage::languagesSQLFilter( 'ezcontentobject' );

        $useVersionName     = true;
        $versionNameTables  = eZContentObjectTreeNode::createVersionNameTablesSQLString ( $useVersionName );
        $versionNameTargets = eZContentObjectTreeNode::createVersionNameTargetsSQLString( $useVersionName );
        $versionNameJoins   = eZContentObjectTreeNode::createVersionNameJoinsSQLString( $useVersionName, false );

        $mainNodeOnlyCond       = eZContentObjectTreeNode::createMainNodeConditionSQLString( $mainNodeOnly );
        $showInvisibleNodesCond = eZContentObjectTreeNode::createShowInvisibleSQLString( !$ignoreVisibility );

        $db  = eZDB::instance();
        $sql = "SELECT
                             $selectSql
                             AVG( ezstarrating.rating  ) as rating,
                             COUNT( ezstarrating.rating  ) as rating_count,
                             COUNT( ezcontentobject.id ) as object_count,
                             ezcontentclass.serialized_name_list as class_serialized_name_list,
                             ezcontentclass.identifier as class_identifier,
                             ezcontentclass.is_container as is_container
                             $versionNameTargets
                             $extendedAttributeFilter[columns]
                            FROM
                             ezcontentobject_tree,
                             ezcontentobject_tree owner_tree,
                             ezcontentclass
                             $fromSql
                             $versionNameTables
                             $extendedAttributeFilter[tables]
                             $sqlPermissionChecking[from]
                             ,ezcontentobject
                             $ratingFromSql
                            WHERE
                             $extendedAttributeFilter[joins]
                             $ratingWhereSql
                             ezcontentobject.id = ezcontentobject_tree.contentobject_id AND
                             ezcontentobject.owner_id = owner_tree.contentobject_id AND
                             owner_tree.node_id = owner_tree.main_node_id AND
                             ezcontentclass.version=0 AND
                             ezcontentclass.id = ezcontentobject.contentclass_id AND
                             $mainNodeOnlyCond
                             $classCondition
                             $whereSql
	                         $versionNameJoins
	                         $showInvisibleNodesCond
	                         $sqlPermissionChecking[where]
	                         $languageFilter
                            $groupBySql
                            $orderBySql";

        $server = isset( $sqlPermissionChecking['temp_tables'][0] ) ? eZDBInterface::SERVER_SLAVE : false;

        if ( $offset !== false || $limit !== false )
            $ret = $db->arrayQuery( $sql, array( 'offset' => $offset, 'limit' => $limit ), $server );
        else
            $ret = $db->arrayQuery( $sql, null, $server );

        $db->dropTempTableList( $sqlPermissionChecking['temp_tables'] );

        unset($db);

        if ( isset( $ret[0] ) && is_array( $ret ) )
        {
            if ( $asObject )
            {
                $ret = eZContentObjectTreeNode::makeObjectsArray( $ret );
                if ( $loadDataMap )
                    eZContentObject::fillNodeListAttributes( $ret );
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
