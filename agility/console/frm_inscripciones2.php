<!-- 
frm_inscripciones2.php

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
 
<!-- este panel se divide en dos partes:
- La primera, desplegable contiene la información de la prueba y jornadas disponibles
- La segunda, contiene la lista de inscritos a la prueba y la barra de botones de gestion de inscripciones
-->

<?php
require_once(__DIR__."/../server/tools.php");
require_once(__DIR__."/../server/auth/Config.php");
require_once(__DIR__."/dialogs/dlg_perros.inc");
require_once(__DIR__."/dialogs/dlg_guias.inc");
require_once(__DIR__."/dialogs/dlg_clubes.inc");
require_once(__DIR__."/dialogs/dlg_jornadas.inc");
require_once(__DIR__."/dialogs/dlg_equipos.inc");
require_once(__DIR__."/frm_equipos.php");
require_once(__DIR__."/dialogs/dlg_newInscripcion.inc");
require_once(__DIR__."/dialogs/dlg_editInscripcion.inc");
$config =Config::getInstance();
?>
<div  style="width:100%;height:100%">

	<!-- PANEL INFORMATIVO SOBRE LA PRUEBA Y JORNADAS ASOCIADAS -->
<div id="inscripciones-infolayout" class="easyui-layout" data-options="fit:true,border:true" style="padding:10px;">
	
	<!-- PANEL IZQUIERDO: DATOS DE LA PRUEBA -->
	<div data-options="region:'west',title:'<?php _e('Contests data');?>',split:true,collapsed:false,collapsible:false"
		style="width:30%;padding:10px" class="c_inscripciones-datosprueba">
		<form id="inscripciones-pruebas" method="get" >
		<input type="hidden" name="ID"/>
		<input type="hidden" name="Club"/>
		<input type="hidden" name="Ubicacion"/>
		<input type="hidden" name="Triptico"/>
		<input type="hidden" name="Cartel"/>
		<input type="hidden" name="Cerrada"/>
		<p>
		<label for="Nombre" style="font-weight:bold"><?php _e('Title'); ?></label>
		<input id="inscripciones-pnombre" type="text" name="Nombre" disabled="disabled" size="19"/>
		</p>
		<p>
		<label for="Club" style="font-weight:bold"><?php _e('Organizing Club'); ?>:</label>
		<input id="inscripciones-pclub" type="text" name="NombreClub" disabled="disabled" size="15"/>
		</p>
		<p>
		<label for="Observaciones" style="font-weight:bold"><?php _e('Comments'); ?>:</label>
		<input id="inscripciones-pcomments" type="text" name="Observaciones" disabled="disabled" size="33"/>
		</p>
		</form>
	</div>
		
	<!-- PANEL DERECHO: LISTA DE JORNADAS -->
	<div data-options="region:'center',title:'<?php _e("Journey list for this contest"); ?>',split:true,collapsed:false,collapsible:false"
            style="width:70%;">
		<table id="inscripciones-jornadas"></table>
	</div>

    <!-- PANEL INFERIOR: LISTADO DE INSCRIPCIONES -->
    <div data-options="region:'south',title:'<?php _e("Inscription list on this contest"); ?>',split:true,collapsed:false,collapsible:false"
        style="height:80%;">
        <table id="inscripciones-datagrid"></table>
    </div>
</div>

</div>

