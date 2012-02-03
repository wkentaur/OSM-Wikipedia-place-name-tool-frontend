<?php
// mark_name.php
// 
// user session based marking of name:XX values
// on succesful update output number of marked elements
// otherwise output empty string

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

if ( $ll_from_lang and $ll_from and $lang and $action ) {
    $marked_arr = array();
    
    if ( isset($_SESSION['marked_all'])  and isset($_SESSION['marked_all']["$lang"])) {
        $marked_arr = $_SESSION['marked_all']["$lang"];
    }

  
    if( isset($action) ) switch($action) {
        case 'mark-ok':
            $mark_ok = $ll_from_lang . '|' . $ll_from . '|' . $lang;
            if (!in_array($mark_ok, $marked_arr)) {
                $marked_arr[] = $mark_ok;
                $ret_msg = count( $marked_arr );
            }
            break;
        
        case 'mark-nok':
            $remove_mark = $ll_from_lang . '|' . $ll_from . '|' . $lang;
            if (in_array($remove_mark, $marked_arr)) {
                foreach($marked_arr as $key => $value) {
                    if ($value == $remove_mark) unset($marked_arr[$key]);
                }            
                $ret_msg = count( $marked_arr );
            }
            break;
    }
    //write back to session array
    $_SESSION['marked_all']["$lang"] = $marked_arr;

} //if $ll_from_lang and $ll_from and $lang and $action

print $ret_msg;

?>