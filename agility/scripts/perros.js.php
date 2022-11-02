<?php header('Content-Type: text/javascript'); ?>
/*
 perros.js

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

<?php
require_once(__DIR__."/../server/auth/Config.php");
require_once(__DIR__."/../server/tools.php");
$config =Config::getInstance();
?>

// ***** gestion de perros		*********************************************************

function reload_perrosDatagrid(dg) {
	var w=$(dg+'-search').val();
	if (strpos(w,"<?php _e('-- Search --'); ?>",0)!==false) w='';
	// $(dg).datagrid('load',{ Operation: 'select', where: w, Federation: workingData.federation });
    setTimeout(function() { $(dg).datagrid('reload'); },500);
}

/**
 * Abre el dialogo para insertar datos de un nuevo perro
 * @param {string} dg datagrid ID de donde se obtiene el perro
 * @param def nombre por defecto que se asigna al perro en el formulario
 */
function newDog(dg,def){
    $('#perros-form').form('clear'); // start with an empty form
	$('#perros-dialog').dialog('open').dialog('setTitle','<?php _e('New dog'); ?>'+' - '+fedName(workingData.federation));
	if (strpos(def,"<?php _e('-- Search --'); ?>")===false) $('#perros-Nombre').textbox('setValue',def.capitalize());
	$('#perros-Operation').val('insert');
    $("#perros-Baja").css('display','inline'); // make sure "retired" option is visible
	$('#perros-warning').css('visibility','hidden');
	$('#perros-okBtn').one('click',function() {reload_perrosDatagrid(dg);});
}

/**
 * Abre el dialogo para editar datos de un perro ya existente
 * @param {string} dg datagrid ID de donde se obtiene el perro
 */
function editDog(dg,row){
    if ($('#perros-datagrid-search').is(":focus")) return; // on enter key in search input ignore
    if ($('#new_inscription-datagrid-search').is(":focus")) return; // on enter key in search input ignore
    if (typeof(row)==="undefined") {
        var rows = $(dg).datagrid('getSelections');
        if (rows.length==0) {
            $.messager.alert('<?php _e("Edit Error"); ?>','<?php _e("There is no selected dog"); ?>',"warning");
            return; // no way to know which dog is selected
        }
        if (rows.length>1) {
            $.messager.alert('<?php _e("Edit Error"); ?>','<?php _e("Too many selected dogs"); ?>',"warning");
            return; // no way to know which dog is selected
        }
        row=rows[0];
    }
    // add extra required data to form dialog and open it
    row.Operation='update';
    $('#perros-dialog').dialog('open').dialog('setTitle','<?php _e('Modify data on dog'); ?>'+' - '+fedName(workingData.federation));
    $('#perros-form').form('load',row);// load form with row data
    $('#perros-Baja').css('display','inline'); // make sure "retired" option is visible
	$('#perros-warning').css('visibility','visible');
	$('#perros-okBtn').one('click',function() {
        $(dg).datagrid('clearSelections');
	    reload_perrosDatagrid(dg);
	});
}

/**
 * Abre el dialogo para editar datos de un perro que se ha inscrito en una prueba
 */
function editInscribedDog(){
	var id=$('#edit_inscripcion-Perro').val();
    $('#perros-dialog').dialog('open').dialog('setTitle','<?php _e('Modify data on dog to be inscribed'); ?>'+' - '+fedName(workingData.federation));
    $('#perros-form').form('load','../ajax/database/dogFunctions.php?Operation=getbyidperro&ID='+id);
    // cannot mark as retired an inscribed dog, so hide form label and entry
    $("#perros-Baja").css('display','none');
    // add extra required data to form dialog
	$('#perros-warning').css('visibility','visible');
	$('#perros-okBtn').one('click',function() {
		// leave inscripcion dialog open
		saveInscripcion(false);
		// and refill dog changes with new data
		$.ajax({
			url: '../ajax/database/dogFunctions.php',
			data: { Operation: 'getbyidperro', ID: id },
			dataType: 'json',
			success: function(data) {
				$('#edit_inscripcion-Nombre').val(data.Nombre);
				$('#edit_inscripcion-Licencia').val(data.Licencia);
				$('#edit_inscripcion-Categoria').val(data.Categoria);
				$('#edit_inscripcion-Grado').val(data.Grado);
                $('#edit_inscripcion-NombreGuia').val(data.NombreGuia);
                $('#edit_inscripcion-CatGuia').val(formatCatGuia(data.CatGuia,0,0));
				$('#edit_inscripcion-NombreClub').val(data.NombreClub);
			}
		});
	});
}

