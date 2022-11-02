<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
require_once(__DIR__."/../server/tools.php");
require_once(__DIR__."/../server/auth/Config.php");
require_once(__DIR__."/../server/auth/AuthManager.php");
$config =Config::getInstance();
?>
<!--
pb_ordensalida.inc

Copyright  2013-2018 by Juan Antonio Martinez ( juansgaviota at gmail dot com )

This program is free software; you can redistribute it and/or modify it under the terms 
of the GNU General Public License as published by the Free Software Foundation; 
either version 2 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; 
without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. 
See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program; 
if not, write to the Free Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 -->

<!-- Presentacion del orden de salida de la jornada -->
<div id="pb_ordensalida-panel">
	<div id="pb_ordensalida-layout" style="width:100%">
		<div id="pb_ordensalida-Cabecera" style="height:10%;" class="pb_floatingheader"
             data-options="region:'north',split:false,collapsed:false">
            <a id="pb_back-link" class="easyui-linkbutton" onClick="pbmenu_expandMenu(true);" href="#" style="float:left">
                <img id="pb_back-logo" src="../images/backtomenu.png" width="50" />
            </a>&nbsp;
            <a id="pb_header-link" class="easyui-linkbutton" onClick="pb_updateOrdenSalida2(workingData.tanda);" href="#" style="float:left">
                <img id="pb_header-logo" src="../images/logos/agilitycontest.png" width="50" />
            </a>
		    <span style="float:left;padding:10px;" id="pb_header-infocabecera"><?php _e('Header'); ?></span>
			<span style="float:right;" id="pb_header-texto">
                <?php _e('Starting order'); ?><br />
                <span id="pb_enumerateMangas" style="width:200px" >Nombre Manga</span>
            </span>
		</div>
		<div id="team_table" data-options="region:'center'">
            <?php include_once(__DIR__ . "/../console/templates/orden_salida.inc.php");?>
		</div>
        <div id="pb_ordensalida-footer" data-options="region:'south',split:false" style="height:10%;" class="pb_floatingfooter">
            <span id="pb_footer-footerData"></span>
        </div>
	</div>
</div> <!-- pb_ordensalida-window -->

<script type="text/javascript">

addTooltip($('#pb_header-link').linkbutton(),'<?php _e("Update starting order"); ?>');
addTooltip($('#pb_back-link').linkbutton(),'<?php _e("Back to contest menu"); ?>');
$('#pb_ordensalida-layout').layout({fit:true});

$('#pb_ordensalida-panel').panel({
    fit:true,
    noheader:true,
    border:false,
    closable:false,
    collapsible:false,
    collapsed:false,
    resizable:true,
    callback: null
});

$('#ordensalida-datagrid').datagrid({
    queryParams: {
        Operation: 'getDataByTanda',
        Prueba: workingData.prueba,
        Jornada: workingData.jornada,
        Sesion: 1, // defaults to "-- sin asignar --"
        ID: workingData.tanda // Tanda 0 defaults to every tandas
    },
    onBeforeLoad:function(param) {
        if (workingData.tanda==0) return false; // do not try to load if not variable initialized
        return true;
    }
});

// fire autorefresh if configured
function pbmenu_ordenSalida_timeoutHandler() {
    // check for request to stop
    var rtime=parseInt(ac_config.web_refreshtime);
    if ((rtime===0) || (pb_config.Timeout===null)) return;
    // refresh data
    pb_updateOrdenSalida2(workingData.tanda);
    // re-trigger event
    pb_config.Timeout=setTimeout(pbmenu_ordenSalida_timeoutHandler,1000*rtime);
}
// update header title
setTimeout( function(){
    pb_getHeaderInfo();
    pb_setFooterInfo();
    $('#pb_enumerateMangas').text(workingData.nombreTanda);
},0);
// and fire up refresh
if (pb_config.Timeout==="readyToRun")  pbmenu_ordenSalida_timeoutHandler();

</script>