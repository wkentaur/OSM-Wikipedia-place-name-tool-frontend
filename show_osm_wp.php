<?php
// show_osm_wp.php
// 
// show OSM_WP_TABLE
//

error_reporting(E_ALL);
ini_set('display_errors', true);
ini_set('html_errors', false);

require('/home/kentaur/php/osm_wp/common.php');

// functions
function h($s) { return htmlspecialchars($s); }


//main
$wiki_site = '.wikipedia.org/wiki/';

session_start();
header('Content-Type: text/html; charset=utf-8');

$lang = isset($_GET['lang']) ? $_GET['lang'] : null;

?>
<html>
	<head>
		<title>List of proposed name:<? echo $lang; ?> values</title>
		<meta http-equiv="Content-Type" value="text/html; charset=utf-8" />
		<meta name="robots" content="noindex,nofollow" />
		<style type="text/css">
			tr {
				border-top: 1px solid black;
				border-bottom: 1px solid black;
			}
			td, th {
				padding: 3px 10px;
                text-align:left;
			}
			table {
				border-collapse: collapse;
			}
			
			tr.ok {
				background-color: darkgreen;
			}
			tr.ok td, tr.ok a {
				color: white;
			}
			
			td.action a {
				padding: 0 2px;
				margin-right: 2px;
				text-decoration: none;
				border: 1px solid #808080;
				background: #E0E0E0;
				color: black;
			}
			
		</style>
	</head>
    <body>
<?

// open psql connection
$pg = pg_connect('host='. OSM_HOST .' dbname='. OSM_DB);
 
// check for connection error
if($e = pg_last_error()) trigger_error($e, E_USER_ERROR);

