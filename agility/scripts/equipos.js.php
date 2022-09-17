<?php header('Content-Type: text/javascript'); ?>
/*
 equipos.js

Copyright  2013-2021 by Juan Antonio Martinez ( juansgaviota at gmail dot com )

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

// ***** gestion de equipos de una prueba	*****************************************************

function buscaEquipos() {
	$('#team_datagrid').datagrid( 'load', { 
		where: ($('#team_datagrid-search').val()==='<?php _e('-- Search --'); ?>')? '' : $('#team_datagrid-search').val()
		}
	);
}

/**
* genera una tabla con encabezado
*@param {array} data list of { team_name, #dogs }
*@param {string} cabecera table header string
*/
function checkTeamsCompose(data,cabecera) {
    var str="<p><strong>"+cabecera+": "+data.length+"</strong>";
    if (data.length==0) return str+"</p>";
    str +="<table>";
    // componemos lista de equipos y numero de perros
    $.each(
        data,
        function(index,val) {
            str+="<tr><td>Equipo: '"+val['Nombre']+"'</td><td>"+val['Numero']+" perro(s)</td></tr>";
        }
    );
    str+="</table></p>";
    return str;
}

/*
* Verifica los equipos chequeando numero de miembros y que no haya ningun perro asignado al equipo por defecto
*/
function checkTeams(datagrid) {
    // verificamos que no haya participantes en el equipo por defecto,
    // ni equipos con mas o menos perros de lo debido
    $.ajax({
        type:'GET',
        url:"../ajax/database/equiposFunctions.php",
        dataType:'json',
        data: {
            Operation: 'verify',
            Prueba:	workingData.prueba,
            Jornada: workingData.jornada
        },
        success: function(data) {
            if (data.errorMsg) {
                $.messager.alert('<?php _e("Error"); ?>',data.errorMsg,"error");
                return false;
            }
            var a=getTeamDogs()
            var str ="<h4><?php _e('Registered teams revision'); ?>";
                str+="<br /><?php _e('Journey');?> '"+workingData.datosJornada.Nombre;
                str+="' ("+a[2]+")</h4>";
            str +="<p><strong><?php _e('Number of teams'); ?>: "+(data['teams'].length)+"</strong></p>";
            if (typeof(data['default'][0])!=="undefined") {
                str+="<p><strong><?php _e('Dogs without assigned team'); ?>: "+data['default'][0]['Numero']+"</strong></p>";
            }
            str+=checkTeamsCompose(data['more'],'<?php _e('Teams with excess dogs'); ?>');
            str+=checkTeamsCompose(data['less'],'<?php _e('Incomplete teams'); ?>');
            var w=$.messager.alert("Verificar",str,"info");
            w.window('resize',{width:450}).window('center');
            return false; // prevent default fireup of event trigger
        }
    });
    return false; //this is critical to stop the click event which will trigger a normal file download!
}


/*
* imprime los equipos de la jornada y los miembros de cada equipo
*/
function realPrintTeams() {
    var options=$('#team_datagrid').datagrid('options');
    var search=$('#team_datagrid-search').val();
    if (search==="<?php _e('-- Search --')?>") search="";
    $.fileDownload(
        '../ajax/pdf/print_equiposByJornada.php',
        {
            httpMethod: 'GET',
            data: {
                Prueba: workingData.prueba,
                Jornada: workingData.jornada,
                sort: options.sortName,
                order: options.sortOrder,
                where: search
            },
            preparingMessageHtml: '<?php _e("Printing team lists; please wait"); ?> ...',
            failMessageHtml: '<?php _e("There was a problem generating your report, please try again."); ?>'
        }
    );
}

