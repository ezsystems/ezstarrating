<?php
//
// Definition of ezsrRatingType class
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

class ezsrRatingType extends eZDataType
{
	const DATA_TYPE_STRING = 'ezsrrating';

    /*!
     Construction of the class, note that the second parameter in eZDataType 
     is the actual name showed in the datatype dropdown list.
    */
    function __construct()
    {
        parent::__construct( self::DATA_TYPE_STRING, ezi18n( 'extension/ezstarrating/datatype', 'Star Rating', 'Datatype name' ) );
    }

    /*!
      Validates the input and returns true if the input was
      valid for this datatype.
    */
    function validateObjectAttributeHTTPInput( $http, $base, $objectAttribute )
    {
        return eZInputValidator::STATE_ACCEPTED;
    }

    function deleteStoredObjectAttribute( $objectAttribute, $version = null )
    {
      // Remove all ratings associated with thes objectAttribute;
      if ($version == null)
      {
          ezsrRatingObject::removeAll( $objectAttribute->attribute('id') );
      }
    }

    /*!
    */
    function fetchObjectAttributeHTTPInput( $http, $base, $objectAttribute )
    {
        return true;
    }

    /*!
     Store the content. Since the content has been stored in function 
     fetchObjectAttributeHTTPInput(), this function is with empty code.
    */
    function storeObjectAttribute( $objectattribute )
    {
    }

    /*!
     Returns the meta data used for storing search indices.
    */
    function metaData( $contentObjectAttribute )
    {
      // TODO Should return the rating average...might have to think this through
      // as the avgerage changes independant of publishing of item 
      return 2;
    }

    /*!
     Returns the text.
    */
    function title( $objectAttribute, $name = null)
    {
        return $this->metaData($objectAttribute);
    }

    function isIndexable()
    {
        return false;
    }

    function sortKey( $objectAttribute )
    {
        return $this->metaData($objectAttribute);
    }
  
    function sortKeyType()
    {
        return 'integer';
    }

    function hasObjectAttributeContent( $contentObjectAttribute )
    {
        return true;
    }

    /*!
     Returns the content.
    */
    function objectAttributeContent( $objectAttribute )
    {
        $object = ezsrRatingObject::create( array('contentobject_id' => $objectAttribute->attribute('contentobject_id'),
                                                  'contentobject_attribute_id' => $objectAttribute->attribute('id') ) );
        return $object;
    }
}

eZDataType::register( ezsrRatingType::DATA_TYPE_STRING, 'ezsrRatingType' );