<!-- BARRA DE TAREAS DE LA TABLA DE INSCRIPCIONES -->
<div id="inscripciones-toolbar" style="width:100%;display:inline-block">
   	<span style="float:left;padding:5px"> <!-- estos elementos deben estar alineados a la izquierda -->
   		<a id="inscripciones-newBtn" href="#" class="easyui-linkbutton"
   			data-options="iconCls:'icon-notes'"
   			onclick="newInscripcion($('#inscripciones-datagrid','#inscripciones-datagrid-search').val())"><?php _e('New'); ?></a>
   		<a id="inscripciones-editBtn" href="#" class="easyui-linkbutton"
   			data-options="iconCls:'icon-edit'"
   			onclick="editInscripcion('#inscripciones-datagrid')"><?php _e('Edit'); ?></a>
   		<a id="inscripciones-delBtn" href="#" class="easyui-linkbutton"
		   data-options="iconCls:'icon-trash'"
		   onclick="deleteInscripcion('#inscripciones-datagrid')"><?php _e('Delete'); ?></a>
		<a id="inscripciones-setBtn" href="#" class="easyui-linkbutton"
		   data-options="iconCls:'icon-order'"
		   onclick="setDorsal()"><?php _e('Set dorsal'); ?></a>
   		<input id="inscripciones-datagrid-search" type="text" value="<?php _e('-- Search --'); ?>" class="search_textfield"
			   onfocus="handleSearchBox(this,true);" onblur="handleSearchBox(this,false);"/>
   		<a id="inscripciones-reloadBtn" href="#" class="easyui-linkbutton"
           data-options="iconCls:'icon-brush'"
           onclick="
   	        	// clear selection and reload table
				reloadWithSearch('#inscripciones-datagrid','inscritos',true);
   	       " ><?php _e('Clear'); ?></a>
		<span id="inscripciones-readonly" class="blink" style="color:red">Read Only</span>
   	</span>
   	<span style="float:right;padding:5px"> 	<!-- estos elementos deben estar alineados a la derecha -->
    	<a id="inscripciones-reorderBtn" href="#" class="easyui-linkbutton"
    		data-options="iconCls:'icon-updown'" 
    		onclick="reorderInscripciones(workingData.prueba)"><?php _e('Reorder'); ?></a>
   		<a id="inscripciones-excelBtn" href="#" class="easyui-linkbutton"
		   data-options="iconCls:'icon-db_restore'"
		   onclick="importExportInscripciones()"><?php _e('Import/Export'); ?></a>
    	<a id="inscripciones-teamBtn" href="#" class="easyui-linkbutton"
    		data-options="iconCls:'icon-huella'" 
    		onclick="openTeamWindow(workingData.prueba)"><?php _e('Teams'); ?></a>
    	<a id="inscripciones-printBtn" href="#" class="easyui-linkbutton"
    		data-options="iconCls:'icon-print'" onclick="printInscripciones();"><?php _e('Print'); ?></a>
   	</span>
</div>

<script type="text/javascript">

$('#import-excelFederation').html(workingData.datosFederation.LongName);
ac_import.type='inscripciones';
$('#inscripciones-pruebas').form('load','../ajax/database/pruebaFunctions.php?Operation=getbyid&ID='+workingData.prueba);

// salto directo desde inscripciones al desarrollo de la jornada seleccionada
var menuDesarrollo;
function createMenuDesarrollo() {
    menuDesarrollo = $('<div/>').appendTo('body');
    menuDesarrollo.menu({
        align:'right',
        onClick: function(item){
            var row=$('#inscripciones-jornadas').datagrid('getSelected');
            if(row==null) return false;
            if (item.name==='ok') jumpToSelectedJourney(row);
            if (item.name==='edit') editJornadaFromPrueba(workingData.prueba,row);
        }
    });
    menuDesarrollo.menu('appendItem',{name:'edit', text: "<?php _e('Open dialog editor for selected journey')?>",iconCls:'icon-edit' });
    menuDesarrollo.menu('appendItem',{name:'ok', text: "<?php _e('Open Journey development window on selected journey')?>",iconCls:'icon-table' });
}

