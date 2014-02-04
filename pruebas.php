<!-- TABLA DE jquery-easyui para listar y editar la BBDD DE Pruebas -->
    
    <!-- DECLARACION DE LA TABLA -->
    <table id="pruebas-datagrid" class="easyui-datagrid"></table>     
    	<!-- BARRA DE TAREAS -->
    	<div id="pruebas-toolbar">
    	    <a id="pruebas-newBtn" href="#" class="easyui-linkbutton" onclick="newPrueba()">Nueva prueba</a>
    	    <a id="pruebas-editBtn" href="#" class="easyui-linkbutton" onclick="editPrueba()">Editar prueba</a>
    	    <a id="pruebas-delBtn" href="#" class="easyui-linkbutton" onclick="destroyPrueba()">Borrar prueba</a>
    	    <input id="pruebas-search" type="text" onchange="doSearchPrueba()"/> 
    	    <a id="pruebas-searchBtn" href="#" class="easyui-linkbutton" onclick="doSearchPrueba()">Buscar</a>
    	    <input id="pruebas-openBox" type="checkbox" value="1" class="easyui-checkbox" onclick="doSearchPrueba()"/>Incl. Cerradas
    	</div>
    
 	<?php include_once("dialogs/dlg_clubes.inc");?>
 	<?php include_once("dialogs/dlg_pruebas.inc");?>
 	<?php include_once("dialogs/dlg_jornadas.inc");?>

    <script type="text/javascript">
    
    	// set up operation header content
        $('#Header_Operation').html('<p>Creaci&oacute;n y edici&oacute;n de pruebas</p>');
        
        // datos de la tabla de pruebas
        // - tabla
        $('#pruebas-datagrid').datagrid({
        	title: 'Gesti&oacute;n de datos de pruebas',
        	url: 'database/pruebaFunctions.php?Operation=select',
        	method: 'get',
            toolbar: '#pruebas-toolbar',
            pagination: true,
            rownumbers: false,
            singleSelect: true,
            fitColumns: true,
            expansible: true,
            view: detailview,
            height: 'auto',
            columns: [[
                { field:'ID', hidden:true }, // primary key
            	{ field:'Nombre',		width:20,	sortable:true,	title:'Nombre de la prueba:' },
            	{ field:'Club',			width:15,	sortable:true,	title:'Club organizador' },
            	{ field:'Ubicacion',	width:20,					title:'Lugar de celebraci&oacute;n' },
                { field:'Triptico',		width:10,					title:'URL del Tr&iacute;ptico'},
                { field:'Cartel',		width:10,					title:'URL del Cartel'},
                { field:'Observaciones',width:15,					title:'Observaciones'},
                { field:'Cerrada',		width:10,					title:'Cerrada', align: 'center'}
            ]],
            // colorize rows. notice that overrides default css, so need to specify proper values on datagrid.css
            rowStyler:function(index,row) { 
                return ((index&0x01)==0)?'background-color:#ccc;':'background-color:#eee;';
            },
    		// on double click fireup editor dialog
        	onDblClickRow:function() { editPrueba(); },
            // especificamos un formateador especial para desplegar la tabla de jornadas por prueba
            detailFormatter:function(index,row){
                return '<div style="padding:2px"><table id="jornadas-datagrid-' + row.ID + '"/></div>';
            },
            onExpandRow: function(index,row) {
                if (row.ID!=0) showJornadasByPrueba(index,row); 
            }
            
        }); // end of pruebas-datagrid
         
        // - botones  y tooltips de la tabla
        $('#pruebas-newBtn').linkbutton({iconCls:'icon-add',plain:true }).tooltip({ // nueva prueba
        	position: 'top',
        	content: '<span style="color:#000">Dar de alta una nueva prueba</span>',
        	onShow: function(){	$(this).tooltip('tip').css({backgroundColor: '#ef0',borderColor: '#444'	});
        	}
    	});
        $('#pruebas-editBtn').linkbutton({iconCls:'icon-edit',plain:true }).tooltip({ // editar prueba
        	position: 'top',
        	content: '<span style="color:#000">Editar los datos de la prueba seleccionada</span>',
        	onShow: function(){	$(this).tooltip('tip').css({backgroundColor: '#ef0',borderColor: '#444'	});}
    	});
        $('#pruebas-delBtn').linkbutton({iconCls:'icon-remove',plain:true }).tooltip({ // borrar prueba
            position: 'top',
            content: '<span style="color:#000">Eliminar la prueba seleccionada</span>',
        	onShow: function(){	$(this).tooltip('tip').css({backgroundColor: '#ef0',borderColor: '#444'	});}
        });

        $('#pruebas-searchBtn').linkbutton({iconCls:'icon-search',plain:true }).tooltip({ // buscar prueba
        	position: 'top',
        	content: '<span style="color:#000">Buscar pruebas coincidentes con el texto indicado</span>',
        	onShow: function(){	$(this).tooltip('tip').css({backgroundColor: '#ef0',borderColor: '#444'	});
        	}
    	});
        $('#pruebas-openBox').tooltip({ 
            position: 'top',
            content: '<span style="color:#000">Incluir en el listado las pruebas finalizadas</span>',
        	onShow: function(){	$(this).tooltip('tip').css({backgroundColor: '#ef0',borderColor: '#444'	});}
        });


        // ------------- submenu de jornadas asociadas a una prueba --------------------- //
        function showJornadasByPrueba (index,prueba) {
            var datagridID='#jornadas-datagrid-'+prueba.ID;
            $(datagridID).datagrid({
        		title: 'Jornadas de que consta la prueba '+prueba.Nombre,
        		url: 'database/jornadaFunctions.php',
        		queryParams: { Operation: 'select', Prueba: prueba.ID },
        		method: 'get',
        		// definimos inline la sub-barra de tareas para que solo aparezca al desplegar el sub formulario
        		// por defecto, cada prueba tiene asociadas 8 jornadas que se crean automaticamente
        		// por consiguiente desde la aplicacion no se deben poder anyadir ni borrar jornadas
        		toolbar:  [{
        			id: 'jornadasbyprueba-editBtn',
                	text: 'Editar jornada',
                	plain: true,
            		iconCls: 'icon-edit',
           			handler: function(){editJornadaFromPrueba(prueba.ID,datagridID);}
        		}],
       		    pagination: false,
        	    rownumbers: false,
        	    fitColumns: true,
        	    singleSelect: true,
        	    loadMsg: 'Loading list of journeys',
        	    height: 'auto',
        	    columns: [[
                   	{ field:'ID',			hidden:true }, // ID de la jornada
            	    { field:'Prueba',		hidden:true }, // ID de la prueba
            	    { field:'Numero',		width:4, sortable:true,		align:'center', title: '#'},
            		{ field:'Nombre',		width:20, sortable:false,   title: 'Nombre/Comentario' },
            		{ field:'Fecha',		width:12, sortable:true,	title: 'Fecha:' },
            		{ field:'Hora',			width:10, sortable:false,	title: 'Hora.' },
            		{ field:'Grado1',		width:6, sortable:false,   align:'center', title: 'G-I   ' },
            		{ field:'Grado2',		width:6, sortable:false,   align:'center', title: 'G-II  ' },
            		{ field:'Grado3',		width:6, sortable:false,   align:'center', title: 'G-III ' },
            		{ field:'Equipos',		width:6, sortable:false,   align:'center', title: 'Eq.   ' },
            		{ field:'PreAgility',	width:6, sortable:false,   align:'center', title: 'Pre.  ' },
            		{ field:'KO',			width:6, sortable:false,   align:'center', title: 'K.O.  ' },
            		{ field:'Exhibicion',	width:6, sortable:false,   align:'center', title: 'Show  ' },
            		{ field:'Otras',		width:6, sortable:false,   align:'center', title: 'Otras ' },
            		{ field:'Cerrada',		width:6, sortable:false,   align:'center', title: 'Cerrada' }
            	]],
            	// colorize rows. notice that overrides default css, so need to specify proper values on datagrid.css
            	rowStyler:function(idx,row) { 
            	    return ((idx&0x01)==0)?'background-color:#ccc;':'background-color:#eee;';
            	},
            	// on double click fireup editor dialog
                onDblClickRow:function(idx,row) { //idx: selected row index; row selected row data
                    editJornadaFromPrueba(prueba.ID,datagridID);
                },
                onResize:function(){
                    $('#pruebas-datagrid').datagrid('fixDetailRowHeight',index);
                },
                onLoadSuccess:function(){
                    setTimeout(function(){
                        $('#pruebas-datagrid').datagrid('fixDetailRowHeight',index);
                    },0);
                } 
        	}); // end of pruebas-jornada-datagrid
        	$('#pruebas-datagrid').datagrid('fixDetailRowHeight',index);
        	
            // botones de los sub-formularios
            $('#jornadasbyprueba-editBtn').linkbutton().tooltip({ // editar datos de la jornada
                position: 'top',
                content: '<span style="color:#000">Editar los datos la jornada seleccionada</span>',
            	onShow: function(){	$(this).tooltip('tip').css({backgroundColor: '#ef0',borderColor: '#444'	});}
            });
        };
        
	</script>
