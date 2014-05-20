<?php
//
// Definition of ezsrTemplateOperators class
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

class ezsrTemplateOperators
{
    function ezsrTemplateOperators()
    {
    }

    function operatorList()
    {
        return array( 'fetch_starrating_data',
                      'fetch_starrating_stats',
                      'fetch_by_starrating'
                      );
    }

    function namedParameterPerOperator()
    {
        return true;
    }

    function namedParameterList()
    {
        return array( 'fetch_starrating_data' => array( 'params' => array( 'type' => 'array',
                                              'required' => true,
                                              'default' => array() )),
                      'fetch_starrating_stats' => array( 'object_id' => array( 'type' => 'integer',
                                              'required' => true,
                                              'default' => 0 )),
                      'fetch_by_starrating' => array( 'params' => array( 'type' => 'array',
                                              'required' => false,
                                              'default' => array() ))
        );
                                              
    }

    function modify( $tpl, $operatorName, $operatorParameters, $rootNamespace, $currentNamespace, &$operatorValue, $namedParameters )
    {
        switch ( $operatorName )
        {
            case 'fetch_starrating_data':
            {
                $ret = ezsrRatingDataObject::fetchByConds( $namedParameters['params'] );
            } break;
            case 'fetch_starrating_stats':
            {
                $ret = ezsrRatingObject::stats( $namedParameters['object_id'] );
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