$('#inscripciones-jornadas').datagrid({
	// propiedades del panel asociado
	fit: true,
	border: false,
	closable: false,
	collapsible: false,
	collapsed: false,
	// propiedades especificas del datagrid
    pagination: false,
    rownumbers: false,
    fitColumns: true,
    singleSelect: true,
	url: '../ajax/database/jornadaFunctions.php',
    queryParams: { Operation: 'select', Prueba: workingData.prueba },
	method: 'get',
	loadMsg: '<?php _e('Updating journeys data');?>'+'...',
    columns:[[
            { field:'ID',			hidden:true }, // ID de la jornada
      	    { field:'Prueba',		hidden:true }, // ID de la prueba
      	    { field:'Numero',		width:9, sortable:false,	align:'center', title: '#'},
			{ field:'Nombre',		width:50, sortable:false,   align:'right',  title: '<?php _e('Name/Comment');?>',formatter:formatBold },
			{ field:'Fecha',		width:25, sortable:false,	align:'right',  title: '<?php _e('Date');?>: ' },
			{ field:'Hora',			width:20, sortable:false,	align:'right',  title: '<?php _e('Hour');?>:  ' },
			{ field:'Grado1',		width:12, sortable:false, formatter:formatGrado1 ,	align:'center', title: '<?php _e('G-I   ');?>' },
			{ field:'Grado2',		width:12, sortable:false, formatter: formatOk,	align:'center', title: '<?php _e('G-II  ');?>' },
			{ field:'Grado3',		width:12, sortable:false, formatter: formatOk,	align:'center', title: '<?php _e('G-III ');?>' },
			{ field:'Equipos3',		width:15, sortable:false, formatter: formatTeamDogs,align:'center', title: '<?php _e('Teams ');?>' },
			{ field:'Equipos4',		hidden:true }, // since 4.2.x Equipos3/Equipos4 becomes minDogs/maxDogs
			{ field:'Open',			width:12, sortable:false, formatter: formatOk,	align:'center', title: '<?php _e('Open  ');?>' },
			{ field:'PreAgility',	width:12, sortable:false, formatter: formatPreAgility,	align:'center', title: '<?php _e('PreAg.');?>' },
            { field:'Children',	    width:12, sortable:false, formatter: formatOk,	align:'center', title: '<?php _e('Children');?>' },
            { field:'Junior',	    width:12, sortable:false, formatter: formatOk,	align:'center', title: '<?php _e('Junior');?>' },
            { field:'Senior',	    width:12, sortable:false, formatter: formatOk,	align:'center', title: '<?php _e('Senior');?>' },
            { field:'ParaAgility',  width:12, sortable:false, formatter: formatOk,	align:'center', title: '<?php _e('ParaAgility');?>' },
            { field:'KO',			width:12, sortable:false, formatter: formatOk,	align:'center', title: '<?php _e('K.O.  ');?>' },
            { field:'Games',		width:12, sortable:false, formatter: formatOk,	align:'center', title: '<?php _e('Games ');?>' },
			{ field:'Especial',		width:12, sortable:false, formatter: formatOk,	align:'center', title: '<?php _e('Special');?>' },
			{ field:'Cerrada',		width:9, sortable:false, formatter: formatCerrada,	align:'center', title: '<?php _e('Closed');?>' }
    ]],
    rowStyler:myRowStyler,
	// on click mark as active; on double click fireup editor dialog
	onClickRow: function(idx,row) { setJornada(row); },	
	onDblClickRow:function(idx,row) { //idx: selected row index; row selected row data
		setJornada(row);
    	editJornadaFromPrueba(workingData.prueba,row);
	},
    onRowContextMenu: function(e,index,row){
	    e.preventDefault();
	    $('#inscripciones-jornadas').datagrid('selectRow',index);
        if (!menuDesarrollo)  createMenuDesarrollo();
        menuDesarrollo.menu('show', {
            left:e.pageX,
            top:e.pageY+15
        });
    },
    onLoadSuccess:function(data) {
	    var count=0;
	    for (var n=0; n<8 ;n++) {
	        if( (data.rows[n].Nombre==="-- Sin asignar --") || (parseInt(data.rows[n].Cerrada)===1) ) count++;
        }
        if (count===8) {
	        var msg1="<?php _e('There are no valid nor open journeys to inscribe into');?>";
            var msg2="<?php _e('New inscriptions will have no effect');?>";
            var msg3="<?php _e('Please edit Journey list to create/define valid entries');?>";
	        $.messager.alert({
                title:  "<?php _e('Notice');?>",
                msg: msg1+"<br/>"+msg2+"<br/>&nbsp;<br/>"+msg3,
                icon: 'warning',
                width: 450
            });
        } else {
            workingData.datosPrueba.jornadas=data['rows'];
            for (i=1;i<9;i++) {
                $('#idg_j'+i).tooltip('update',workingData.datosPrueba.jornadas[i-1].Nombre);
            }
        }
    }

});

