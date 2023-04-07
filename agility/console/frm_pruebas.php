<!-- 
frm_pruebas.php

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
require_once(__DIR__ . "/../server/tools.php");
require_once(__DIR__ . "/../server/auth/Config.php");
$config =Config::getInstance();
?>

<!-- TABLA DE jquery-easyui para listar y editar la BBDD DE Pruebas -->
<div style="width:100%;height:100%">    
    <!-- DECLARACION DE LA TABLA -->
    <table id="pruebas-datagrid"></table> 
</div>
    
<!-- BARRA DE TAREAS DE LA TABLA DE PRUEBAS-->
<div id="pruebas-toolbar" style="width:100%;display:inline-block">
   	<span style="float:left;padding:5px">
   	    <a id="pruebas-newBtn" href="#" class="easyui-linkbutton" 
   	    	data-options="iconCls:'icon-add'"
   	    	onclick="newPrueba('#pruebas-datagrid',$('#pruebas-datagrid-search').val())"><?php _e('New contest'); ?></a>
   	    <a id="pruebas-editBtn" href="#" class="easyui-linkbutton"
   	    	data-options="iconCls:'icon-edit'"
   	    	onclick="editPrueba('#pruebas-datagrid')"><?php _e('Edit contest'); ?></a>
   	    <a id="pruebas-delBtn" href="#" class="easyui-linkbutton" 
   	    	data-options="iconCls:'icon-remove'"
   	    	onclick="deletePrueba('#pruebas-datagrid')"><?php _e('Remove contest'); ?></a>
   		<input id="pruebas-datagrid-search" type="text" value="<?php _e('-- Search --'); ?>" class="search_textfield"
			   onfocus="handleSearchBox(this,true);" onblur="handleSearchBox(this,false);"/>
   	    <input id="pruebas-openBox" type="checkbox" value="1" class="easyui-checkbox"
   	    	data-options="iconCls:'icon-search'" 
   	    	onclick="doSearchPrueba()"/><?php _e('Incl. closed'); ?>
        <a id="pruebas-reloadBtn" href="#" class="easyui-linkbutton"
           data-options="iconCls:'icon-brush'"
           onclick="
        		// clear selection and reload table
				reloadWithSearch('#pruebas-datagrid','select',true);
			"><?php _e('Clear'); ?></a>
   	</span>
   	<span style="float:right;padding:5px">
   		<a id="pruebas-emailBtn" href="#" class="easyui-linkbutton"
           data-options="iconCls:'icon-mail'"
           onclick="emailPrueba('#pruebas-datagrid')"><?php _e('Mail'); ?></a>
   		<a id="pruebas-exportBtn" href="#" class="easyui-linkbutton"
		   data-options="iconCls:'icon-db_backup'"
		   onclick="exportPrueba('#pruebas-datagrid')"><?php _e('Export'); ?></a>
   	</span>
</div>
    
<?php require_once("dialogs/dlg_clubes.inc");?>
<?php require_once("dialogs/dlg_pruebas.inc");?>
<?php require_once("dialogs/dlg_jornadas.inc");?>

