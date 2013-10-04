/**
 * Abre el formulario para jornadas a una prueba
 *@param prueba objeto que contiene los datos de la prueba
 */
function addJornadaToPrueba(prueba) {
	myPrueba=prueba;
	$('#jornadas-dialog').dialog('open').dialog('setTitle','A&ntilde;adir jornada a la prueba '+prueba.Nombre);
	$('#jornadas-form').form('clear');
	$('#jornadas-Prueba').val(prueba.Nombre);
	$('#jornadas-Operation').val('insert');
}

/**
 * Edita la jornada seleccionada
 *@param index: indice que ocupa el guia en la entrada principal
 *@param prueba objeto que contiene los datos de la prueba
 */
function editJornadaFromPrueba(prueba) {
	// obtenemos datos de la JORNADA seleccionada
    var row = $('#jornadas-datagrid-'+prueba.Nombre).datagrid('getSelected');
    if (!row) return; // no hay ninguna jornada seleccionada. retornar
    if (row.Cerrada==true) { // no permitir la edicion de pruebas cerradas
        $.messager.show({    
            title: 'Error',
            msg: 'No se puede editar una jornada una vez cerrada'
        });
        return;
    }
    // todo ok: abrimos ventana de dialogo
    $('#jornadas-dialog').dialog('open').dialog('setTitle','Modificar datos de la jornada');
    $('#jornadas-form').form('load',row);
    // fix date value value into datebox
    // take care on int-to-bool translation for checkboxes
    $('#jornadas-Grado1').prop('checked',(row.Grado1==1)?true:false);
    $('#jornadas-Grado2').prop('checked',(row.Grado2==1)?true:false);
    $('#jornadas-Grado3').prop('checked',(row.Grado3==1)?true:false);
    $('#jornadas-Equipos').prop('checked',(row.Equipos==1)?true:false);
    $('#jornadas-PreAgility').prop('checked',(row.PreAgility==1)?true:false);
    $('#jornadas-KO').prop('checked',(row.KO==1)?true:false);
    $('#jornadas-Exhibicion').prop('checked',(row.Exhibicion==1)?true:false);
    $('#jornadas-Otras').prop('checked',(row.Otras==1)?true:false);
    $('#jornadas-Cerrada').prop('checked',(row.Cerrada==1)?true:false);
	$('#jornadas-Prueba').val(prueba.Nombre);
	$('#jornadas-Operation').val('update');
}

/**
 * Quita la asignacion de la jornada indicada a la prueba asignada
 *@param indice de la fila (jornada) afectada
 *@prueba objeto que contiene los datos de la prueba
 */
function delJornadaFromPrueba(prueba) {
    var row = $('#jornadas-datagrid-'+prueba.Nombre).datagrid('getSelected');
    if (!row) return; // no row selected
    if (prueba.Cerrada==true) {
        $.messager.show({    // show error message
            title: 'Error',
            msg: 'No se pueden borrar jornadas de una prueba cerrada'
        });
    }
    $.messager.confirm('Confirm',"Borrar Jornada '"+row.ID+"' de la prueba '"+prueba.Nombre+"' ¿Seguro?'",function(r){
        if (r){
            $.get('database/jornadaFunctions.php',{Operation:'delete',ID:row.ID},function(result){
                if (result.success){
                    $('#jornadas-datagrid-'+prueba.Nombre).datagrid('reload');    // reload the pruebas data
                } else {
                    $.messager.show({    // show error message
                        title: 'Error',
                        msg: result.errorMsg
                    });
                }
            },'json');
        }
    });
}

/**
 * Ask for commit new/edit jornada to server
 */
function saveJornada(){
	// take care on bool-to-int translation from checkboxes to database
    $('#jornadas-Grado1').val( $('#jornadas-Grado1').is(':checked')?'1':'0');
    $('#jornadas-Grado2').val( $('#jornadas-Grado2').is(':checked')?'1':'0');
    $('#jornadas-Grado3').val( $('#jornadas-Grado3').is(':checked')?'1':'0');
    $('#jornadas-Equipos').val( $('#jornadas-Equipos').is(':checked')?'1':'0');
    $('#jornadas-PreAgility').val( $('#jornadas-PreAgility').is(':checked')?'1':'0');
    $('#jornadas-KO').val( $('#jornadas-KO').is(':checked')?'1':'0');
    $('#jornadas-Exhibicion').val( $('#jornadas-Exhibicion').is(':checked')?'1':'0');
    $('#jornadas-Otras').val( $('#jornadas-Otras').is(':checked')?'1':'0');
    $('#jornadas-Cerrada').val( $('#jornadas-Cerrada').is(':checked')?'1':'0');
    // handle fecha
    // do normal submit
    $('#jornadas-form').form('submit',{
        url: 'database/jornadaFunctions.php',
        method: 'get',
        onSubmit: function(param){
            return $(this).form('validate');
        },
        success: function(result){
            var result = eval('('+result+')');
            if (result.errorMsg){
                $.messager.show({
                    title: 'Error',
                    msg: result.errorMsg
                });
            } else {
            	var prueba=$('#jornadas-Prueba').val();
                $('#jornadas-dialog').dialog('close');        // close the dialog
                $('#jornadas-datagrid-'+prueba).datagrid('reload',{Prueba: prueba});    // reload the prueba data
            }
        }
    });
}