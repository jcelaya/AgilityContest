<?php header('Content-Type: text/javascript'); ?>
/*
 guias.js

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

// ***** gestion de guias		*********************************************************

/**
 * Abre el formulario para anyadir guias a un club
 *@param {String} dgname: Identificador del elemento ( datagrid) desde el que se invoca esta funcion
 *@param {object} club: datos del club
 */
function assignGuiaToClub(dgname,club) {
	// clear data forms
	$('#chguias-header').form('clear'); // erase header form
	$('#chguias-Search').combogrid('clear'); // reset header combogrid
	$('#chguias-form').form('clear'); // erase data form
	// fill default values
	$('#chguias-newClub').val(club.ID); // id del club to assign
	$('#chguias-Operation').val('update'); // operation
    $('#chguias-parent').val(dgname);

    // finalmente desplegamos el formulario y ajustamos textos
	$('#chguias-title').text('<?php _e('Reassign/Declare a handler as belonging to club'); ?>'+' '+club.Nombre);
	$('#chguias-dialog').dialog('open').dialog('setTitle','<?php _e('Assign/Register a handler'); ?>'+' - '+fedName(workingData.federation));
}

/**
 * Abre el formulario de edicion de guias para cambiar los datos de un guia preasignado a un club
 * @param {string} dg datagrid ID de donde se obtiene el guia
 * @param {object} club datos del club
 */
function editGuiaFromClub(dg, club) {
    var row = $(dg).datagrid('getSelected');
    if (!row) {
    	$.messager.alert('<?php _e("Delete error"); ?>','<?php _e("There is no handler selected"); ?>',"warning");
    	return; // no way to know which guia is selected
    }
    // add extra needed parameters to dialog
    row.Club=club.ID;
    row.NombreClub=club.Nombre;
    row.Operation='update';
    $('#guias-form').form('load',row);
    $('#guias-dialog').dialog('open').dialog('setTitle','<?php _e('Modify data on handler belonging to club'); ?>'+' '+club.Nombre+' - '+fedName(workingData.federation));
	// on click OK button, close dialog and refresh data
	$('#guias-okBtn').one('click',function () { $(dg).datagrid('reload'); } ); 
}

/**
 * Quita la asignacion del guia marcado al club indicado
 * Invocada desde el menu de clubes
 * @param {string} dg datagrid ID de donde se obtiene el guia
 * @param {object} club datos del club
 * @param {function} onAccept what to do (only once) when window gets closed
 */
function delGuiaFromClub(dg,club) {
    var row = $(dg).datagrid('getSelected');
    if (!row){
    	$.messager.alert('<?php _e("Delete error"); ?>','<?php _e("There is no handler selected"); ?>',"warning");
    	return; // no way to know which guia is selected
    }
    $.messager.confirm('<?php _e('Confirm'); ?>','<?php _e("Delete assignation for handler"); ?>'+" '"+row.Nombre+"' "+'<?php _e("to club"); ?>'+" '"+club.Nombre+"' "+'<?php _e("Sure?"); ?>'+"'",function(r){
        if (r){
            $.get('../ajax/database/guiaFunctions.php',{'Operation':'orphan','ID':row.ID},function(result){
                if (result.success){
                	$(dg).datagrid('clearSelections');
                	$(dg).datagrid('reload');
                } else {
                	// show error message
                    $.messager.show({ title: '<?php _e('Error'); ?>', width: 300, height: 200, msg: result.errorMsg });
                }
            },'json');
        }
    });
}

function reload_guiasDatagrid() {
	var w=$('#guias-datagrid-search').val();
	if (strpos(w,"<?php _e('-- Search --'); ?>")!==false) w='';
	setTimeout(function(){
        $('#guias-datagrid').datagrid('load',{ Operation: 'select', where: w, Federation: workingData.federation });
    },500);
}

/**
 * Abre el dialogo para crear un nuevo guia
 * @param {string} def valor por defecto para el campo nombre
 * @param {function} onAccept what to do (only once) when window gets closed
 */
