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

class ezsrRatingObject extends eZPersistentObject
{
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
                    'rating_average' => array(
                      'name' => 'rating_average',
                      'datatype' => 'float',
                      'default' => 0,
                      'required' => false ),
                    'rating_count' => array(
                      'name' => 'rating_count',
                      'datatype' => 'integer',
                      'default' => 0,
                      'required' => false ),
                  ),
                  'keys' => array( 'contentobject_id', 'contentobject_attribute_id' ),
                  'function_attributes' => array(
                      'rounded_average' => 'getRoundedAverage',
                      'rating' => 'getRating',
                      'rating_data' => 'getRatingData',
                      'current_user_has_rated' => 'currentUserHasRated',
                  ),
                  'class_name' => 'ezsrRatingObject',
                  'name' => 'ezstarrating' );
        return $def;
    }

    /**
     * Get a rounded (nearest 0.5) version of 'rating_average'
     *
     * @return float
     */
    function getRoundedAverage()
    {
        $avg = $this->attribute('rating_average');
        $rnd_avg = intval($avg * 2 + 0.5) / 2;
        return $rnd_avg;
    }

    /**
     * Returns the rating, rounded to one digit
     *
     * @return float
     */
    public function getRating()
    {
        return round( $this->attribute( 'rating_average' ), 1 );
    }

    /**
     * Fetch rating data
     *
     * @return array
     */
    function getRatingData()
    {
        return ezsrRatingDataObject::fetchByObjectId( $this->attribute('contentobject_id'), $this->attribute('contentobject_attribute_id') );
    }

    /**
     * Check if current user has rated on this content attribute or not!
     *
     * @return bool
     */
    function currentUserHasRated()
    {
        $rateDataObj = ezsrRatingDataObject::create( array( 'contentobject_id' => $this->attribute('contentobject_id'),
                                                            'contentobject_attribute_id' =>  $this->attribute('contentobject_attribute_id'),
                                                            'created_at' => 0 ) );
        return $rateDataObj->userHasRated();
    }

    /**
     * Remove calculated ratings by content object id and optionally attribute id.
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
     * Create a ezsrRatingObject by definition data (but do not store it, thats up to you!)
     * NOTE: you have to provide the following attributes:
     *     contentobject_id
     *     contentobject_attribute_id
     *
     * @param array $row
     * @return ezsrRatingDataObject
     */
    static function create( $row = array() )
    {
        if ( !isset( $row['contentobject_id'] ) )
            eZDebug::writeError( 'Missing \'contentobject_id\' parameter!', __METHOD__ );

        if ( !isset( $row['contentobject_attribute_id'] ) )
            eZDebug::writeError( 'Missing \'contentobject_attribute_id\' parameter!', __METHOD__ );

        if ( !isset( $row['rating_average'] ) )
            $row['rating_average'] = 0.0;

        if ( !isset( $row['rating_count'] ) )
            $row['rating_count'] = 0;

        $object = new self( $row );
        return $object;
    }

    /**
     * Fetch rating data by content object id and optionally attribute id!
     *
     * @param int $contentobjectID
     * @return null|ezsrRatingObject
     */
    static function fetchByObjectId( $contentobjectID, $contentobjectAttributeId = null )
    {
        $cond = array( 'contentobject_id' => $contentobjectID );
        if ( $contentobjectAttributeId !== null )
        {
            $cond['contentobject_attribute_id'] = $contentobjectAttributeId;
        }
        $return = eZPersistentObject::fetchObject( self::definition(), null, $cond );
        return $return;
    }

    /**
     * Update rating_average and rating_count from rating data.
     * Note: Does not store the change!
     *
     * @return bool False if no rating data was returned so no updates could be done
     */
    function updateFromRatingData()
    {
        $custom = array( array( 'operation' => 'count( id )',
                                'name'      => 'rating_count' ) ,
                         array( 'operation' => 'avg( rating )',
                                'name'      => 'rating_average' ));
        $cond = array( 'contentobject_id' => $this->attribute('contentobject_id'),
                       'contentobject_attribute_id' => $this->attribute('contentobject_attribute_id') );
        $data = self::fetchObjectList( ezsrRatingDataObject::definition(), array(), $cond, null, null, false, false, $custom );
        if ( isset( $data[0]['rating_average'] ) )
        {
            $this->setAttribute( 'rating_average', $data[0]['rating_average'] );
            $this->setAttribute( 'rating_count', $data[0]['rating_count'] );
            return true;
        }
        return false;
    }

    /**
     * Fetch and cache rating stats pr object id.
     * ( usefull when you have several rating attributes pr object )
     *
     * @param int $ContentObjectID
     * @return array (with count and average values)
     */
    static function stats( $ContentObjectID )
    {
        static $cachedStats = array( 0 => null );
        if ( isset( $cachedStats[$ContentObjectID] ) )
        {
            return $cachedStats[$ContentObjectID];
        }
        else
        {
            $custom = array( array( 'operation' => 'sum( rating_count )',
                                    'name'      => 'rating_count' ) ,
                             array( 'operation' => 'avg( rating_average )',
                                    'name'      => 'rating_average' ) );
            $cond = array( 'contentobject_id' => $ContentObjectID );
            $return = self::fetchObjectList( self::definition(), array() ,$cond, null, null, false, false, $custom );

            if ( isset( $return[0]['rating_count'] ) )
                $return = $return[0];

            $cachedStats[$ContentObjectID] = $return;
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
         * Works like fetch list/tree, except:
         * 1. Attribute filter is not supported (because of dependency on normal sort_by param)
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
        $groupBySql  = '';
        $orderBySql  = 'ORDER BY rating DESC, rating_count DESC';// default sorting
        $ratingFields = 'ezstarrating.rating_average as rating, ezstarrating.rating_count as rating_count,';

        // WARNING: group_by_owner only works as intended if user is owner of him self..
        if ( isset( $params['group_by_owner'] ) && $params['group_by_owner'] )
        {
            // group by owner instead of content object and fetch users instead of content objects
            $selectSql  = 'ezcontentobject.*, owner_tree.*,';
            $ratingFields = 'AVG( ezstarrating.rating_average ) as rating, SUM( ezstarrating.rating_count ) as rating_count,';
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
        else if ( isset( $params['parent_node_path'] ) )
        {
            if ( is_string( $params['parent_node_path'] ) )
            {
                // filter recursivly by main parent node id
                // supported format is /1/2/144/256/ ( $node.path_string )
                $parentNodePath = $params['parent_node_path'];
                $whereSql[] = "ezcontentobject_tree.path_string != '$parentNodePath'";
                $whereSql[] = "ezcontentobject_tree.path_string like '$parentNodePath%'";
            }
            else
            {
                eZDebug::writeError( "Parameter 'parent_node_path' needs to be node path_string, was '$params[parent_node_path]'.", __METHOD__ );
            }
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
                        $selectSql  .= 'COUNT( ezcontentobject.id ) as object_count,';
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
                             $ratingFields
                             ezcontentclass.serialized_name_list as class_serialized_name_list,
                             ezcontentclass.identifier as class_identifier,
                             ezcontentclass.is_container as is_container
                             $versionNameTargets
                             $extendedAttributeFilter[columns]
                            FROM
                             ezcontentobject_tree,
                             ezcontentobject_tree owner_tree,
                             ezcontentclass,
                             ezcontentobject
                             $fromSql
                             $versionNameTables
                             $extendedAttributeFilter[tables]
                             $sqlPermissionChecking[from]
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
            $rows = $db->arrayQuery( $sql, array( 'offset' => $offset, 'limit' => $limit ), $server );
        else
            $rows = $db->arrayQuery( $sql, null, $server );

        $db->dropTempTableList( $sqlPermissionChecking['temp_tables'] );

        unset($db);

        if ( is_array( $rows ) && !empty( $rows ) )
        {
            if ( $asObject )
            {
                $ret = ezsrRatingObjectTreeNode::makeObjectsArray( $rows );
                if ( $loadDataMap )
                    eZContentObject::fillNodeListAttributes( $ret );
            }
            else
            {
                $ret = $rows;
            }
        }
        else if ( $rows === false )
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
