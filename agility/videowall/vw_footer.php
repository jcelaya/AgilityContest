<?php
    require_once(__DIR__."/../server/auth/Config.php");
    $config = Config::getInstance();
/*
vw_footer.php

Copyright  2013-2018 by Juan Antonio Martinez ( juansgaviota at gmail dot com )

This program is free software; you can redistribute it and/or modify it under the terms
of the GNU General Public License as published by the Free Software Foundation;
either version 2 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program;
if not, write to the Free Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
*/
/* File used to insert logo, supporters,  head lines and so */
?>
<div id="vw_footer">
    <span style="float:left">
        <a id="vw_footer-urlFederation" target="fed" href="" style="border:0 none;">
            <img id="vw_footer-logoFederation" src="../images/logos/agilitycontest.png" alt="" width="40"/>
        </a>
        <a id="vw_footer-urlFederation2" target="fed2" href="">
            <img id="vw_footer-logoFederation2" src="../images/logos/agilitycontest.png" alt="" width="40"/>
        </a>
        <span style="display:inline-block;padding:12px;font-size:0.6vw;font-style:oblique">
            AgilityContest-<?php echo $config->getEnv('version_name'); ?> <br/>&copy; 2013-2018 JAMC
        </span>
    </span>
    <span style="float:right">
    <table><tr><td>
<?php
/* el fichero "supporters,csv" tiene el formato CSV: "patrocinador":"logo":"url"[:"categoria"] */
$file=fopen(__DIR__ . "/../../config/supporters.csv","r");
if ($file) {
    $odd=false;

    while (($datos = fgetcsv($file, 0, ':','"')) !== FALSE) {
        $nitems=count($datos);
        if ($nitems<3) continue; // invalid format
        $cat=($nitems==3)?"bronze":strtolower($datos[3]); // "gold","silver","bronze"
        $height=10;
        if ($cat=="gold") $height=40;
        if ($cat=="silver") $height=20;
        if ($cat=="bronze") continue;
        echo '<a  target="'.$datos[0].'" href="'.$datos[2].'">';
        echo '<img id="vw_footer-'.$datos[0].'" src="../images/supporters/'.$datos[1].'" alt="'.$cat." ".$datos[0].'" height="'.$height.'"/>';
        echo '</a>';
        if (($odd==false) && ($height==20)) { echo "<br/>"; $odd=true; }
        else { echo "</td><td>"; $odd=false;}
    }
    if ($odd==true)  echo "</td><td>"; // take care on even ends
    fclose($file); // this also removes temporary file
}
?>
        <!-- El logo de y URL de la aplicación siempre esta presente :-) -->
        <a target="acontest" href="https://www.github.com/jonsito/AgilityContest">
            <img id="vw_footer-logoAgilityContest" src="../images/supporters/agilitycontest.png" alt="agilitycontest" height="40"/>
        </a>
    </td></tr></table>
    </span>
</div>