function newGuia(def,onAccept){
	$('#guias-dialog').dialog('open').dialog('setTitle','<?php _e('New handler'); ?>'+' - '+fedName(workingData.federation));
	$('#guias-form').form('clear');
	if (strpos(def,"<?php _e('-- Search --');?>")===false) $('#guias-Nombre').textbox('setValue',def.capitalize());
	$('#guias-Operation').val('insert');
	$('#guias-Parent').val('');
	// if defined, add event manager
	if (onAccept!==undefined) $('#guias-okBtn').one('click',onAccept);
}

/**
 * Abre el dialogo para editar un guia ya existente
 * @param {string} dg datagrid ID de donde se obtiene el guia
 * @param {object} row datagrid row to be edited on dblClick. may be undefined
 */
function editGuia(dg,row){
	if ($('#guias-datagrid-search').is(":focus")) return; // on enter key in search input ignore
    if (typeof(row)=="undefined") {
        var rows = $(dg).datagrid('getSelections');
        if (rows.length==0) {
            $.messager.alert('<?php _e("Edit Error"); ?>','<?php _e("There is no handler selected"); ?>',"warning");
            return; // no way to know which dog is selected
        }
        if (rows.length>1) {
            $.messager.alert('<?php _e("Edit Error"); ?>','<?php _e("Too many selected handlers"); ?>',"warning");
            return; // no way to know which dog is selected
        }
        row=rows[0];
    }
    $('#guias-dialog').dialog('open').dialog('setTitle','<?php _e('Modify handler data'); ?>'+' - '+fedName(workingData.federation));
    // add extra required parameters to dialog
    row.Parent='';
    row.Operation='update';
    // stupid trick to make dialog's clubs combogrid display right data
    $('#guias-form').form('load',row); // load row data into guia edit form
    // on accept, display correct data
    $('#guias-okBtn').one('click',reload_guiasDatagrid);
}

function editGuiaFromPerros(){ // editar guia desde el dialogo de edicion de perros
    // convert a jquery form to a (json) object
    function formToObject(form){
        var array = form.serializeArray();
        var json = {};
        jQuery.each(array, function() { json[this.name] = this.value || '';  });
        return json;
    }

    var pg=$('#perros-Guia');
    var dg=pg.combogrid('grid');
    var r=dg.datagrid('getSelected');
    if (!r) {
        $.messager.alert('<?php _e("Edit error"); ?>','<?php _e("There is no handler selected"); ?>',"warning");
        return false;
        /*
        // no selection, look for valid data in combogrid
        var id=pg.combogrid('getValue');
        if (isNaN(id)) return false;
        dg.datagrid('selectRecord',id);
        r=dg.datagrid('getSelected');
        */
    }
    $('#guias-dialog').dialog('open').dialog('setTitle','<?php _e('Modify handler data'); ?>'+' - '+fedName(workingData.federation));
    // add extra required parameters to dialog
    $('#guias-Operation').val('update');
    // stupid trick to make dialog's clubs combogrid display right data
    $('#guias-form').form('load',r); // load row data into guia edit form

    // on accept, display correct data in modify dog data
    $('#guias-okBtn').one('click',function(){
        // locate datagrid index for data being edited
        var idx = dg.datagrid('getRowIndex',dg.datagrid('getSelected'));
        // retrieve new club name
        var cname=$('#guias-Club').combogrid('grid').datagrid('getSelected');
        if (!cname) return;
        var r=formToObject($('#guias-form'));
        r.NombreClub=cname.Nombre; // add additional field not present in form
        // update datagrid entry at idx with new values
        dg.datagrid('updateRow',{index:idx,row:r});
        // tell combogrid to search and display guiaID (stored at datagrid position idx)
        pg.combogrid('setValue',r.ID);
        // finally fix club name on textbox
        $('#perros-Club').textbox('setValue',cname.Nombre);
    });
}

/**
 * Borra el perro seleccionado de la base de datos
 * @param {string} dg datagrid ID de donde se obtiene el perro
 */