/*
* Comprueba la consistencia de los datos de equipos y en caso
* si no hay inconsistencias, manda imprimir
* si hay inconsistencias, las presenta y pregunta si a pesar de todo se quiere imprimir
*/
function printTeams(datagrid) {
    // primero verificamos la lista de equipos
    $.ajax({
        type: 'GET',
        url: "../ajax/database/equiposFunctions.php",
        dataType: 'json',
        data: {
        Operation: 'verify',
        Prueba:	workingData.prueba,
        Jornada: workingData.jornada
        },
        success: function(data) {
            var flag=false;
            if (data.errorMsg) {
                $.messager.alert('<?php _e("Error"); ?>',data.errorMsg,"error");
                return false;
            }

            var a=getTeamDogs()
            var str ="<h4><?php _e('Registered teams revision'); ?>";
            str+="<br /><?php _e('Journey');?> '"+workingData.datosJornada.Nombre;
            str+="' ("+a[2]+")</h4>";
            str +="<p><strong><?php _e('Number of teams'); ?>: "+(data['teams'].length)+"</strong></p>";
            if (typeof(data['default'][0])!=="undefined") {
                str+="<p><strong><?php _e('Dogs without assigned team'); ?>: "+data['default'][0]['Numero']+"</strong></p>";
                flag=true;
            }
            str+=checkTeamsCompose(data['more'],'<?php _e('Teams with excess dogs'); ?>');
            str+=checkTeamsCompose(data['less'],'<?php _e('Incomplete teams'); ?>');
            str+="<p><em><?php _e('Print anyway'); ?>?</em></p>";
            // si hay errores presentamos alerta y preguntamos si se quiere continuar
            if (data['more'].length>0) flag=true;
            if (data['less'].length>0) flag=true;
            if (flag==false) { realPrintTeams(); return false; }
            var w=$.messager.confirm('<?php _e("Found problems"); ?>',str,function(r){
                if (r) realPrintTeams();
            });
            w.window('resize',{width:450}).window('center');
            return false; // prevent default fireup of event trigger
        }
    });
    return false; // this is critical to stop the click event which will trigger a normal file download!
}


/**
 * Abre un dialogo para declarar un nuevo equipo para la prueba 
 */
function openTeamWindow(pruebaID) {
	// buscamos la jornada seleccionada
	var row=$('#inscripciones-jornadas').datagrid('getSelected');
	// si no hay jornada por equipos seleccionada indicamos error
	if (row===null) {
		$.messager.alert("Error:","<?php _e('You must select a journey with team rounds');?>","error");
		return;
	}
	if ( (row.Equipos3==0) && (row.Equipos4==0) ) {
		$.messager.alert("Error:","<?php _e('Selected journey has no team rounds');?>","error");
        return;
	}
    // comprobamos si tenemos permiso para manejar jornadas por equipos
    setJornada(row);
    check_access(access_perms.ENABLE_TEAMS,function(res) {
        if (res.errorMsg) {
            $.messager.alert('<?php _e("Current License has no permissions to handle Team Journeys"); ?>',res.errorMsg,"error");
        } else {
            // allright: marcamos jornada como activa, recargamos lista de equipos y abrimos ventana
            $('#team_datagrid').datagrid('load',{ Operation: 'select', Prueba: workingData.prueba, Jornada: workingData.jornada, where:''});
            $('#team_datagrid-dialog').dialog('open');
        }
        return false; // prevent default fireup of event trigger
    });
    return false;
}

/**
 *Open dialogo de alta de equipos
 *@param {string} dg datagrid ID de donde se obtiene el equipo y el id de prueba
 *@param {string} def default value to insert into Nombre 
 *@param {function} onAccept what to do when a new team is created
 */
function newTeam(dg,def,onAccept){
	$('#team_edit_dialog').dialog('open').dialog('setTitle','A&ntilde;adir nuevo equipo');
	$('#team_edit_dialog-form').form('clear');
	if (strpos(def,"<?php _e('-- Search --'); ?>")===false) $('#team_edit_dialog-Nombre').textbox('setValue',def.capitalize());// fill team Name
	$('#team_edit_dialog-Operation').val('insert');
	$('#team_edit_dialog-Prueba').val(workingData.prueba);
	$('#team_edit_dialog-Jornada').val(workingData.jornada);
    // notice that on "new" window must be explicitely closed, so don't add close-on-ok() code
	if (onAccept!==undefined)$('#team_edit_dialog-okBtn').one('click',onAccept);
}

/**
 * Open dialogo de modificacion de equipos
 * @param {string} dg datagrid ID de donde se obtiene el equipo a editar
 */