if ($lang) {
    if ( preg_match('/[\w\-]+/', $lang, $matches) ) {
        $lang = $matches[0];
    } else {
        die('Invalid $lang value!');
    }
    $marked_arr = array();
    
    if ( isset($_SESSION['marked_all'])  and isset($_SESSION['marked_all']["$lang"])) {
        $marked_arr = $_SESSION['marked_all']["$lang"];
    }

  
    if(isset($_GET['action'])) switch($_GET['action']) {
        case 'mark-ok':
            $mark_ok = $_GET['ll_from_lang'] . '|' . $_GET['ll_from'] . '|' . $lang;
            $marked_arr[] = $mark_ok;
            break;
        
        case 'mark-nok':
            $remove_mark = $_GET['ll_from_lang'] . '|' . $_GET['ll_from'] . '|' . $lang;
            if (in_array($remove_mark, $marked_arr)) {
                foreach($marked_arr as $key => $value) {
                    if ($value == $remove_mark) unset($marked_arr[$key]);
                }            
            }
            break;
    }
    $esc_lang = pg_escape_string($lang);

    $sql_from_where = " FROM ". OSM_WP_TABLE .", ". WP_LANG_TABLE . " 
   WHERE (". OSM_WP_TABLE .".wiki_lang = ". WP_LANG_TABLE . ".ll_from_lang AND 
         ". OSM_WP_TABLE .".wiki_page_id = ". WP_LANG_TABLE . ".ll_from AND
         ". WP_LANG_TABLE . ".ll_lang = '$esc_lang' AND
         ". WP_LANG_TABLE . ".status = ". $st_lang['TO_CHECK'] ." ) ";
        
    $sql_total = "SELECT count(*) " . $sql_from_where;
    $sql_lim = "SELECT osm_table, osm_id, osm_wikipedia, wiki_lang, wiki_art_title, wiki_page_id, ". WP_LANG_TABLE . ".status, ll_title " . $sql_from_where . " LIMIT 300";

    // query the database
    $res_total = pg_query($sql_total);
    if ($row = pg_fetch_assoc($res_total)) {
        $total_rows = $row['count'];
    }

    $res = pg_query($sql_lim);
    
    // check for query error
    if($e = pg_last_error()) trigger_error($e, E_USER_ERROR);

    $showing_rows = pg_num_rows($res);

    print "<P>Showing $showing_rows from $total_rows for $lang. ";
    print '<A HREF ="'. $_SERVER['PHP_SELF'] . '">Back to all languages.</A></P>';

    if ( preg_match('@^(.+)/@i',
       $_SERVER['REQUEST_URI'], $matches) ) {
       $download_uri = $matches[1] . '/'. DOWNLOAD_OSC_FILE . '?lang='. $lang . '&amp;'. h(SID);
    }

    $download_osc = '<P>1) Mark suitable names OK.</P>';
    $download_osc .= '<P>2) <A HREF="'. $download_uri .'">Download osmChange (.osc) file with marked name:' . $lang . ' tags.</A> ';
    if ( count($marked_arr) ) {
        $download_osc .= count($marked_arr) . ' names marked.';
    }
    $download_osc .= '</P>';
    $download_osc .= '<P>3) After that open .osc file in <A HREF="http://josm.openstreetmap.de/">JOSM</A> and upload changes to Openstreetmap.</P>';

    print $download_osc;

    print "<TABLE>\n";
    print "<TR>
  <TH>OSM link</TH>
  <TH></TH>
  <TH>Wikipedia article</TH>
  <TH>OSM name</TH>
  <TH>current name:$lang</TH>
  <TH>proposed name:$lang</TH>
  <TH></TH>
</TR>
    ";

    while($row = pg_fetch_assoc($res))
    {
        if ($row['osm_id'] < 0) {
            $osm_url = 'http://www.openstreetmap.org/browse/relation/';
            $anchor = 'r';
        } else {
            switch ($row['osm_table']) {
                case "planet_point":
                    $osm_url = 'http://www.openstreetmap.org/browse/node/';
                    $anchor = 'n';
                    break;
                case "planet_line":
                    $osm_url = 'http://www.openstreetmap.org/browse/way/';
                    $anchor = 'w';
                    break;
                case "planet_polygon":
                    $osm_url = 'http://www.openstreetmap.org/browse/way/';
                    $anchor = 'w';
                    break;
            }
        } //if
        $anchor = $anchor . abs($row['osm_id']);    
        $lang_row_key = $row['wiki_lang'] . '|' . $row['wiki_page_id'] . '|' . $lang;

        $wiki_url = 'http://'. $row['wiki_lang'] . $wiki_site . $row['wiki_art_title'];
        $ok = in_array($lang_row_key, $marked_arr);
        $row_class = $ok ? 'ok' : 'nok';
        print '<TR id="'. $anchor .'" class="' . $row_class . '">';
        print '<TD><A HREF="'. $osm_url . abs($row['osm_id']) . '">'. $row['osm_id'] ."</A></TD>\n";
        print "<TD>". $row['wiki_lang'] ."</TD>\n";
        print '<TD> <A HREF="'. $wiki_url . '">' . $row['wiki_art_title'] ."</A> </TD>\n";

        $name_field = "tags->'name:" . $lang . "' AS ". $lang;
        $osm_table = $row['osm_table'];
        $osm_id = $row['osm_id'];
        $osm_sql = "SELECT  name, tags->'route_name' AS route_name, $name_field
        FROM $osm_table 
        WHERE (osm_id = '$osm_id')
        LIMIT 1";

        $name_in_wp = strip_wp_title($row['ll_title'], $lang);
        
        $osm_res = pg_query($osm_sql);
        if($e = pg_last_error()) trigger_error($e, E_USER_ERROR);
        if ( pg_num_rows($osm_res) ) {
            while($osm_row = pg_fetch_assoc($osm_res))
            {
                if ($osm_row['name']) {
                    $osm_name = $osm_row['name'];
                } else {
                    $osm_name = $osm_row['route_name'];
                }            
                print "<TD>". $osm_name ."</TD>\n";
                print "<TD>". $osm_row["$lang"] ."</TD>\n";
                if (strcmp($name_in_wp, $osm_row["$lang"]) == 0) {
                    $lang_status = $st_lang['IS_IN_OSM'];
                    $update_sql = "UPDATE ". WP_LANG_TABLE . " SET status = '$lang_status'
                    WHERE ll_from_lang = '". pg_escape_string($row['wiki_lang']) ."' AND ll_from = '". $row['wiki_page_id'] ."' AND ll_lang = '$esc_lang'";
                    $update_res = pg_query($update_sql);
                    if($e = pg_last_error()) trigger_error($e, E_USER_ERROR);                
                }
            }
        } else {
            print "<TD></TD>\n";
            print "<TD></TD>\n";
        }
        print "<TD><strong>". $name_in_wp ."</strong></TD>\n";
        //print "<TD>". $row['status'] ."</TD>";
    
        //FIXME: get rid of onClick        
?>
    <TD>
      <a href="?lang=<?=h($lang)?>&amp;ll_from_lang=<?=h($row['wiki_lang'])?>&amp;ll_from=<?=h($row['wiki_page_id'])?>&amp;action=mark-<?=$ok ? 'nok' : 'ok'?>&amp;<?=h(SID)?>#<?=h($anchor)?>" onclick="return mark(this, <?=h($osm_id)?>, '<?=$ok ? 'nok' : 'ok'?>')">mark <?=$ok ? 'not OK' : 'OK'?></a>
    </TD>      
<?      
        print "</TR>\n";
    } //while

    print "</TABLE>\n";

    print $download_osc;
    //write back to session array
    $_SESSION['marked_all']["$lang"] = $marked_arr;
    
} else {
    print "<P>Semi-automatically add new name:XX values to Openstreetmap using wikipedia links from OSM and interlanguage links from Wikipedias.</P>";
    $sql = "SELECT ll_lang, count(*)
    FROM ". OSM_WP_TABLE .", ". WP_LANG_TABLE . " 
   WHERE (". OSM_WP_TABLE .".wiki_lang = ". WP_LANG_TABLE . ".ll_from_lang AND 
         ". OSM_WP_TABLE .".wiki_page_id = ". WP_LANG_TABLE . ".ll_from AND
         ". WP_LANG_TABLE . ".status = ". $st_lang['TO_CHECK'] ." )
    GROUP BY ll_lang
    ORDER BY ll_lang";

    // query the database
    $res = pg_query($sql);
    // check for query error
    if($e = pg_last_error()) trigger_error($e, E_USER_ERROR);   

    print "<UL>\n";
    while ($row = pg_fetch_assoc($res)) {
        $lang_uri = $_SERVER['PHP_SELF'] . '?lang=' . $row['ll_lang'];
        print '<LI><A HREF="'. $lang_uri .'">'. $row['ll_lang'] . " -- " . $row['count'] . " proposed name:". $row['ll_lang'] . " values</A></LI>\n";
    }
    print "</UL>\n";

} //if-else $lang

print "</body>\n</html>";
?>