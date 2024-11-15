<!-- 
dlg_selectJornada.inc

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

<?php
require_once(__DIR__."/../../server/auth/Config.php");
require_once(__DIR__."/../../server/tools.php");
$config =Config::getInstance();
?>

<!-- FORMULARIO DE SELECCION DE PRUEBAS ABIERTAS-->
<div id="seljornada-window" style="position:relative;width:500px;height:auto;padding:20px 20px">
	<div id="seljornada-Layout" class="easyui-layout" data-options="fit:'true'">
		<form id="seljornada-Selection">
			<div id="seljornada-Content" data-options="region:'north',border:'true'">
        		<div class="fitem">
            		<label for="Prueba"><?php _e('Select contest'); ?>:</label>
            		<select id="seljornada-Prueba" name="Prueba" style="width:200px"></select>
        		</div>        		
        		<div class="fitem">
            		<label for="Jornada"><?php _e('Select journey'); ?>:</label>
            		<select id="seljornada-Jornada" name="Jornada" style="width:200px"></select>
        		</div>
			</div> <!-- contenido -->
		</form>
		<div data-options="region:'center'"></div>
		<div id="seljornada-Buttons" data-options="region:'south',border:false" style="text-align:right;padding:5px 0 0;">
            <a id="seljornada-cancelBtn" href="#" class="easyui-linkbutton"
               data-options="iconCls: 'icon-cancel'" onclick="cancelSelectJornada()"><?php _e('Cancel'); ?></a>
    	    <a id="seljornada-okBtn" href="#" class="easyui-linkbutton" 
    	    	data-options="iconCls: 'icon-ok'" onclick="acceptSelectJornada()"><?php _e('Accept'); ?></a>
		</div>	<!-- botones -->
	</div> <!-- Layout -->
</div> <!-- Window -->

<script type="text/javascript">
$('#seljornada-window').window({
	title: "<?php _e('Select contest and journey to work with'); ?>",
	collapsible: false,
	minimizable: false,
	maximizable: false,
	closable: false,
	closed: true,
	shadow: false,
	modal: true
});

$('#seljornada-form').form();
$('#seljornada-layout').layout();

addTooltip($('#seljornada-okBtn').linkbutton(),'<?php _e("Update results on selected round/category"); ?>');
addTooltip($('#seljornada-cancelBtn').linkbutton(),'<?php _e("Print round results"); ?>');

$('#seljornada-Prueba').combogrid({
	panelWidth: 550,
	panelHeight: 150,
	idField: 'ID',
	textField: 'Nombre',
	url: '../ajax/database/pruebaFunctions.php',
    queryParams: { Operation: 'enumerate'},
	method: 'get',
	mode: 'remote',
	required: true,
	multiple: false,
	fitColumns: true,
	singleSelect: true,
	selectOnNavigation: true, // let use cursor keys to interactive select
	editable: isMobileDevice()?false:true, //disable keyboard deploy on mobile devices
	columns: [[
	   	    {field:'ID',			hidden:true},
			{field:'Nombre',		title:'<?php _e('Name'); ?>',			width:50,	align:'left'},
            {field:'Club',			hidden:true},
			{field:'NombreClub',	title:'<?php _e('Club'); ?>',			width:20,	align:'right'},
			{field:'RSCE',			title:'<?php _e('Fed'); ?>.',			width:15,	align:'center', formatter:formatFederation},
			{field:'Observaciones',	title:'<?php _e('Comments'); ?>',	width:30,	align:'right'},
            {field:'Inscritos',		hidden:true}
	]],
	onChange:function(value){
		var p=$('#seljornada-Prueba').combogrid('grid').datagrid('getSelected');
		if (p===null) return; // no selection
		setPrueba(p); // retorna jornada, o 0 si la prueba ha cambiado
		$('#seljornada-Jornada').combogrid('clear');
		$('#seljornada-Jornada').combogrid('grid').datagrid('load',{Prueba:p.ID});
	},
	onLoadSuccess: function(data) {
		if (workingData.prueba!=0) $('#seljornada-Prueba').combogrid('setValue',workingData.prueba);
		else {
			$('#seljornada-Prueba').combogrid('grid').datagrid('clearSelections');
			$('#seljornada-Jornada').combogrid('grid').datagrid('clearSelections');
		}
	}
});