var menuJornadas;
function createMenuJornadas(){
    menuJornadas = $('<div/>').appendTo('body');
    menuJornadas.menu({
        current:0, // added by JAMC
        align:'right',
        onClick: function(item){
            var current=menuJornadas.menu('options').current;
            if (item.name==='clear') clearJourneyInscriptions(current);
            if (item.name==='all')   inscribeAllIntoJourney(current);
            if (item.name==='journey') inscribeSelectedIntoJourney(current);
        }
    });
    menuJornadas.menu('appendItem',{name:'clear', text: "<?php _e('Clear all inscriptions on this journey')?>",iconCls:'icon-cut' });
    menuJornadas.menu('appendItem',{name:'all', text: "<?php _e('Register all inscriptions into this journey')?>",iconCls:'icon-notes' });
    menuJornadas.menu('appendItem',{name:'journey', text: "<?php _e('Clone inscriptions on selected journey into this one')?>",iconCls:'icon-tip' });
}

// datos de la tabla de inscripciones
// - tabla
$('#inscripciones-datagrid').datagrid({
	// propiedades del panel asociado
	fit: true,
	border: false,
	closable: false,
	collapsible: false,
    expansible: false,
	collapsed: false,
	// propiedades especificas del datagrid
    pagination: false,
    rownumbers: false,
    fitColumns: true,
    singleSelect: true,
    view: scrollview,
    pageSize: 50,
    multiSort: true,
    remoteSort: true,
	idField: 'ID',
	url: '../ajax/database/inscripcionFunctions.php',
    queryParams: { Operation: 'inscritos', Prueba: workingData.prueba },
	method: 'get',
	loadMsg: '<?php _e('Updating inscriptions data');?>'+'....',
    toolbar: '#inscripciones-toolbar',
    columns: [[
        { field:'ID',		hidden:true }, // inscripcion ID
        { field:'Prueba',	hidden:true }, // prueba ID
        { field:'Jornadas',	hidden:true }, // bitmask de jornadas inscritas
        { field:'Perro',	hidden:true }, // dog ID
        { field:'Pagado', 	hidden:true }, // to store if handler paid :-)
        { field:'Guia', 	hidden:true }, // Guia ID
        { field:'Club',		hidden:true }, // Club ID
        { field:'LOE_RRC',	hidden:true }, // LOE/RRC
        { field:'Club',		hidden:true }, // Club ID
    	{ field:'Dorsal',	width:6,  sortable:true, align: 'right',	title: '<?php _e('Dorsal');?>' },
        { field:'Nombre',	width:15, sortable:true, align: 'right',	title: '<?php _e('Name');?>',formatter:formatBold },
        { field:'Licencia',	width:8, sortable:true, align: 'center',	title: '<?php _e('Lic');?>' },
    	{ field:'Categoria',width:4,  sortable:true, align: 'center',  	title: '<?php _e('Cat');?>',formatter:formatCategoria },
    	{ field:'Grado',	width:5,  sortable:true, align: 'center',  	title: '<?php _e('Grade');?>', formatter:formatGrado },
        { field:'NombreGuia',width:22, sortable:true, align: 'right',	title: '<?php _e('Handler');?>' },
        { field:'CatGuia',	width:6, sortable:true, align: 'right',	title: '<?php _e('Cat');?>',formatter:formatCatGuia },
    	{ field:'NombreClub',	width:13, sortable:true, align: 'right',	title: '<?php _e('Club');?>' },
        { field:'Celo',		width:3, align:'center', formatter: formatCelo,	 title: '<?php _e('Heat');?>' },
        { field:'NC',	    width:3, align:'center', formatter: formatNC,	 title: '<?php _e('N.C.');?>' },
        { field:'J1',		width:2, align:'center', formatter: formatJ1,
            title: '<span id="idg_j1" class="easyui-tooltip" title="' + workingData.datosPrueba.jornadas[0].Nombre + '">J1</span>'},
        { field:'J2',		width:2, align:'center', formatter: formatJ2,
            title: '<span id="idg_j2" class="easyui-tooltip" title="' + workingData.datosPrueba.jornadas[1].Nombre + '">J2</span>'},
        { field:'J3',		width:2, align:'center', formatter: formatJ3,
            title: '<span id="idg_j3" class="easyui-tooltip" title="' + workingData.datosPrueba.jornadas[2].Nombre + '">J3</span>'},
        { field:'J4',		width:2, align:'center', formatter: formatJ4,
            title: '<span id="idg_j4" class="easyui-tooltip" title="' + workingData.datosPrueba.jornadas[3].Nombre + '">J4</span>'},
        { field:'J5',		width:2, align:'center', formatter: formatJ5,
            title: '<span id="idg_j5" class="easyui-tooltip" title="' + workingData.datosPrueba.jornadas[4].Nombre + '">J5</span>'},
        { field:'J6',		width:2, align:'center', formatter: formatJ6,
            title: '<span id="idg_j6" class="easyui-tooltip" title="' + workingData.datosPrueba.jornadas[5].Nombre + '">J6</span>'},
        { field:'J7',		width:2, align:'center', formatter: formatJ7,
            title: '<span id="idg_j7" class="easyui-tooltip" title="' + workingData.datosPrueba.jornadas[6].Nombre + '">J7</span>'},
        { field:'J8',		width:2, align:'center', formatter: formatJ8,
            title: '<span id="idg_j8" class="easyui-tooltip" title="' + workingData.datosPrueba.jornadas[7].Nombre + '">J8</span>'},
    ]],
    // colorize rows. notice that overrides default css, so need to specify proper values on datagrid.css
    rowStyler:myRowStyler,
	// on double click fireup editor dialog
    onDblClickRow:function() { 
        editInscripcion();
    },
    onHeaderContextMenu: function(e, field){
	    if ( ['J1','J2','J3','J4','J5','J6','J7','J8'].indexOf(field) <0) return true;
	    var index=parseInt(field.substr(1,1))-1;
        e.preventDefault();
        if (!menuJornadas){
            createMenuJornadas();
        }
        menuJornadas.menu('options').current=index;
        menuJornadas.menu('show', {
            left:e.pageX-375,
            top:e.pageY+15
        });
    }
});

