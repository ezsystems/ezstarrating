<?php
//
// Definition of ezsrServerFunctions class
//
// Created on: <31-Jul-2009 00:00:00 ar>
//
// ## BEGIN COPYRIGHT, LICENSE AND WARRANTY NOTICE ##
// SOFTWARE NAME: eZ Star Rating
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

/*
 * ezjscServerFunctions for ezstarrating (rating related)
 */

class ezsrServerFunctions extends ezjscServerFunctions
{
    /**
     * Rate content object attribute id
     *
     * @param array $args ( 0 => contentobjectattribute_id,  1 => contentobject_version, 2 => rating )
     * @return array
     */
    public static function rate( $args )
    {
        $ret = array( 'id' => 0, 'rated' => false, 'already_rated' => false, 'stats' => false );
        if ( !isset( $args[2] ) )
            throw new LengthException( 'Rating expects 3 arguments: attr_id, version, rating' );
        else if ( !is_numeric( $args[0] ) )
            throw new InvalidArgumentException( 'Rating argument[0] attr_id must be a number' );
        else if ( !is_numeric( $args[1] ) )
            throw new InvalidArgumentException( 'Rating argument[1] version must be a number' );
        else if ( !is_numeric( $args[2] ) )
            throw new InvalidArgumentException( 'Rating argument[2] rating must be a number' );
        else if ( $args[2] > 5 || $args[2] < 1 )
            throw new UnexpectedValueException( 'Rating argument[2] rating must be between 1 and 5' );

        $ret['id'] = (int) $args[0];

        // Provide extra session protection on 4.1 (not possible on 4.0) by expecting user
        // to have an existing session (new session = mostlikely a spammer / hacker trying to manipulate rating)
        if (
            eZSession::userHasSessionCookie() !== true
            && eZINI::instance()->variable( 'eZStarRating', 'AllowAnonymousRating' ) === 'disabled'
        )
            return $ret;

        // Return if parameters are not valid attribute id + version numbers
        $contentobjectAttribute = eZContentObjectAttribute::fetch( $ret['id'], $args[1] );
        if ( !$contentobjectAttribute instanceof eZContentObjectAttribute )
            return $ret;

        // Return if attribute is not a rating attribute
        if ( $contentobjectAttribute->attribute('data_type_string') !== ezsrRatingType::DATA_TYPE_STRING )
            return $ret;

        // Return if rating has been disabled on current attribute
        if ( $contentobjectAttribute->attribute('data_int') )
            return $ret;

        // Return if user does not have access to object
        $contentobject = $contentobjectAttribute->attribute('object');
        if ( !$contentobject instanceof eZContentObject || !$contentobject->attribute('can_read') )
            return $ret;

        $rateDataObj = ezsrRatingDataObject::create( array( 'contentobject_id' => $contentobjectAttribute->attribute('contentobject_id'),
                                                    'contentobject_attribute_id' =>  $ret['id'],
                                                    'rating' => $args[2]
        ));

        $proiorRating = $rateDataObj->userHasRated( true );

        if ( $proiorRating === true )
        {
            $ret['already_rated'] = true;
        }
        else if ( $proiorRating instanceof ezsrRatingDataObject )
        {
            $rateDataObj = $proiorRating;
            $rateDataObj->setAttribute( 'rating', $args[2] );
            $ret['already_rated'] = true;
            $proiorRating = false;// just to reuse code bellow
        }

        if ( !$proiorRating )
        {
            $rateDataObj->store();
            $avgRateObj = $rateDataObj->getAverageRating();
            $avgRateObj->updateFromRatingData();
            $avgRateObj->store();
            eZContentCacheManager::clearContentCacheIfNeeded( $rateDataObj->attribute('contentobject_id') );
            $ret['rated'] = true;
            $ret['stats'] = array(
               'rating_count' => $avgRateObj->attribute('rating_count'),
               'rating_average' => $avgRateObj->attribute('rating_average'),
               'rounded_average' => $avgRateObj->attribute('rounded_average'),
            );
        }
        return $ret;
    }

    /**
     * Check if user has rated.
     *
     * @param array $args ( 0 => contentobject_id,  1 => contentobjectattribute_id )
     * @return bool|null (null if params are wrong)
     */
    public static function user_has_rated( $args )
    {
        if ( !isset( $args[1] ) || !is_numeric( $args[0] ) || !is_numeric( $args[1] ) )
            return null;

        $rateDataObj = ezsrRatingDataObject::create( array( 'contentobject_id' => $args[0],
                                                    'contentobject_attribute_id' =>  $args[1]
        ));

        return $rateDataObj->userHasRated();
    }

    /**
     * Reimp
     */
    public static function getCacheTime( $functionName )
    {
        return time();
    }
}

?>