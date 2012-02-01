<?php
// get_osc_file.php
// 
// uses psql WP_LANG_TABLE
// updates psql WP_LANG_TABLE
//

error_reporting(E_ALL);
ini_set('display_errors', true);
ini_set('html_errors', false);

$time_start = microtime(true);
   
require('/home/kentaur/php/osm_wp/common.php');
require_once('/home/kentaur/php/osm_wp/phposm.class.php');

//register_shutdown_function('shutdown'); 

//main
session_start();

$log = new Logging();  
$log->lwrite('Started');  

$lang = isset($_GET['lang']) ? $_GET['lang'] : null;
if (!$lang) {
    die("lang parameter not set!");
}

if ( isset($_SESSION['marked']) and count($_SESSION['marked']) ) {
    $marked_arr = $_SESSION['marked'];

    $osc_generator = 'OSM-Wikipedia bot';
    $osc_version = '0.6';


    // open psql connection
    $pg = pg_connect('host='. OSM_HOST .' dbname='. OSM_DB);
 
    // check for connection error
    if($e = pg_last_error()) die($e);

    $log->lwrite('Ready to add '. count($marked_arr) . ' marked rows for '. $lang);

    /* To fetch an object from the API you use the osm class */
    $osm = new osm();

    $update_count = 0;
    $xml_out_text = '';

    foreach ($marked_arr as $marked_key => $marked_row) {

        $cycle_start = microtime(true);
        list($ll_from_lang, $ll_from, $ll_lang) =
            explode("|", $marked_row, 3);
        //only update in $lang == $ll_lang
        if (strcmp ($lang, $ll_lang) == 0 ) {
            $sql = "SELECT osm_table, osm_id, ll_title 
            FROM ". OSM_WP_TABLE .", ". WP_LANG_TABLE ." 
            WHERE (". OSM_WP_TABLE .".wiki_lang = ". WP_LANG_TABLE .".ll_from_lang AND 
            ". OSM_WP_TABLE .".wiki_page_id = ". WP_LANG_TABLE .".ll_from AND
            ll_lang = '". pg_escape_string($lang) ."' AND
            ll_from_lang = '". pg_escape_string($ll_from_lang) ."' AND
            ll_from =". intval($ll_from) . ")";
            $res = pg_query($sql);
            if($e = pg_last_error()) die($e);
            if($row = pg_fetch_assoc($res)) {
                $osm_table = $row['osm_table'];
                $osm_id = abs($row['osm_id']);
                $name_in_wp = strip_wp_title($row['ll_title'], $lang);

                if ($row['osm_id'] < 0) {
                    $type = 'relation';
                } else {
                    switch ($row['osm_table']) {
                        case "planet_point":
                            $type = 'node';
                            break;
                        case "planet_line":
                            $type = 'way';
                            break;
                        case "planet_polygon":
                            $type = 'way';
                            break;
                    }
                } //if

    
                /* If we watch to delete or update a node or a way we have to fetch this object first */
                $toupdate = $osm->getObject($type, $osm_id);

                if ($toupdate) {  

                    $xml = new SimpleXMLElement($toupdate);
                    switch ($type) {
                        case "node":
                            $osm_obj = $xml->node;
                            $xp_tag_str = '/osm/node/tag';
                            break;
                        case "way":
                            $osm_obj = $xml->way;
                            $xp_tag_str = '/osm/way/tag';
                            break;
                        case "relation":
                            $osm_obj = $xml->relation;
                            $xp_tag_str = '/osm/relation/tag';
                            break;
                    }
                
                    unset($osm_obj['timestamp']);
                    unset($osm_obj['changeset']);
                    unset($osm_obj['user']);
                    unset($osm_obj['uid']);
                    //JOSM specific
                    $osm_obj->addAttribute('action', 'modify');

                    $name_lang = 'name:'. $lang;
                    $name_is_set = false;
                    foreach ($xml->xpath($xp_tag_str) as $tag) {    //xpath('/osm/node/tag')
                        if (strcmp($tag['k'], $name_lang) == 0) {
                            if (strcmp($tag['v'], $name_in_wp) == 0) {
                                $name_is_set = true;
                                break;
                            }
                        }
                    }

                    if ($name_is_set) {
                        $lang_status = $st_lang['ALREADY_SET'];
                        $update_sql = "UPDATE ". WP_LANG_TABLE . " SET status = '$lang_status'
                        WHERE ll_from_lang = '". pg_escape_string($ll_from_lang) ."' AND ll_from = '". intval($ll_from) ."' AND ll_lang = '". pg_escape_string($lang) ."'";
                        $update_res = pg_query($update_sql);
                        if($e = pg_last_error()) die($e);
                        unset($marked_arr[$marked_key]);
                    } else {
                        $tag = $osm_obj->addChild('tag');   
                        $tag->addAttribute('k', 'name:'. $lang);
                        $tag->addAttribute('v', $name_in_wp);

                        /* Render the structure back to XML */
                        $toupdate = $osm_obj->asXML();

                        if ( $toupdate ) {
                            $xml_out_text .= $toupdate . "\n";            
                            $update_count++;
                            unset($marked_arr[$marked_key]);
                            $lang_status = $st_lang['UPDATED'];
                            $update_sql = "UPDATE ". WP_LANG_TABLE . " SET status = '$lang_status'
                            WHERE ll_from_lang = '". pg_escape_string($ll_from_lang) ."' AND ll_from = '". intval($ll_from) ."' AND ll_lang = '". pg_escape_string($lang) ."'";
                            $update_res = pg_query($update_sql);
                            if($e = pg_last_error()) die($e);
                        }
                    } //if-else
                } // if ($toupdate)
                //FIXME should log if fetching object for $toupdate was unsucessful
    
                //don't overload the server
                $cycle_end = microtime(true);
                $cycle_time = $cycle_end - $cycle_end;
                //sleep 0.1 sec
                if ($cycle_time < 1) time_nanosleep(0, 100000000);
            } else {
                $log->lwrite('Row: '. $marked_row . ' not found in database.'); 
            } //if-else row
        } //if strcmp
    } //while

    if ( $update_count ) {
        $timestamp = date("Y-m-d_H:i:s");
        $osc_filename = 'OSM_'. $timestamp . '_('. $update_count . ')_name_'. $lang .'.osc';
        header ('Content-Type:text/xml');
        header('Content-Disposition: attachment; filename="'. $osc_filename .'"');
    
        $xml_out_text = '<?xml version="1.0" encoding="UTF-8"?>
<osmChange version="'. $osc_version .'" generator="'. $osc_generator .'">
  <modify version="'. $osc_version .'" generator="'. $osc_generator .'"> 
'. $xml_out_text .'
  </modify>
</osmChange>';  
        print $xml_out_text;
        $log->lwrite('Added '. $update_count . ' tags to osm.');
    }

    $_SESSION['marked'] = $marked_arr;
} else {
    print "No marked name:". $lang ." tags found.";
} //if-else
    
    
$time_end = microtime(true);
$time = $time_end - $time_start;
$log->lwrite('Ended. Runtime: '. $time);  

?>