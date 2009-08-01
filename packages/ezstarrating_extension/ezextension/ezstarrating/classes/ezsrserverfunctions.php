<?php
//
// Definition of ezsrServerFunctions class
//
// Created on: <31-Jul-2009 00:00:00 ar>
//
// ## BEGIN COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
// SOFTWARE NAME: eZ Star Rating
// SOFTWARE RELEASE: 2.x
// COPYRIGHT NOTICE: Copyright (C) 2009 eZ Systems AS
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

/*
 * ezjscServerFunctions for ezstarrating (rating related)
 */

class ezsrServerFunctions extends ezjscServerFunctions
{
    /**
     * Rate content object attribute id
     *
     * @param array $args ( 0 => contentobjectattribute_id, 1 => rating )
     * @return bool
     */
    public static function rate( $args )
    {
        $ret = array( 'id' => 0, 'rated' => false, 'already_rated' => false );
        if ( isset( $args[0] ) )
            $ret['id'] = $args[0];
            
        if ( !isset( $args[2] ) || !is_numeric( $args[0] ) || !is_numeric( $args[1] ) || !is_numeric( $args[2] ) || $args[2] > 5 || $args[2] < 1 )
            return $ret;

        $contentobjectAttribute = eZContentObjectAttribute::fetch( $ret['id'], $args[1] );
        if ( !$contentobjectAttribute instanceof eZContentObjectAttribute )
            return $ret;

        if ( $contentobjectAttribute->attribute('data_type_string') !== ezsrRatingType::DATA_TYPE_STRING )
            return $ret;

        $contentobject = $contentobjectAttribute->attribute('object');
        if ( !$contentobject instanceof eZContentObject || !$contentobject->attribute('can_read') )
            return $ret;

        $rateObj = ezsrRatingObject::create( array( 'contentobject_id' => $contentobjectAttribute->attribute('contentobject_id'),
                                                    'contentobject_attribute_id' =>  $ret['id'],
                                                    'rating' => $args[2]
        ));
        if ( $rateObj->userHasRated() )
        {
            $ret['already_rated'] = true;
        }
        else
        {
        	$rateObj->store();
        	$ret['rated'] = true;
        	eZContentCacheManager::clearContentCacheIfNeeded( $rateObj->attribute('contentobject_id'), true, false );
        }

        return $ret;
    }

    /**
     * Rate content object attribute id
     *
     * @param array $args ( 0 => contentobjectattribute_id )
     * @return false|array
     *
    public static function view( $args )
    {
        if ( !isset( $args[0] ) || !$args[0] )
            return false;

        return array( 'count' => 44, 'rating' => 3.5 );
    }/*

    /**
     * Reimp
     */
    public static function getCacheTime( $functionName )
    {
        return time();
    }
}

?>