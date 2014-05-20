<?php
//
// Definition of ezsrRatingType class
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

class ezsrRatingType extends eZDataType
{
    const DATA_TYPE_STRING = 'ezsrrating';

    /*!
     Construction of the class, note that the second parameter in eZDataType 
     is the actual name showed in the datatype dropdown list.
    */
    function __construct()
    {
        parent::__construct( self::DATA_TYPE_STRING, ezpI18n::tr( 'extension/ezstarrating/datatype', 'Star Rating', 'Datatype name' ), array( 'serialize_supported' => true ) );
    }

    /*!
      Validates the input and returns true if the input was
      valid for this datatype.
    */
    function validateObjectAttributeHTTPInput( $http, $base, $contentObjectAttribute )
    {
        return eZInputValidator::STATE_ACCEPTED;
    }

    function deleteStoredObjectAttribute( $contentObjectAttribute, $version = null )
    {
      // Remove all ratings associated with thes objectAttribute;
      if ( $version == null )
      {
          ezsrRatingObject::removeByObjectId( $contentObjectAttribute->attribute('contentobject_id'), $contentObjectAttribute->attribute('id') );
          ezsrRatingDataObject::removeByObjectId( $contentObjectAttribute->attribute('contentobject_id'), $contentObjectAttribute->attribute('id') );
      }
    }

    /*!
    */
    function fetchObjectAttributeHTTPInput( $http, $base, $contentObjectAttribute )
    {
        // Use data_int for storing 'disabled' flag
        $contentObjectAttribute->setAttribute( 'data_int', $http->hasPostVariable( $base . '_data_srrating_disabled_' . $contentObjectAttribute->attribute( 'id' ) ) );
        return true;
    }

    /*!
     Store the content. Since the content has been stored in function 
     fetchObjectAttributeHTTPInput(), this function is with empty code.
    */
    function storeObjectAttribute( $contentObjectAttribute )
    {
    }

    /*!
     Returns the meta data used for storing search indices.
    */
    function metaData( $contentObjectAttribute )
    {
        $ratingObj = $contentObjectAttribute->attribute( 'content' );
        return $ratingObj instanceof ezsrRatingObject ? $ratingObj->attribute('rating_count') > 0 : '';
    }

    /*!
     Returns the text.
    */
    function title( $contentObjectAttribute, $name = null)
    {
        return $this->metaData( $contentObjectAttribute );
    }

    function isIndexable()
    {
        return true;
    }

    function sortKey( $contentObjectAttribute )
    {
        return $this->metaData( $contentObjectAttribute );
    }
  
    function sortKeyType()
    {
        return 'integer';
    }

    function hasObjectAttributeContent( $contentObjectAttribute )
    {
        $ratingObj = $contentObjectAttribute->attribute( 'content' );
        return $ratingObj instanceof ezsrRatingObject ? $ratingObj->attribute('rating_count') > 0 : false;
    }

    /*!
     Returns the content.
    */
    function objectAttributeContent( $contentObjectAttribute )
    {
        $objectId = $contentObjectAttribute->attribute('contentobject_id');
        $attributeId = $contentObjectAttribute->attribute('id');
        $ratingObj = null;
        if ( $objectId && $attributeId )
        {
            $ratingObj = ezsrRatingObject::fetchByObjectId( $objectId, $attributeId );
    
            // Create empty object if none could be fetched
            if (  !$ratingObj instanceof ezsrRatingObject )
            {
                $ratingObj = ezsrRatingObject::create( array('contentobject_id' => $objectId,
                                                             'contentobject_attribute_id' => $attributeId ) );
            }
        }
        return $ratingObj;
    }
}

eZDataType::register( ezsrRatingType::DATA_TYPE_STRING, 'ezsrRatingType' );
