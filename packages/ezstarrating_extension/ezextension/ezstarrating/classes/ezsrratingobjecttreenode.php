<?php
//
// Definition of ezsrRatingObjectTreeNode class
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

/**
 * Only to be used by {@link ezsrRatingObject::fetchNodeByRating()}
 * As other fetch functions won't have the needed rating values in the returned data.
 * 
 * @author Andr� R.
 *
 */


class ezsrRatingObjectTreeNode extends eZContentObjectTreeNode
{
     /**
     * Construct
     * 
     * @param array $row
     */
    protected function __construct( $row )
    {
        $this->eZContentObjectTreeNode( $row );
    }

    /** definition of ezsrRatingObjectTreeNode, extends eZContentObjectTreeNode definition
     * 
     *  @return array
     */
    static function definition()
    {
        static $def = null;
        if ( $def === null )
        {
            $def = parent::definition();
            $def['class_name'] = 'ezsrRatingObjectTreeNode';
            $def['fields']['rating'] = array( 'name' => 'Rating',
                                                               'datatype' => 'integer',
                                                               'default' => 0,
                                                               'required' => false );
            $def['fields']['rating_count'] = array( 'name' => 'RatingCount',
                                                               'datatype' => 'integer',
                                                               'default' => 0,
                                                               'required' => false );
        }
        return $def;
    }
    
    // Needs to be forked since tree node doesn't use the asObject
    // handling in eZPersistentObject
    static function makeObjectsArray( $array , $with_contentobject = true )
    {
        $retNodes = array();
        if ( !is_array( $array ) )
            return $retNodes;

        foreach ( $array as $node )
        {
            unset( $object );

            if( $node['node_id'] == 1 )
            {
                if( !isset( $node['name'] ) || !$node['name'] )
                    $node['name'] = ezpI18n::tr( 'kernel/content', 'Top Level Nodes' );
            }

            $object = new self( $node );
            // If the name is not set it will be fetched later on when
            // getName()/attribute( 'name' ) is accessed.
            if ( isset( $node['name'] ) )
            {
                $object->setName( $node['name'] );
            }

            if ( isset( $node['class_serialized_name_list'] ) )
            {
                $node['class_name'] = eZContentClass::nameFromSerializedString( $node['class_serialized_name_list'] );
                $object->ClassName = $node['class_name'];
            }
            if ( isset( $node['class_identifier'] ) )
                $object->ClassIdentifier = $node['class_identifier'];

            if ( isset( $node['is_container'] ) )
                $object->ClassIsContainer = $node['is_container'];

            if ( $with_contentobject )
            {
                if ( isset( $node['class_name'] ) )
                {
                    unset( $node['remote_id'] );
                    $contentObject = new eZContentObject( $node );

                    $permissions = array();
                    $contentObject->setPermissions( $permissions );
                    $contentObject->setClassName( $node['class_name'] );
                    if ( isset( $node['class_identifier'] ) )
                        $contentObject->ClassIdentifier = $node['class_identifier'];

                }
                else
                {
                    $contentObject = new eZContentObject( array());
                    if ( isset( $node['name'] ) )
                         $contentObject->setCachedName( $node['name'] );
                }
                if ( isset( $node['real_translation'] ) && $node['real_translation'] != '' )
                {
                    $object->CurrentLanguage = $node['real_translation'];
                    $contentObject->CurrentLanguage = $node['real_translation'];
                }
                if ( $node['node_id'] == 1 )
                {
                    $contentObject->ClassName = 'Folder';
                    $contentObject->ClassIdentifier = 'folder';
                    $contentObject->ClassID = 1;
                    $contentObject->SectionID = 1;
                }

                $object->setContentObject( $contentObject );
            }
            $retNodes[] = $object;
        }
        return $retNodes;
    }
    
    
}

?>