/**
 * Borra el perro seleccionado de la base de datos
 * @param {string} dg datagrid ID de donde se obtiene el perro
 */
function deleteDog(dg){
    var rows = $(dg).datagrid('getSelections');
    if (rows.length==0) {
        $.messager.alert('<?php _e("Edit Error"); ?>','<?php _e("There are no selected dogs"); ?>',"info");
        return; // no way to know which dog is selected
    }
    var msg="<?php _e('You are about to delete following dog(s) from database');?><br/><?php _e('Are you sure?');?><br/>";
    var lista=
        '<br/><table width="100%"><tr><th>ID</th><th><?php _e("Name");?></th><th><?php _e("License");?></th>'+
        '<th><?php _e("Cat.");?>/<?php _e("Grad.");?></th><th><?php _e("Handler");?></th><th><?php _e("Club");?></th></tr>';

    for(var n=0;n<rows.length;n++) {
        lista+="<tr>";
        var row=rows[n];
        lista +="<td>"+row.ID+"</td><td>"+row.Nombre+"</td><td>"+row.Licencia+"</td><td> "+row.Categoria+"/"+row.Grado+"</td>";
        lista +="<td>"+row.NombreGuia+"</td><td>"+row.NombreClub+"</td></tr>";
    }
    lista+="</table>"

    $.messager.confirm('<?php _e('Delete'); ?>',msg+lista,function(r){
       	if (!r) return;
       	$.each(rows,function(index,row){
            $.get('../ajax/database/dogFunctions.php',{ Operation: 'delete', ID: row.ID },function(result){
                var nombre=row.Nombre;
                if (result.success){
                    $(dg).datagrid('clearSelections');
                    $(dg).datagrid('reload');    // reload the dog data. PENDING: what about using reloadPerrosDatagrid()?
                } else { // show error message
                    var errormsg="Cannot delete dog: "+row.Nombre+":<br/>&nbsp<br/>"+result.errorMsg;
                    $.messager.show({ width:300, height:200, title: 'Error',  msg: errormsg });
                }
            },'json');
        });
    }).window('resize',{width:640,height:'auto'});
}

/**
 * une los perros seleccionados en uno
 * @param {string} dg datagrid ID de donde se obtienen los perros a unir
 */
function joinDog(dg){
    var rows = $(dg).datagrid('getSelections');
    if (rows.length==0) {
        $.messager.alert('<?php _e("Join Error"); ?>','<?php _e("There are no selected dogs"); ?>',"info");
        return; // no way to know which dog is selected
    }
    if (rows.length<2) {
        $.messager.alert('<?php _e("Join Error"); ?>','<?php _e("Need to select at least two dogs"); ?>',"info");
        return; // no way to know which dog is selected
    }
    var msg="<?php _e('Please select the dog that will remain after join');?><br/><?php _e('Press accept to proceed');?><br/>";
    var lista=
        '<br/><form id="dogs_join_form"><table width="100%">'+
        '<tr><th>&nbsp;</th><th>ID</th><th><?php _e("Name");?></th><th><?php _e("License");?></th>'+
        '<th><?php _e("Cat.");?>/<?php _e("Grad.");?></th><th><?php _e("Handler");?></th><th><?php _e("Club");?></th></tr>';
    var selection="BEGIN";
    for(var n=0;n<rows.length;n++) {
        var row=rows[n];
        selection = selection + "," +row.ID;
        var input='<input type="radio" name="join_dogid" value="'+row.ID+'">';
        lista +="<tr>";
        lista +="<td>"+input+"</td><td>"+row.ID+"</td><td>"+row.Nombre+"</td><td>"+row.Licencia+"</td><td> "+row.Categoria+"/"+row.Grado+"</td>";
        lista +="<td>"+row.NombreGuia+"</td><td>"+row.NombreClub+"</td>";
        lista +="</tr>";
    }
    selection += ",END";
    lista+="</table></form>";

    $.messager.confirm('<?php _e('Join dogs'); ?>',msg+lista,function(r){
        if (!r) return;
        if (typeof ($('input[name=join_dogid]:checked').val() ) == "undefined") {
            $.messager.show({ title: 'Error',  msg: 'No dog selected for join' });
            return;
        }
        var selected=$('input[name=join_dogid]:checked').val();
        $.ajax({
            type: 'GET',
            url: '../ajax/database/dogFunctions.php',
            data: { Operation: 'join', From: selection, To: selected },
            dataType: 'json',
            success: function (result) {
                if (result.errorMsg){
                    $.messager.show({ width:300,height:200, title: 'Error', msg: result.errorMsg });
                } else {
                    $(dg).datagrid('clearSelections');
                    $(dg).datagrid('reload');
                }
            }
        });
    }).window('resize',{width:640,height:'auto'});
}

