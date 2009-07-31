<?php

// Include the super class file
include_once( "kernel/classes/ezdatatype.php" );
include_once( "extension/starrating/classes/starrating.php" );

// Define the name of datatype string
define( "EZ_DATATYPESTRING_STARRATING", "starrating" );


class starratingType extends eZDataType
{
  /*!
   Construction of the class, note that the second parameter in eZDataType 
   is the actual name showed in the datatype dropdown list.
  */
  function starratingType()
  {
    $this->eZDataType( EZ_DATATYPESTRING_STARRATING, "Star Rating" );
  }

  /*!
    Validates the input and returns true if the input was
    valid for this datatype.
  */
  function validateObjectAttributeHTTPInput( $http, $base, 
                                               $objectAttribute )
  {
    return eZInputValidator::STATE_ACCEPTED;
  }

  function deleteStoredObjectAttribute( $objectAttribute, $version = null )
  {
    // Remove all ratings associated with thes objectAttribute;
    if ($version == null)
    {
      starrating::removeAll( $objectAttribute->attribute('id') );
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
    return true;
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
    $row = array('contentobject_attribute_id' => $objectAttribute->attribute('id'),
                 'version'                    => $objectAttribute->attribute('version'));
    $object = starrating::create($row);
    return $object;
  }
}
eZDataType::register( EZ_DATATYPESTRING_STARRATING, "starratingType" );
