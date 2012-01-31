<?php
// bad_wikipedia_tags.php
// 
// show malformed wikipedia tags
//

error_reporting(E_ALL);
ini_set('display_errors', true);
ini_set('html_errors', false);

require('/home/kentaur/php/osm_wp/common.php');

header('Content-Type: text/html; charset=utf-8');

?>
<html>
	<head>
		<title>List of bad wikipedia tags</title>
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


$wiki_site = '.wikipedia.org/wiki/';

// open psql connection
$pg = pg_connect('host='. OSM_HOST .' dbname='. OSM_DB);
 
// check for connection error
if($e = pg_last_error()) trigger_error($e, E_USER_ERROR);

$sql_total = "SELECT count(*)
   FROM ". OSM_WP_TABLE ." 
   WHERE status = ". $status_arr['NOT_FOUND'] ." 
     OR  status = ". $status_arr['UNKNOWN_WIKI'];
   
    $res_total = pg_query($sql_total);
    if ($row = pg_fetch_assoc($res_total)) {
        $total_rows = $row['count'];
    }

$sql = "SELECT osm_table, osm_id, osm_wikipedia, wiki_lang, wiki_art_title
   FROM ". OSM_WP_TABLE ." 
   WHERE status = ". $status_arr['NOT_FOUND'] ." 
     OR  status = ". $status_arr['UNKNOWN_WIKI'] ."
   LIMIT 1500";

$res = pg_query($sql);
    
// check for query error
if($e = pg_last_error()) trigger_error($e, E_USER_ERROR);

$showing_rows = pg_num_rows($res);
print "<P>Showing $showing_rows rows from $total_rows.</P>";

print "<TABLE>\n";
    print "<TR>
  <TH>OSM link</TH>
  <TH>edit</TH>
  <TH></TH>
  <TH>malformed wikipedia tag/page not found in wikipedia</TH>
</TR>";

while($row = pg_fetch_assoc($res))
{
    $obj_type = '';
        if ($row['osm_id'] < 0) {
            $osm_url = 'http://www.openstreetmap.org/browse/relation/';
            $obj_type = 'relation';
        } else {
            switch ($row['osm_table']) {
                case "planet_point":
                    $osm_url = 'http://www.openstreetmap.org/browse/node/';
                    $obj_type = 'node';
                    break;
                case "planet_line":
                    $osm_url = 'http://www.openstreetmap.org/browse/way/';
                    $obj_type = 'way';
                    break;
                case "planet_polygon":
                    $osm_url = 'http://www.openstreetmap.org/browse/way/';
                    $obj_type = 'way';
                    break;
            }
        } //if
        $wiki_url = 'http://'. $row['wiki_lang'] . $wiki_site . $row['wiki_art_title'];
    $edit_url = 'http://www.openstreetmap.org/edit?'. $obj_type . '=' . $row['osm_id'];

    print '<TR>';
        print '<TD><A HREF="'. $osm_url . abs($row['osm_id']) . '">'. $row['osm_id'] ."</A></TD>\n";
        print '<TD><A HREF="'. $edit_url . '">edit</A></TD>' . "\n";
        print "<TD>". $row['wiki_lang'] ."</TD>\n";
        print '<TD> <A HREF="'. $wiki_url . '">' . $row['osm_wikipedia'] ."</A> </TD>\n";
    print "</TR>\n";
    

}
print "</TABLE>\n";
print "</body>\n</html>";
?>