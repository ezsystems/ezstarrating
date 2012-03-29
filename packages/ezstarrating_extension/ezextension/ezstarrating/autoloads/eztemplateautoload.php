<?php
/**
 * Template autoload definition for eZ Starrating
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/gnu_gpl GNU GPLv2
 *
 */

$eZTemplateOperatorArray = array();


$eZTemplateOperatorArray[] = array( 'script' => 'extension/ezstarrating/autoloads/ezsrtemplateoperators.php',
                                    'class' => 'ezsrTemplateOperators',
                                    'operator_names' => array( 'fetch_starrating_data',
                                                               'fetch_starrating_stats',
                                                               'fetch_by_starrating'
) );

?>