$('#seljornada-Jornada').combogrid({
	panelWidth: 600,
	panelHeight: 'auto',
	idField: 'ID',
	textField: 'Nombre',
	url: '../ajax/database/jornadaFunctions.php',
	method: 'get',
	mode: 'remote',
	required: true,
	multiple: false,
	fitColumns: true,
	singleSelect: true,
	editable: ( ! isMobileDevice() ), //disable keyboard deploy on mobile devices
	columns: [[
	    { field:'ID',			hidden:true }, // ID de la jornada
	    { field:'Prueba',		hidden:true }, // ID de la prueba
	    { field:'Numero',		width:4, sortable:false,	align:'center', title: '#'},
		{ field:'Nombre',		width:25, sortable:false,   align:'left',  title: '<?php _e('Name'); ?>' },
        { field:'Nombre_Competicion',hidden:true },
        { field:'Tipo_Competicion',	hidden:true},
		{ field:'Fecha',		hidden:true},
		{ field:'Hora',			hidden:true},
		{ field:'Grado1',		width:7, sortable:false,	align:'center', title: 'G-I    ', formatter:formatGrado1 },
		{ field:'Grado2',		width:7, sortable:false,	align:'center', title: 'G-II   ', formatter:formatOk },
		{ field:'Grado3',		width:7, sortable:false,	align:'center', title: 'G-III  ', formatter:formatOk },
		{ field:'Open',		    width:7, sortable:false,	align:'center', title: 'Open   ', formatter:formatOk },
		{ field:'Equipos3',		width:12, sortable:false,	align:'center', title: 'Teams  ', formatter:formatTeamDogs },
		{ field:'Equipos4',		hidden:true }, // since 4.2.x Equipos3/Equipos4 becomes mindogs/maxdogs
		{ field:'PreAgility',	width:7, sortable:false,	align:'center', title: 'PreAg. ', formatter: formatPreAgility },
        { field:'Children',	    width:7, sortable:false,	align:'center', title: 'Childre', formatter:formatOk },
        { field:'Junior',	    width:7, sortable:false,	align:'center', title: 'Junior ', formatter:formatOk },
        { field:'Senior',	    width:7, sortable:false,	align:'center', title: 'Senior ', formatter:formatOk },
        { field:'ParaAgility',  width:7, sortable:false,	align:'center', title: 'Para Ag', formatter:formatOk },
		{ field:'KO',			width:7, sortable:false,	align:'center', title: 'K.O.   ', formatter:formatOk },
        { field:'Especial',		width:7, sortable:false,	align:'center', title: '<?php _e('Special');?>', formatter:formatOk },
        { field:'Cerrada',		width:6, sortable:false,	align:'center', title: '<?php _e('Closed');?>', formatter: formatCerrada }
    ]],
	onBeforeLoad: function(param) { 
		param.Operation='enumerate';
		param.Prueba=workingData.prueba;
		param.AllowClosed=<?php echo $allowClosed;?>;
		param.HideUnassigned=1;
		return true;
	},
	onLoadSuccess: function(data) {
        $(this).combogrid('grid').datagrid('getPanel').find('tr.datagrid-row').each(function(){
            var index = parseInt($(this).attr('datagrid-row-index'));
            $(this).tooltip({
                zIndex:9999,
                trackMouse:false,
                position:'left',
                content:data.rows[index]['Nombre_Competicion']
            })
        });
		if (parseInt(workingData.jornada)!==0) $('#seljornada-Jornada').combogrid('setValue',workingData.jornada);
	}
});

function acceptSelectJornada() {
	// si prueba invalida cancelamos operacion
	var p=$('#seljornada-Prueba').combogrid('grid').datagrid('getSelected');
	var j=$('#seljornada-Jornada').combogrid('grid').datagrid('getSelected');
	if ( (p==null) || (j==null) ) {
		// indica error
		$.messager.alert('<?php _e("Error"); ?>','<?php _e("You must select a valid contest/journey"); ?>',"error");
		return;
	}
	// store selected data into global structure
	setPrueba(p);
	setJornada(j);
	$('#seljornada-window').window('close');	
}

function cancelSelectJornada() {
    setPrueba(null);
    setJornada(null);
	$('#seljornada-window').window('close');
}
</script>