/**
 * Abre el formulario para anyadir/asignar perros a un guia
 *@param {string} dgstr identificador del datagrid que se actualiza
 *@param {object} guia datos del guia
 */
function assignPerroToGuia(dgstr,guia) {
	// clean previous dialog data
	$('#chperros-header').form('clear');
	$('#chperros-Search').combogrid('clear');
	$('#chperros-form').form('clear'); 
	// set up default guia data
	$('#chperros-newGuia').val(guia.ID);
    $('#chperros-Operation').val('update');
    $('#chperros-parent').val(dgstr);
	// desplegar ventana y ajustar textos
	$('#chperros-title').text('<?php _e('Search dog/Declare new dog and assign to'); ?>'+' '+guia.Nombre);
	$('#chperros-dialog').dialog('open').dialog('setTitle','<?php _e("Reassign / Declare dog"); ?>'+' - '+fedName(workingData.federation));
}

/**
* Abre el formulario para anyadir perros a un guia
* @param {string} dgstr datagrid ID de donde se obtiene el perro
* @param {object} guia datos del guia
*/
function editPerroFromGuia(dgstr,guia) {
	// try to locate which dog has been selected
    var dg=$(dgstr);
    var row = dg.datagrid('getSelected');
    if (!row) {
    	$.messager.alert('<?php _e("Error"); ?>','<?php _e("There is no selected dog"); ?>',"warning");
    	return; // no way to know which dog is selected
    }
    // add extra required data to form dialog
    row.Operation='update';
    // finally display composed data
    $('#perros-dialog').dialog('open').dialog('setTitle','<?php _e('Modify data on dog assigned to'); ?>'+' '+guia.Nombre+' - '+fedName(workingData.federation));
    $('#perros-form').form('load',row);	// load form with row data. onLoadSuccess will fix comboboxes
	$('#perros-okBtn').one('click',function () { dg.datagrid('reload'); } );
}

/**
 * Quita la asignacion del perro marcado al guia indicado
 * @param {string} dgstr datagrid ID de donde se obtiene el perro
 * @param {object} guia datos del guia
 */
function delPerroFromGuia(dgstr,guia) {
    var dg=$(dgstr);
    var row = dg.datagrid('getSelected');
    if (!row){
    	$.messager.alert('<?php _e("Error"); ?>','<?php _e("There is no selected dog"); ?>',"warning");
    	return; // no way to know which dog is selected
    }
    $.messager.confirm('<?php _e('Confirm'); ?>','<?php _e("Delete assignment from dog"); ?>'+" '"+row.Nombre+"' '+'<?php _e('to handler');?>'+' '"+guia.Nombre+"' "+'<?php _e('Sure?');?>',function(r){
        if (r){
            $.get('../ajax/database/dogFunctions.php',{ Operation: 'orphan', ID: row.ID },function(result){
                if (result.success){
                	dg.datagrid('reload');
                } else {
                    $.messager.show({title: '<?php _e('Error'); ?>', msg: result.errorMsg, width: 300,height:200 });
                }
            },'json');
        }
    });
}

/** 
 * Actualiza (reassign+edit) los datos de un perro pre-asignado a otro guia
 */
