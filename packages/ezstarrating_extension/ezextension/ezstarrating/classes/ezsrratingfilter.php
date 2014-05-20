<?php
//
// Definition of ezsrRatingFilter class
//
// Created on: <10-Aug-2009 12:42:08 ar>
//
// ## BEGIN COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
// SOFTWARE NAME:  eZ Star Rating
// SOFTWARE RELEASE: 2.x
// COPYRIGHT NOTICE: Copyright (C) 2009-2014 eZ Systems AS
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
// ## END COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
//

class ezsrRatingFilter
{
    function ezsrRatingFilter()
    {
    }

    function createSqlParts( $params )
    {
        /*
         * Sorting nodes by rating
         * This extended attribute filter adds the following sorting options:
         *   rating
         *   rating_count
         *   
         * NOTE: This filter depends on the patch supplied in patch/extended_attribute_grop_by.diff
         * 
         * Params can be either hash or array, in case of array this is the order:
         * param 1: incl_not_rated (Optional, defaults to false. Nodes withouth rating will be included when true)
         * 
         * 
         * Full example for fetching nodes that are rated and sort them by rating:
         * 
         * {def $rated_articles = fetch( 'content', 'tree', hash(
                                      'parent_node_id', 1503,
                                      'limit', 3,
                                      'sort_by', array( 'rating', false() ),
                                      'class_filter_type', 'include',
                                      'class_filter_array', array( 'article' ),
                                      'extended_attribute_filter', hash( 'id', 'ezsrRatingFilter', 'params', hash( 'incl_not_rated', false() ) )
                                      ) )}
         * 
         */


        $leftJoin = isset( $params['incl_not_rated'] ) ? $params['incl_not_rated'] : ( isset( $params[0] ) ? $params[0] : false );

        if ( $leftJoin )
        {
            $sqlFrom = ', ezcontentobject ezco2 LEFT JOIN ezstarrating
                             ON ezstarrating.contentobject_id = ezco2.id ';
        	$sqlWhere = 'ezco2.id = ezcontentobject.id AND';
        }
        else
        {
            $sqlFrom = ', ezstarrating';
            $sqlWhere = 'ezstarrating.contentobject_id = ezcontentobject.id AND';
        }

        return array('tables' => $sqlFrom, 'joins' => $sqlWhere, 'columns' => ', AVG( ezstarrating.rating_average ) as rating,
                             SUM( ezstarrating.rating_count ) as rating_count',
                      'group_by' => 'GROUP BY ezcontentobject.id');
    }
}
?>