function deleteGuia(dg){
    var rows = $(dg).datagrid('getSelections');
    if (rows.length==0) {
        $.messager.alert('<?php _e("Edit Error"); ?>','<?php _e("There is no handler selected"); ?>',"warning");
        return; // no way to know which handler is selected
    }
    for(var n=0;n<rows.length;n++) {
        if (rows[n].ID==1) {
            $.messager.alert('<?php _e("Delete error"); ?>','<?php _e("Cannot mark default handler to be deleted"); ?>',"error");
            return; // cannot delete default entry
        }
    }
    var msg="<?php _e('You are about to delete following handlers(s) from database');?><br/><?php _e('Are you sure?');?><br/>";
    var lista= '<br/><table width="100%">'+
        '<tr><th>ID</th><th><?php _e("Name");?></th><th><?php _e("Category");?></th>'+'<th><?php _e("Club");?></th></tr>';

    for(n=0;n<rows.length;n++) {
        lista+="<tr>";
        var row=rows[n];
        var cat=toHandlerCategoria(row.Categoria,workingData.federation);
        lista +="<td>"+row.ID+"</td><td>"+row.Nombre+"</td><td> "+cat+"</td><td>"+row.NombreClub+"</td></tr>";
    }
    lista+="</table>"

    $.messager.confirm('<?php _e('Delete'); ?>',msg+lista,function(r){
        if (!r) return;
        $.each(rows,function(index,row){
            $.get('../ajax/database/guiaFunctions.php',{ Operation: 'delete', ID: row.ID },function(result){
                var nombre=row.Nombre;
                if (result.success){
                    $(dg).datagrid('reload');    // reload the guia data
                } else { // show error message
                    var errormsg="Cannot delete handler: "+row.Nombre+":<br/>&nbsp<br/>"+result.errorMsg;
                    $.messager.show({ width:300, height:200, title: 'Error',  msg: errormsg });
                }
            },'json');
        });
    }).window('resize',{width:480,height:'auto'});
}

/**
 * une los guias seleccionados en uno
 * @param {string} dg datagrid ID de donde se obtienen los guias a unir
 */