function assignDog() {
	// set up guia
	$('#chperros-Guia').val($('#chperros-newGuia').val());
    $('#chperros-Operation').val('update');
    $('#chperros-Federation').val(workingData.federation);
    var frm = $('#chperros-form');
    if (!frm.form('validate')) return; // don't call inside ajax to avoid override beforeSend()
    $.ajax({
        type: 'GET',
        url: '../ajax/database/dogFunctions.php',
        data: frm.serialize(),
        dataType: 'json',
        success: function (result) {
            if (result.errorMsg){
                $.messager.show({ width:300,height:200, title: 'Error', msg: result.errorMsg });
            } else {
                // TODO: this leave focus datagrid handling buggy. study why
                var dg=$('#chperros-parent').val();
                if (dg!="") $(dg).datagrid('reload');
            	$('#chperros-Search').combogrid('clear');  // clear search field
                $('#chperros-dialog').dialog('close');        // close the dialog
            }
        }
    });
}

/**
 * Anyade (new) un nuevo perro desde el menu de reasignacion de perros
 */
function saveChDog(){
    var frm = $('#chperros-form');
    $('#chperros-Guia').val($('#chperros-newGuia').val());
    $('#chperros-Operation').val('insert');
    $('#chperros-Federation').val(workingData.federation);
    if (!frm.form('validate')) return; // don't call inside ajax to avoid override beforeSend()

    $('#chperros-okBtn').linkbutton('disable');
    $.ajax({
        type: 'GET',
        url: '../ajax/database/dogFunctions.php',
        data: frm.serialize(),
        dataType: 'json',
        success: function (result) {
            if (result.errorMsg){
                $.messager.show({ width:300,height:200, title: '<?php _e('Error'); ?>', msg: result.errorMsg });
            } else {
                // TODO: this leave focus datagrid handling buggy. study why
                var dg=$('#chperros-parent').val();
                if (dg!="") $(dg).datagrid('load');
            	if (result.insert_id ) $('#chperros-ID').val(result.insert_id);
            	$('#chperros-Search').combogrid('clear');  // clear search field
                $('#chperros-dialog').dialog('close');    // close the dialog
            }
        },
        error: function(XMLHttpRequest,textStatus,errorThrown) {
            $.messager.alert("Save ChPerros","Error:"+XMLHttpRequest.status+" - "+XMLHttpRequest.responseText+" - "+textStatus+" - "+errorThrown,'error' );
        }
    }).then(function(){
        $('#chperros-okBtn').linkbutton('enable');
    });
}

/**
 * Ejecuta la peticion json para anyadir/editar un perro
 */
function saveDog(){
    var frm = $('#perros-form');
    $('#perros-Federation').val(workingData.federation);
    if (!frm.form('validate')) return; // don't call inside ajax to avoid override beforeSend()

    $('#perros-okBtn').linkbutton('disable');
    $.ajax({
        type: 'GET',
        url: '../ajax/database/dogFunctions.php',
        data: frm.serialize(),
        dataType: 'json',
        success: function (result) {
            if (result.errorMsg){
                $.messager.show({ width:300,height:200, title: '<?php _e('Error'); ?>', msg: result.errorMsg });
            } else {
                // reload the dog data from inscripciones (if any)
            	if (isDefined('listaNoInscritos')) listaNoInscritos();
            	if(result.insert_id && (frm.operation==="insert") ) $('#perros-ID').val(result.insert_id);
    	        // close the dialog
                $('#perros-dialog').dialog('close'); 
            }
        },
        error: function(XMLHttpRequest,textStatus,errorThrown) {
            $.messager.alert("Save Dog","Error:"+XMLHttpRequest.status+" - "+XMLHttpRequest.responseText+" - "+textStatus+" - "+errorThrown,'error' );
        }
    }).then(function(){
        $('#perros-okBtn').linkbutton('enable');
    });
}

/**
 * Pregunta al usuario si quiere importar o exportar a excel la lista de perros
 */
function perros_importExportDogs() {
    $.messager.radio(
        '<?php _e("Import/Export"); ?>',
        '<?php _e("Import/Export dog data from/to Excel file"); ?>:<br/>&nbsp;<br/>',
        {
            0:'*<?php _e("Create Excel file with current search/sort criteria"); ?>',
            1:'<?php _e("Update database with imported data from Excel file"); ?>'
        },
        function(r){
            if (!r) return false;
            switch(parseInt(r)){
                case 0:
                    print_listaPerros('excel');
                    break;
                case 1:
                    // import
                    $('#importdialog').dialog('open');
                    return false; // prevent default fireup of event trigger
                    break;
            }
        }).window('resize',{width:550});
    return false; //this is critical to stop the click event which will trigger a normal file download!
}