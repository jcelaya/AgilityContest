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
<div id="pb_ordensalida-window">
	<div id="pb_ordensalida-layout" style="width:100%">
		<div id="pb_ordensalida-Cabecera" style="height:15%;" class="pb_floatingheader"
             data-options="
                region:'north',
                split:true,
                title:'<?php _e('Round selection');?>',
                collapsed:false,
                onCollapse:function(){
                	setTimeout(function(){
				    	var top = $('#pb_ordensalida-layout').layout('panel','expandNorth');
				    	var round = $('#pb_enumerateMangas').combogrid('getText');
					    top.panel('setTitle','<?php _e('Starting order');?>: '+round);
				    },0);
                }
                ">
            <a id="pb_header-link" class="easyui-linkbutton" onClick="pb_updateOrdenSalida();" href="#" style="float:left">
                <img id="pb_header-logo" src="../images/logos/agilitycontest.png" width="50" />
            </a>
		    <span style="float:left;padding:10px;" id="pb_header-infocabecera"><?php _e('Header'); ?></span>
			<span style="float:right;" id="pb_header-texto">
                <?php _e('Starting order'); ?><br />
                <label for="pb_enumerateMangas">&nbsp;</label>
                <select id="pb_enumerateMangas" style="width:200px"></select>
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

// in a mobile device, increase north window height
if (isMobileDevice()) {
    $('#pb_ordensalida-Cabecera').css('height','90%');
}

addTooltip($('#pb_header-link').linkbutton(),'<?php _e("Update starting order"); ?>');
$('#pb_ordensalida-layout').layout({fit:true});

$('#pb_ordensalida-window').window({
    fit:true,
    noheader:true,
    border:false,
    closable:false,
    collapsible:false,
    collapsed:false,
    resizable:true,
    callback: null,
    // 1 minute poll is enouth for this, as no expected changes during a session
    onOpen: function() {
        // update header
        pb_getHeaderInfo();
        // update footer
        pb_setFooterInfo();
    }
});

$('#pb_enumerateMangas').combogrid({
    panelWidth: 350,
    panelHeight: 150,
    idField: 'ID',
    textField: 'Nombre',
    method: 'get',
    url: '../ajax/database/tandasFunctions.php',
    queryParams: {
        Operation: 'getTandas',
        Prueba: workingData.prueba,
        Jornada: workingData.jornada,
        Sesion: 1 // show only non user defined tandas
    },
    required: true,
    multiple: false,
    fitColumns: true,
    singleSelect: true,
    editable: false,  // to disable tablet keyboard popup
    selectOnNavigation: true, // let use cursor keys to interactive select
    columns:[[
        { field:'ID',		hidden:true },
        { field:'Sesion',	hidden:true },
        { field:'Prueba',	hidden:true },
        { field:'Jornada',	hidden:true },
        { field:'Manga',	hidden:true },
        { field:'Categoria',hidden:true },
        { field:'Grado',	hidden:true },
        { field:'Sesion',	hidden:true },
        { field:'Tipo',	    hidden:true },
        { field:'Horario',	hidden:true },
        { field:'Nombre',	width:150, sortable:false, align:'left',title:'<?php _e('List of rounds in this journey'); ?>'},
        { field:'Comentario', hidden:true}
    ]],
    rowStyler: pbRowStyler,
    onSelect: function(index,row) {
        $('#ordensalida-datagrid').datagrid('reload',{
            Operation: 'getDataByTanda',
            Prueba: workingData.prueba,
            Jornada: workingData.jornada,
            Sesion: 1, // defaults to "-- sin asignar --"
            ID:  row.ID // Tanda ID
        });
        $('#pb_ordensalida-layout').layout('collapse','north');
    }
});

$('#ordensalida-datagrid').datagrid({
    queryParams: {
        Operation: 'getDataByTanda',
        Prueba: workingData.prueba,
        Jornada: workingData.jornada,
        Sesion: 1, // defaults to "-- sin asignar --"
        ID: 0 // Tanda 0 defaults to every tandas
    },
    onBeforeLoad:function(param) {
        var row=$('#pb_enumerateMangas').combogrid('grid').datagrid('getSelected');
        if (!row) return false;
        return true;
    }
});

</script>