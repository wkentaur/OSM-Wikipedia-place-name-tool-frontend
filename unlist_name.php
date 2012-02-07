<?php
// unlist_name.php
// 
// unlist name
// 

error_reporting(E_ALL);
ini_set('display_errors', true);
ini_set('html_errors', false);

require('/home/kentaur/php/osm_wp/common.php');

// functions
function h($s) { return htmlspecialchars($s); }


//main

session_start();
header('Content-Type: text/html; charset=utf-8');
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

$ll_from_lang = isset($_GET['ll_from_lang']) ? $_GET['ll_from_lang'] : null;
$ll_from = isset($_GET['ll_from']) ? intval( $_GET['ll_from'] ) : null;
$lang = isset($_GET['lang']) ? $_GET['lang'] : null;
$action = isset($_GET['action']) ? $_GET['action'] : null;

$ret_msg = '';
$sess_id = session_id();

if ( $ll_from_lang and $ll_from and $lang and $action and $sess_id) {

    // open psql connection
    $pg = pg_connect('host='. OSM_HOST .' dbname='. OSM_DB);
 
    // check for connection error
    if($e = pg_last_error()) trigger_error($e, E_USER_ERROR);
    
    //insert ignore
    $ins_rule = 'CREATE OR REPLACE RULE "bl_on_duplicate_ignore" AS ON INSERT TO "'. BLACKLIST_TABLE .'"
    WHERE EXISTS(SELECT 1 FROM '. BLACKLIST_TABLE .' 
        WHERE (bl_from_lang, bl_from, bl_lang, bl_session_id)=(NEW.bl_from_lang, NEW.bl_from, NEW.bl_lang, NEW.bl_session_id))
    DO INSTEAD NOTHING';
    $res = pg_query($ins_rule);
    if($e = pg_last_error()) trigger_error($e, E_USER_ERROR);   
  
    if( isset($action) ) switch($action) {
        case 'unlist':
            $ins_sql = "INSERT INTO ". BLACKLIST_TABLE . " 
                (bl_from_lang, bl_from, bl_lang, bl_session_id)
                VALUES ($1, $2, $3, $4)";
            $ins_res = pg_prepare( $pg, "ins_bl", $ins_sql );
            $ins_res = pg_execute( $pg, "ins_bl", array($ll_from_lang, $ll_from, $lang, $sess_id) );
            if($e = pg_last_error()) trigger_error($e, E_USER_ERROR);
            $sel_sql = "SELECT count(*) FROM ". BLACKLIST_TABLE . " 
                WHERE ( bl_lang=$1 AND bl_session_id=$2 )";
            $res = pg_prepare( $pg, "sel_count", $sel_sql );
            $res = pg_execute( $pg, "sel_count", array($lang, $sess_id) );
            if($e = pg_last_error()) trigger_error($e, E_USER_ERROR);
            if ($row = pg_fetch_assoc($res)) {
                $ret_msg = $row['count'];
            }
            break;        
    }

} //if $ll_from_lang and $ll_from and $lang and $action

print $ret_msg;

?>