function editTeam(dg){
	if ($('#team_datagrid-search').is(":focus")) return; // on enter key in search input ignore
    var row = $(dg).datagrid('getSelected');
    if (!row) {
    	$.messager.alert('<?php _e("Edit Error"); ?>','<?php _e("There is no team selected"); ?>',"info");
    	return; // no way to know which prueba is selected
    }
    if (row.Nombre==="-- Sin asignar --") {
	$.messager.alert('<?php _e("Edit Error"); ?>',"<?php _e('Default team cannot be edited');?>","error");
    	return; // no way to know which prueba is selected
    }
    $('#team_edit_dialog').dialog('open').dialog('setTitle','<?php _e('Modify team data'); ?>');
	row.Operation="update";
    // tel window to be closed when "OK" clicked
    $('#team_edit_dialog-okBtn').one('click',function() {$('#team_edit_dialog').dialog('close');});
	// load team edit dialog with provided data   
	$('#team_edit_dialog-form').form('load',row);
	// fill properly checkboxes
    $('#team_edit_dialog-X').prop('checked',(row.Categorias.indexOf('L')<0)?false:true);
    $('#team_edit_dialog-L').prop('checked',(row.Categorias.indexOf('L')<0)?false:true);
	$('#team_edit_dialog-M').prop('checked',(row.Categorias.indexOf('M')<0)?false:true);
	$('#team_edit_dialog-S').prop('checked',(row.Categorias.indexOf('S')<0)?false:true);
	$('#team_edit_dialog-T').prop('checked',(row.Categorias.indexOf('T')<0)?false:true);
}

/**
 * Delete data related with a team in BBDD
 * @param {string} dg datagrid ID de donde se obtiene el teamID y la pruebaID
 */
function deleteTeam(dg){
    var row = $(dg).datagrid('getSelected');
    if (!row) {
    	$.messager.alert('<?php _e("Delete error"); ?>','<?php _e("There is no team selected"); ?>',"info");
    	return; // no way to know which prueba is selected
    }
    if (row.Nombre==="-- Sin asignar --") {
		$.messager.alert('<?php _e("Delete error"); ?>',"<?php _e('Default team cannot be deleted');?>","error");
    	return; // no way to know which prueba is selected
    }
    $.messager.confirm('Confirm',
			"<p><?php _e('This operation will remove selected team from journey');?><br />"+
			"<p><?php _e('Do you really want to delete team');?> '"+row.Nombre+"' <?php _e('from this journey');?>?</p>",function(r){
        if (r){
            $.get('../ajax/database/equiposFunctions.php',{Operation: 'delete', ID: row.ID, Prueba: row.Prueba, Jornada: row.Jornada},function(result){
                if (result.success){
                    $(dg).datagrid('load',{ Operation: 'select', Prueba: workingData.prueba, Jornada: workingData.jornada, where:''});    // reload the prueba data
                    $('#selteam-Equipo').combogrid('grid').datagrid('clearSelections'); // update assignment combogrid list
                    $('#selteam-Equipo').combogrid('grid').datagrid('load');
                } else {
                    $.messager.show({ width:300, height:200, title:'Error', msg:result.errorMsg });
                }
            },'json');
        }
    }).window('resize',{width:500});
}

/**
 * Un inscribe team members from current journey
 * ( to easy delete non qualified team members in final team rounds )
 * @param {string} dg datagrid ID de donde se obtiene el teamID y la pruebaID
 */