function joinGuia(dg){
    var rows = $(dg).datagrid('getSelections');
    if (rows.length==0) {
        $.messager.alert('<?php _e("Join Error"); ?>','<?php _e("There are no selected handlers"); ?>',"info");
        return; // no way to know which dog is selected
    }
    if (rows.length<2) {
        $.messager.alert('<?php _e("Join Error"); ?>','<?php _e("Need to select at least two handlers"); ?>',"info");
        return; // no way to know which dog is selected
    }
    for(var n=0;n<rows.length;n++) {
        if (rows[n].ID==1) {
            $.messager.alert('<?php _e("Join error"); ?>','<?php _e("Cannot mark default handler to be joined"); ?>',"error");
            return; // cannot delete default entry
        }
    }
    var msg="<?php _e('Please select the handler that will remain after join');?><br/><?php _e('Press accept to proceed');?><br/>";
    var lista= '<br/><form id="handlers_join_form"><table width="100%">'+
        '<tr><th>&nbsp;</th><th>ID</th><th><?php _e("Name");?></th><th><?php _e("Category");?></th>'+'<th><?php _e("Club");?></th></tr>';

    var selection="BEGIN";
    for(n=0;n<rows.length;n++) {
        var row=rows[n];
        selection = selection + "," +row.ID;
        var input='<input type="radio" name="join_handlerid" value="'+row.ID+'">';
        var cat=toHandlerCategoria(row.Categoria,workingData.federation);
        lista +="<tr>";
        lista +="<td>"+input+"</td><td>"+row.ID+"</td><td>"+row.Nombre+"</td><td> "+cat+"</td><td>"+row.NombreClub+"</td>";
        lista +="</tr>";
    }
    selection += ",END";
    lista+="</table></form>";

    $.messager.confirm('<?php _e('Join handlers'); ?>',msg+lista,function(r){
        if (!r) return;
        if (typeof ($('input[name=join_handlerid]:checked').val() ) == "undefined") {
            $.messager.show({ title: 'Error',  msg: 'No handler selected for join' });
            return;
        }
        var selected=$('input[name=join_handlerid]:checked').val();
        $.ajax({
            type: 'GET',
            url: '../ajax/database/guiaFunctions.php',
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
 * Invoca a json para añadir/editar los datos del guia seleccionado en el formulario
 * Ask for commit new/edit guia to server
 */
function assignGuia(){
	$('#chguias-Club').val($('#chguias-newClub').val());
    $('#chguias-Operation').val('update');
    $('#chguias-Federation').val(workingData.federation);
    var frm = $('#chguias-form');
    if (! frm.form('validate')) return;

    // do not allow re-clicking
    $('#chguias-okBtn').linkbutton('disable');
    $('#chguias-newBtn').linkbutton('disable');
    $.ajax({
        type: 'GET',
        url: '../ajax/database/guiaFunctions.php',
        data: frm.serialize(),
        dataType: 'json',
        success: function (result) {
            if (result.errorMsg){ 
            	$.messager.show({width:300, height:200, title:'<?php _e('Error'); ?>',msg: result.errorMsg });
            } else {
                // TODO: study why datagrid loses focus handling
                var dg=$('#chguias-parent').val();
                if (dg!="") $(dg).datagrid('load');
                $('#chguias-Search').combogrid('clear');  // clear search field
                $('#chguias-dialog').dialog('close');        // close the dialog
            }
        },
        error: function(XMLHttpRequest,textStatus,errorThrown) {
            $.messager.alert("Assign Guia","Error:"+XMLHttpRequest.status+" - "+XMLHttpRequest.responseText+" - "+textStatus+" - "+errorThrown,'error' );
        }
    }).then(function(){
        $('#chguias-okBtn').linkbutton('enable');
        $('#chguias-newBtn').linkbutton('enable');
    });
}

/**
 * Anyade (new) un nuevo guia desde el menu de reasignacion de guia
 */
function saveChGuia(){
    var frm = $('#chguias-form');
	$('#chguias-Club').val($('#chguias-newClub').val());
    $('#chguias-Operation').val('insert');
    $('#chguias-Federation').val(workingData.federation);
    if (!frm.form('validate')) return; // don't call inside ajax to avoid override beforeSend()

    $('#chguias-okBtn').linkbutton('disable');
    $('#chguias-newBtn').linkbutton('disable');
    $.ajax({
        type: 'GET',
        url: '../ajax/database/guiaFunctions.php',
        data: frm.serialize(),
        dataType: 'json',
        success: function (result) {
            if (result.errorMsg){
                $.messager.show({ width:300,height:200, title: '<?php _e('Error'); ?>', msg: result.errorMsg });
            } else {
                // TODO: study why load makes next use focus fail on new datagrid
                var dg=$('#chguias-parent').val();
                if (dg!="") $(dg).datagrid('load');
            	if (result.insert_id ) $('#guias-ID').val(result.insert_id);
            	$('#chguias-Search').combogrid('clear');  // clear search field
                $('#chguias-dialog').dialog('close');    // close the dialog
            }
        },
        error: function(XMLHttpRequest,textStatus,errorThrown) {
            $.messager.alert("Save ChGuia","Error:"+XMLHttpRequest.status+" - "+XMLHttpRequest.responseText+" - "+textStatus+" - "+errorThrown,'error' );
        }
    }).then(function(){
        $('#chguias-okBtn').linkbutton('enable');
        $('#chguias-newBtn').linkbutton('enable');
    });
}

/**
 * Invoca a json para añadir/editar los datos del guia seleccionado en el formulario
 * Ask for commit new/edit guia to server
 */
function saveGuia(){
	// use $.ajax() instead of form('submit') to assure http request header is sent
    $('#guias-Federation').val(workingData.federation);
    var frm = $('#guias-form');
    if (!frm.form('validate')) return;

    $('#guias-okBtn').linkbutton('disable');
	$.ajax({
        type: 'GET',
        url: '../ajax/database/guiaFunctions.php',
        data: frm.serialize(),
        dataType: 'json',
        success: function (result) {
            if (result.errorMsg){ 
            	$.messager.show({width:300, height:200, title:'<?php _e('Error'); ?>',msg: result.errorMsg });
            } else {
            	var oper=$('#guias-Operation').val();
            	// notice that onAccept() already refresh parent dialog
            	if(result.insert_id && (oper==="insert") ) $('#guias-ID').val(result.insert_id);
                $('#guias-dialog').dialog('close');        // close the dialog
            }
        },
        error: function(XMLHttpRequest,textStatus,errorThrown) {
            $.messager.alert({
                title: "Save Guia",
                msg: "Error:"+XMLHttpRequest.status+" - "+XMLHttpRequest.responseText+" - "+textStatus+" - "+errorThrown,
                icon: 'error',
                fn: function() {
                    $('#guias-okBtn').linkbutton('enable');
                }
            });
        }
    }).then(function(){
        $('#guias-okBtn').linkbutton('enable');
    });
}