// set visibility of read-only warning
$('#inscripciones-readonly').css('display',check_softLevel(access_level.PERMS_OPERATOR,null)?'none':'inline-block');

// key handler
addSimpleKeyHandler('#inscripciones-jornadas',"",editJornadaFromPrueba);
addKeyHandler('#inscripciones-datagrid',"",newInscripcion,editInscripcion,deleteInscripcion);

// tooltips
addTooltip($('#inscripciones-newBtn').linkbutton(),'<?php _e("Register new inscriptions");?>');
addTooltip($('#inscripciones-editBtn').linkbutton(),'<?php _e("Modify selected inscription");?>');
addTooltip($('#inscripciones-delBtn').linkbutton(),'<?php _e("Remove selected inscription from database");?>');
addTooltip($('#inscripciones-setBtn').linkbutton(),'<?php _e("Change dorsal number for selected inscription");?>');
addTooltip($('#inscripciones-excelBtn').linkbutton(),'<?php _e("Import/Export Inscriptions from/to Excel file"); ?>');
addTooltip($('#inscripciones-reorderBtn').linkbutton(),'<?php _e("Reassign dorsals according current ordering criteria");?>');
addTooltip($('#inscripciones-teamBtn').linkbutton(),'<?php _e("Open Team handling window for selected journey");?>');
addTooltip($('#inscripciones-printBtn').linkbutton(),'<?php _e("Print inscriptions list on this contest");?>');
addTooltip($('#inscripciones-reloadBtn').linkbutton(),'<?php _e("Clear search box<br/>Update inscriptions list");?>');
addTooltip($('#inscripciones-datagrid-search'),'<?php _e("Search inscriptions matching search criteria");?>');

addTooltip($('#inscripciones-excel-okBtn').linkbutton(),'<?php _e("Import Inscriptions data from selected Excel file"); ?>');
addTooltip($('#inscripciones-excel-cancelBtn').linkbutton(),'<?php _e("Cancel operation. Close window"); ?>');
</script>