function deleteTeamMembers(dg) {

    function getProgress(){
        $.ajax({
            url:"../ajax/database/equiposFunctions.php",
            dataType:'json',
            data: {
                Operation: 'progress',
                ID: row.ID,
                Prueba: row.Prueba,
                Jornada: row.Jornada,
                Suffix: suffix
            },
            success: function(data) {
                if(data.status!=="Done."){
                    var bar = $.messager.progress('bar');  // get the progressbar object
                    bar.progressbar('setValue',data.status);  // set new progress value
                    setTimeout(getProgress,200);
                } else {
                    $.messager.progress('close');
                }
            },
            error: function(XMLHttpRequest,textStatus,errorThrown) {
                $.messager.progress('close');
                $.messager.alert("Delete Team Members","Error:"+XMLHttpRequest.status+" - "+XMLHttpRequest.responseText+" - "+textStatus+" - "+errorThrown,'error' );
            }
        });
    }

    function doAjaxCall(suffix) {
        $.ajax({
            type: 'GET',
            url: '../ajax/database/equiposFunctions.php',
            data: {
                Operation: 'unsubscribe',
                ID: row.ID,
                Prueba: row.Prueba,
                Jornada: row.Jornada,
                Suffix: suffix
            },
            dataType: 'json',
            success: function (result) {
                if (result.success) {
                    $(dg).datagrid('load', {
                        Operation: 'select',
                        Prueba: workingData.prueba,
                        Jornada: workingData.jornada,
                        where: ''
                    });    // reload the prueba data
                    $('#selteam-Equipo').combogrid('grid').datagrid('clearSelections');
                    $('#selteam-Equipo').combogrid('grid').datagrid('load'); // update assignment combogrid list
                } else {
                    $.messager.show({width: 300, height: 200, title: 'Error', msg: result.errorMsg});
                }
            }
        });
    }

    var suffix= getRandomString(8);
    var row = $(dg).datagrid('getSelected');
    if (!row) {
        $.messager.alert('<?php _e("Delete error"); ?>','<?php _e("There is no team selected"); ?>',"info");
        return false; // no way to know which prueba is selected
    }
    $.messager.confirm('Confirm',
        "<p><?php _e('This operation will un-inscribe members of selected team from current journey');?><br />"+
        "<p><?php _e('Do you really want to un-inscribe them');?> ?</p>",function(r){
            if (!r) return false;
            $.messager.progress({ // creamos una progress bar
                msg: "<?php _e('Processing')?>...",
                title: '<?php _e('Uninscribe team member'); ?>',
                interval: 0
            });
            doAjaxCall(suffix); // call ajax operation
            setTimeout(getProgress,200); // start progress monitoring
        }).window('resize',{width:500});
}

/**
 * Save Team being edited, as result of doneBtn.onClick()
 * On success refresh every related datagrids
 */
function saveTeam() {
	// get and validate form data
    var frm = $('#team_edit_dialog-form');
    if (! frm.form('validate')) return;
	// evaluate 'Categorias' field
	var cat='';
    if ( $('#team_edit_dialog-X').is(':checked') ) cat+='X';
    if ( $('#team_edit_dialog-L').is(':checked') ) cat+='L';
	if ( $('#team_edit_dialog-M').is(':checked') ) cat+='M';
	if ( $('#team_edit_dialog-S').is(':checked') ) cat+='S';
	if ( $('#team_edit_dialog-T').is(':checked') ) cat+='T';
	$('#team_edit_dialog-Categorias').val(cat);
    if (cat==="") {
        $.messager.alert("Error",'<?php _e("This team has no assigned categories");?>',"error");
        return false;
    }
    $('#team_edit_dialog-okBtn').linkbutton('disable');
    $.ajax({
        type: 'GET',
        url: '../ajax/database/equiposFunctions.php',
        data: frm.serialize(),
        dataType: 'json',
        success: function (result) {
            if (result.errorMsg){
                $.messager.show({width:300, height:200, title:'Error',msg: result.errorMsg });
            } else {// on submit success, reload results
                // on save done refresh related data/combo grids
                $('#team_edit_dialog').dialog('close');
                $('#team_datagrid').datagrid('load',{ Operation: 'select', Prueba: workingData.prueba, Jornada: workingData.jornada, where:''});
            }
        },
        error: function(XMLHttpRequest,textStatus,errorThrown) {
            $.messager.alert("Save Team","Error:"+XMLHttpRequest.status+" - "+XMLHttpRequest.responseText+" - "+textStatus+" - "+errorThrown,'error' );
        }
    }).then(function(){
        $('#team_edit_dialog-okBtn').linkbutton('enable');
    });
    return false;
}

