<?php
$Module = array( "name" => "Star Rating" );

$ViewList = array();

$ViewList["collect"] = array( 
    'functions'               => array( 'collect' ),
    'script'                  => 'collect.php',
    'params' => array('ContentObjectAttributeID', 'version', 'rating' ) 
);


?>
