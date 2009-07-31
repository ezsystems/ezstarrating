<?php
//
// Definition of ezsrTemplateOperators class
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

class ezsrTemplateOperators
{
    function ezsrTemplateOperators()
    {
    }

    function operatorList()
    {
        return array( 'ezsrrating',
                      'fetch_by_starrating'
                      );
    }

    function namedParameterPerOperator()
    {
        return true;
    }

    function namedParameterList()
    {
        return array( 'ezsrrating' => array( 'params' => array( 'type' => 'array',
                                              'required' => true,
                                              'default' => array() )),
                      'fetch_by_starrating' => array( 'params' => array( 'type' => 'array',
                                              'required' => false,
                                              'default' => array() ))
        );
                                              
    }

    function modify( $tpl, $operatorName, $operatorParameters, $rootNamespace, $currentNamespace, &$operatorValue, $namedParameters )
    {
        switch ( $operatorName )
        {
            case 'ezsrrating':
            {
                $ret = false;
                $params = $namedParameters['params'];
                $conds = array();

                if ( isset( $params['node_id'] ))
                {
                    $obj = eZContentObject::fetchByNodeID( $params['node_id'], false );
                    if ( isset( $obj['id'] ) ) $conds['contentobject_id'] = $obj['id'];
                }

                if ( isset( $params['contentobject_id'] ))
                {
                    // Note, there is no index on content object id, please use contentobject_attribute_id
                	$conds['contentobject_id'] = $params['contentobject_id'];
                }

                if ( isset( $params['contentobject_attribute_id'] ))
                {
                    $conds['contentobject_attribute_id'] = $params['contentobject_attribute_id'];
                }

                if ( isset( $params['user_id'] ))
                {
                    $conds['user_id'] = $params['user_id'];
                }

                if ( isset( $params['session_key'] ))
                {
                    $conds['session_key'] = $params['session_key'];
                }

                $ret = eZPersistentObject::fetchObject( ezsrRatingObject::definition(), null, $conds );
            } break;
            case 'fetch_by_starrating':
            {
                $ret = ezsrRatingObject::fetchNodeByRating( $namedParameters['params'] );
            } break;
        }
        $operatorValue = $ret;
    }
}

?>