/**
* Open assign team dialog
* @param {string} dg parent datagrid name
* @param {array} row selected datagrid data
*/
function changeTeamDialog(dg,row) {
	// cogemos datos de la inscripcion a modificar
	// actualizamos lista de equipos en el combogrid
	$('#selteam-Equipo').combogrid('grid').datagrid('load',{ Operation: 'select', Prueba: workingData.prueba, Jornada: workingData.jornada});
	// ajustamos variables extras del formulario
    row.Parent=dg;
	// recargamos el formulario con los datos de la fila seleccionada
    $('#selteam-Form').form('load',row); // onLoadSuccess takes care on combogrid
    var data=$(dg).datagrid('getSelections');
    $('#selteam-datagrid').datagrid('loadData',data);
	// desplegamos formulario 
    $('#selteam-window').window('open');
}

/**
 * Ask for commit new inscripcion to server
 * @param {string} dg datagrid to retrieve selections from
 */
function changeTeam() {

    function handleChangeTeam(rows,index,size) {
        if (index>=size){  // recursive call finished, clean, close and refresh
            pwindow.window('close');  // close progress bar
            var parent=$('#selteam-Parent').val();
            $(parent).datagrid('reload'); // refresh parent datagrid
            $('#selteam-window').window('close'); // and close change team window
            return;
        }
        $('#selteam-progresslabel').text('<?php _e("Setting team for"); ?>'+": "+rows[index].Nombre);
        $('#selteam-progressbar').progressbar('setValue', (100.0*(index+1)/size).toFixed(2));
        $('#selteam-Perro').val(rows[index].Perro);
        $.ajax({
            cache: false,
            timeout: 20000, // 20 segundos
            type:'GET',
            url: '../ajax/database/equiposFunctions.php',
            dataType:'json',
            data: frm.serialize(),
            success: function(result) {
                if (result.errorMsg){
                    $.messager.show({width:300, height:200, title:'Error',msg: result.errorMsg });
                    handleChangeTeam(rows,index+1,0); // force end
                } else {
                    handleChangeTeam(rows,index+1,size);
                }
            }
        });
    }

    var pwindow=$('#selteam-progresswindow');

    // si no hay ninguna equipo valido seleccionada aborta
    var p=$('#selteam-Equipo').combogrid('grid').datagrid('getSelected');
    if (p==null) {
        // indica error
        $.messager.alert('<?php _e("Error"); ?>',"<?php _e('You must select a valid team');?>","error");
        return;
    }
    $('#selteam-ID').val(p.ID);

    // si no hay ningun perro marcado para cambiar de equipo aborta
    var frm = $('#selteam-Form');
    var parent=$('#selteam-Parent').val();
    var selectedRows= $(parent).datagrid('getSelections');
    var size=selectedRows.length;
    if(size==0) {
        $.messager.alert('<?php _e("No selection"); ?>','<?php _e("There is no marked dog to change team for"); ?>',"warning");
        return; // no hay ningun perro seleccionado para cambiar de equipo
    }

    // si no tienes permiso para la operación, que te den :-)
    if (ac_authInfo.Perms>2) {
        $.messager.alert('<?php _e("No permission"); ?>','<?php _e("Current user has not enought permissions to perform operation"); ?>',"error");
        return; // no tiene permiso para realizar cambios de equipo: retornar
    }
    pwindow.window('open');
    handleChangeTeam(selectedRows,0,size);
}

// reajusta el orden de salida de los equipos
// poniendo el idequipo "from" delante (where==0) o detras (where==1) del idequipo "to"
// al retornar la funcion se invoca whenDone, que normalmente recargara el formulario padre
function dragAndDropOrdenEquipos(from,to,where,whenDone) {
    if (workingData.prueba==0) return;
    if (workingData.jornada==0) return;
    if (workingData.manga==0) return;
    $.ajax({
        type:'GET',
        url:"../ajax/database/ordenSalidaFunctions.php",
        dataType:'json',
        data: {
            Operation: 'dndTeams',
            Prueba: workingData.prueba,
            Jornada: workingData.jornada,
            Manga: workingData.manga,
            From: from,
            To: to,
            Where: where
        }
    }).done( function(msg) {
        if (whenDone) whenDone();
    });
}