<?php
// verify_updates.php
// 
// Verifies if updated name:XX tags are in OSM database.
// Updates WP_LANG_TABLE

error_reporting(E_ALL);
ini_set('display_errors', true);
ini_set('html_errors', false);

$time_start = microtime(true);

require('/home/kentaur/php/osm_wp/common.php');

register_shutdown_function('shutdown'); 

//main

$log = new Logging();  
$log->lwrite('Started'); 

// open psql connection
$pg = pg_connect('host='. OSM_HOST .' dbname='. OSM_DB);
 
// check for connection error
if($e = pg_last_error()) trigger_error($e, E_USER_ERROR);




    //check updated rows
    $updated_sql = "SELECT ll_title FROM ". WP_LANG_TABLE . "
    WHERE status='". $st_lang['UPDATED'] ."'";
    $res = pg_query($updated_sql);
    if($e = pg_last_error()) trigger_error($e, E_USER_ERROR);
    while($row = pg_fetch_assoc($res))
    {
    }

$time_end = microtime(true);
$time = $time_end - $time_start;
$log->lwrite('Ended. Runtime: '. $time);  
    
?>