<script type="text/javascript">
        
        // datos de la tabla de pruebas
        $('#pruebas-datagrid').datagrid({
        	// propiedades del panel padre asociado
        	fit: true,
        	border: false,
        	closable: false,
        	collapsible: false,
            expansible: false,
        	collapsed: false,        	
        	title: '<?php _e('Contest data handling'); ?>',
        	url: '../ajax/database/pruebaFunctions.php',
            queryParams: { Operation: 'select' },
        	loadMsg: '<?php _e('Updating contest list'); ?> ...',
        	method: 'get',
            toolbar: '#pruebas-toolbar',
            pagination: false,
            rownumbers: false,
            fitColumns: true,
			idField: 'ID',
            singleSelect: true,
            view: scrollview,
            pageSize: 50,
            multiSort: true,
            remoteSort: true,
            columns: [[
                { field:'ID', hidden:true }, // primary key
            	{ field:'Nombre',		width:30,	sortable:true,	title:'<?php _e('Contest name'); ?>',formatter:formatBold },
                { field:'OpeningReg',	hidden:true },
                { field:'ClosingReg',	width:7,	sortable:true,	title:'<?php _e('Closing'); ?>' },
                { field:'Club',			hidden:true },
            	{ field:'NombreClub',	width:15,	sortable:true,	title:'<?php _e('Organizing club'); ?>' },
            	{ field:'Ubicacion',	width:15,					title:'<?php _e('Event location'); ?>' },
                { field:'Triptico',		width:10,					title:'<?php _e('Triptych URL'); ?>'},
                { field:'Cartel',		width:10,					title:'<?php _e('Brochure URL'); ?>'},
                { field:'Observaciones',width:10,					title:'<?php _e('Comments'); ?>'},
                { field:'RSCE',			width:7, formatter:	formatFederation,		title:'<?php _e('Federation'); ?>', align: 'center'},
                { field:'Selectiva',	width:7, formatter:	formatOk,title:'<?php _e('Selective'); ?>',	align: 'center'},
                { field:'Cerrada',		width:7, formatter:	formatCerrada,	title:'<?php _e('Closed'); ?>',	align: 'center'}
            ]],
            // colorize rows. notice that overrides default css, so need to specify proper values on datagrid.css
            rowStyler:myRowStyler,
    		// on double click fireup editor dialog
        	onDblClickRow:function(index,row) { 
            	setPrueba(row);
            	editPrueba('#pruebas-datagrid');
            },
            // especificamos un formateador especial para desplegar la tabla de jornadas por prueba
            detailFormatter:function(index,row){
                return '<div style="padding:2px"><table id="jornadas-datagrid-' + row.ID + '"/></div>';
            },
            onExpandRow: function(index,row) { // on scrollview row may be undefined, so ignore
                var r = $(this).datagrid('getRow',index);
                setPrueba(r);
                showJornadasByPrueba(index,r);
            },
            onClickRow: function(index,row) { setPrueba(row); } // mark prueba as active
            
        }); // end of pruebas-datagrid
        
		// key handler
       	addKeyHandler('#pruebas-datagrid',"",newPrueba,editPrueba,deletePrueba);
		// tooltips
		addTooltip($('#pruebas-newBtn').linkbutton(),'<?php _e("Declare a new contest and include into database"); ?>');
		addTooltip($('#pruebas-editBtn').linkbutton(),'<?php _e("Edit information on selected contest"); ?>');
		addTooltip($('#pruebas-delBtn').linkbutton(),'<?php _e("Remove selected contest"); ?>');
        addTooltip($('#pruebas-exportBtn').linkbutton(),'<?php _e("Export selected contest data in Excel format"); ?>');
        addTooltip($('#pruebas-emailBtn').linkbutton(),'<?php _e("Send mail to clubs with contest info and inscription templates"); ?>');
		addTooltip($('#pruebas-reloadBtn').linkbutton(),'<?php _e("Clear search box. Update list"); ?>');
		addTooltip($('#pruebas-openBox').linkbutton(),'<?php _e("Include finished (closed) contest into listing"); ?>');
		addTooltip($('#pruebas-datagrid-search'),'<?php _e("Look for contests matching search criteria"); ?>');

        // ------------- submenu de jornadas asociadas a una prueba --------------------- //
        function showJornadasByPrueba (index,prueba) {
            var datagridID='#jornadas-datagrid-'+prueba.ID;
			workingData.datosPrueba=prueba;
            $(datagridID).datagrid({
        		title: '<?php _e("Journeys on this contest"); ?>'+" '"+prueba.Nombre+"'",
        		url: '../ajax/database/jornadaFunctions.php',
        		queryParams: { Operation: 'select', Prueba: prueba.ID },
        		method: 'get',
       		    pagination: false,
        	    rownumbers: false,
        	    fitColumns: true,
        	    singleSelect: true,
        	    loadMsg: '<?php _e('Loading list of journeys'); ?>...',
        	    height: 'auto',
        	    columns: [[
                   	{ field:'ID',			hidden:true }, // ID de la jornada
            	    { field:'Prueba',		hidden:true }, // ID de la prueba
            	    { field:'Numero',		width:4, sortable:true,		align:'center', title: '#'},
            		{ field:'Nombre',		width:20, sortable:false,   title: '<?php _e('Name'); ?>'+'/'+'<?php _e('Comments'); ?>',formatter:formatBold },
            		{ field:'Fecha',		width:12, sortable:true,	title: '<?php _e('Date'); ?>' },
            		{ field:'Hora',			width:10, sortable:false,	title: '<?php _e('Hour'); ?>' },
            		{ field:'PreAgility',	width:8, sortable:false, formatter:	formatPreAgility, align:'center', title: 'PreAg. ' },
                    { field:'Children',	    width:8, sortable:false, formatter:	formatOk,	align:'center',     title: 'Childrn' },
                    { field:'Junior',	    width:8, sortable:false, formatter:	formatOk,	align:'center',     title: 'Junior ' },
                    { field:'Senior',	    width:8, sortable:false, formatter:	formatOk,	align:'center',     title: 'Senior ' },
                    { field:'ParaAgility',  width:8, sortable:false, formatter:	formatOk,	align:'center',     title: 'ParaAg.' },
            		{ field:'Grado1',		width:8, sortable:false, formatter:formatGrado1 ,align:'center',    title: 'G-I    ' },
            		{ field:'Grado2',		width:8, sortable:false, formatter:	formatOk,	align:'center',     title: 'G-II   ' },
            		{ field:'Grado3',		width:8, sortable:false, formatter:	formatOk,	align:'center',     title: 'G-III  ' },
            		{ field:'Open',			width:8, sortable:false, formatter:	formatOk,	align:'center',     title: 'Open   ' },
            		{ field:'Equipos3',		width:12, sortable:false, formatter:formatTeamDogs,	align:'center', title: 'Teams' },
            		{ field:'Equipos4',		hidden:true }, // since 4.2.x Equipos3/Equipos4 becomes mindogs/maxdogs teams
                    { field:'KO',			width:8, sortable:false, formatter:	formatOk,	align:'center',     title: 'K.O.   ' },
                    { field:'Games',		width:8, sortable:false, formatter:	formatOk,	align:'center',     title: 'Games  ' },
            		{ field:'Especial',	    width:8, sortable:false, formatter:	formatOk,	align:'center',     title: '<?php _e('Special'); ?>'},
            	    { field:'Observaciones',hidden:true }, // texto para el caso de Manga especial
            		{ field:'Cerrada',		width:5, sortable:false, formatter:	formatCerrada,	align:'center', title: '<?php _e('Closed'); ?>' }
            	]],
            	// colorize rows. notice that overrides default css, so need to specify proper values on datagrid.css
            	rowStyler:myRowStyler,
            	// on double click fireup editor dialog
                onDblClickRow:function(idx,row) { //idx: selected row index; row selected row data
                    editJornadaFromPrueba(prueba.ID,row);
                },
                onResize:function(){
                    $('#pruebas-datagrid').datagrid('fixDetailRowHeight',index);
                },
                onLoadSuccess:function(data){
                    setTimeout(function(){
                        $('#pruebas-datagrid').datagrid('fixDetailRowHeight',index);
                    },0);
                } 
        	}); // end of pruebas-jornada-datagrid

        	$('#pruebas-datagrid').datagrid('fixDetailRowHeight',index);
    		// definimos inline la sub-barra de tareas para que solo aparezca al desplegar el sub formulario
    		// por defecto, cada prueba tiene asociadas 8 jornadas que se crean automaticamente
    		// por consiguiente desde la aplicacion no se deben poder anyadir ni borrar jornadas
    		var toolbar=  [
    	        {
    				id: 'jornadasbyprueba-editBtn'+prueba.ID,
            		text: '<?php _e('Edit journey'); ?>',
        			iconCls: 'icon-edit',
					handler: function(){editJornadaFromPrueba(datagridID,$(datagridID).datagrid('getSelected'));}
    			},{
                    id: 'jornadasbyprueba-clearBtn'+prueba.ID,
                    text: '<?php _e('Clear journey'); ?>',
                    iconCls: 'icon-brush',
                    handler: function(){clearJornadaFromPrueba(datagridID,$(datagridID).datagrid('getSelected'));}
                },{
    				id: 'jornadasbyprueba-closeBtn'+prueba.ID,
            		text: '<?php _e('Close journey'); ?>',
        			iconCls: 'icon-forbidden'
                    // commented: handler is defined bellow
       				// handler: function(){closeJornadaFromPrueba(datagridID,$(datagridID).datagrid('getSelected'));}
    			},{
    				id: 'jornadasbyprueba-reloadBtn'+prueba.ID,
            		text: '<?php _e('Update'); ?>',
        			iconCls: 'icon-reload',
        			align: 'right', // notice that this property is handled by our own 'buildToolbar extended method'
       				handler: function(){$(datagridID).datagrid('reload');}    // reload the pruebas data}
    			}
    			];
    		$(datagridID).datagrid('buildToolbar',toolbar);

            /*
            $('#jornadasbyprueba-clearBtn'+prueba.ID).bind('click',function(e) {
                clearJornadaFromPrueba(datagridID,$(datagridID).datagrid('getSelected'));
            });
            */
            // add event to toolbar button handler to detect ctrl-key pressed
            $('#jornadasbyprueba-closeBtn'+prueba.ID).bind('click',function(e) {
                closeJornadaFromPrueba(datagridID,e);
            });

			// tooltips de los sub-formularios
			addSimpleKeyHandler(datagridID,"",editJornadaFromPrueba);
            addTooltip($('#jornadasbyprueba-editBtn'+prueba.ID).linkbutton(),'<?php _e("Edit data on selected journey"); ?>');
            addTooltip($('#jornadasbyprueba-clearBtn'+prueba.ID).linkbutton(),'<?php _e("Clear data on selected journey and remove inscriptions"); ?>');
			addTooltip($('#jornadasbyprueba-closeBtn'+prueba.ID).linkbutton(),'<?php _e("Close journey. Store data as inmutable"); ?>');
			addTooltip($('#jornadasbyprueba-reloadBtn'+prueba.ID).linkbutton(),'<?php _e("Update journey list for this contest"); ?>');


       }

</script>
