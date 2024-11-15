<?php header('Content-Type: text/javascript'); ?>
/*
 jueces.js

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

//***** gestion de jueces		*********************************************************

function juecesRSCE(val,row,idx) { return ( (parseInt(row.Federations)&1)==0)?" ":"&#x2714;"; }
function juecesRFEC(val,row,idx) { return ( (parseInt(row.Federations)&2)==0)?" ":"&#x2714;"; }
function juecesNat3(val,row,idx)  { return ( (parseInt(row.Federations)&8)==0)?" ":"&#x2714;"; }
function juecesNat4(val,row,idx)  { return ( (parseInt(row.Federations)&4)==0)?" ":"&#x2714;"; }
function juecesCPC(val,row,idx) { return ( (parseInt(row.Federations)&16)==0)?" ":"&#x2714;"; }
function juecesNat5(val,row,idx)  { return ( (parseInt(row.Federations)&32)==0)?" ":"&#x2714;"; }
function juecesInternacional(val,row,idx)  { return ( parseInt(row.Internacional)==0)?" ":"&#x2714;"; }
function juecesPracticas(val,row,idx)  { return ( parseInt(row.Practicas)==0)?" ":"&#x2714;"; }
function juecesBaja(val,row,idx) { return ( parseInt(val)==0)?" ":"&#x26D4;"; }

/**
 * Open "New Juez dialog"
 *@param {string} dg datagrid ID de donde se obtiene el juez
 *@param {string} def default value to insert into Nombre 
 *@param {function} onAccept what to do when a new Juez is created
 */
function newJuez(dg,def,onAccept){
	$('#jueces-dialog').dialog('open').dialog('setTitle','<?php _e('New judge'); ?>'); // open dialog
	$('#jueces-form').form('clear');// clear old data (if any)
	if (strpos(def,"<?php _e('-- Search --'); ?>")===false) $('#jueces-Nombre').textbox('setValue',def.capitalize());// fill juez Name
	$('#jueces-Operation').val('insert');// set up operation
	if (onAccept!==undefined) $('#jueces-okBtn').one('click',onAccept);
}

/**
 * Open "Edit Juez" dialog
 * @param {string} dg datagrid ID de donde se obtiene el juez
 * @param {object} row datagrid on dblClickRow. may be undefined
 */
function editJuez(dg,row){
	if ($('#jueces-datagrid-search').is(":focus")) return; // on enter key in search input ignore
    if (typeof(row)==="undefined") {
        row = $(dg).datagrid('getSelected');
        if (!row) {
            $.messager.alert('<?php _e("Edit Error"); ?>','<?php _e("There is no judge selected"); ?>',"warning");
            return; // no way to know which dog is selected
        }
    }
    // set up operation properly
    row.Operation='update';
    // open dialog
    $('#jueces-dialog').dialog('open').dialog('setTitle','<?php _e('Modify judge data'); ?>');
    // and fill form with row data
    $('#jueces-form').form('load',row);
    // set up federation checkboxes
    $('#jueces-RSCE').prop('checked',( (row.Federations & 1) != 0));
    $('#jueces-RFEC').prop('checked',( (row.Federations & 2) != 0));
    $('#jueces-Nat4').prop('checked',( (row.Federations & 4) != 0));
    $('#jueces-Nat3').prop('checked',( (row.Federations & 8) != 0));
    $('#jueces-CPC').prop('checked',( (row.Federations & 16) != 0));
    $('#jueces-Nat5').prop('checked',( (row.Federations & 32) != 0));
}

/**
 * Call json to Ask for commit new/edit juez to server
 */
function saveJuez(){
	// take care on bool-to-int translation from checkboxes to database
    var ji=$('#jueces-Internacional'); ji.val( ji.is(':checked')?'1':'0');
    var jp=$('#jueces-Practicas'); jp.val( jp.is(':checked')?'1':'0');
    var frm = $('#jueces-form');
    if (!frm.form('validate')) return; // don't call inside ajax to avoid override beforeSend()
    // evaluate federation checkboxes
    var fed=0;
    if ( $('#jueces-RSCE').is(':checked') ) fed |=1;
    if ( $('#jueces-RFEC').is(':checked') ) fed |=2;
    if ( $('#jueces-Nat4').is(':checked') ) fed |=4;
    if ( $('#jueces-Nat3').is(':checked') ) fed |=8;
    if ( $('#jueces-CPC').is(':checked') ) fed |=16;
    if ( $('#jueces-Nat5').is(':checked') ) fed |=32;
    $('#jueces-Federations').val(fed);

    // disable ok button during ajax transaction to avoid register twice
    $('#jueces-okBtn').linkbutton('disable');
    $.ajax({
        type: 'GET',
        url: '../ajax/database/juezFunctions.php',
        data: frm.serialize(),
        dataType: 'json',
        success: function (result) {
            if (result.errorMsg){
                $.messager.show({ width:300, height:200, title: '<?php _e('Error'); ?>', msg: result.errorMsg });
            } else {
                $('#jueces-dialog').dialog('close');        // close the dialog
                $('#jueces-datagrid').datagrid('reload');    // reload the juez data
            }
        },
        error: function(XMLHttpRequest,textStatus,errorThrown) {
            $.messager.alert("Save Juez","Error:"+XMLHttpRequest.status+" - "+XMLHttpRequest.responseText+" - "+textStatus+" - "+errorThrown,'error' );
        }
    }).done(function(){
        $('#jueces-okBtn').linkbutton('enable');
    });
}

/**
 * Delete juez data in bbdd
 * @param {string} dg datagrid ID de donde se obtiene el juez
 */
function deleteJuez(dg){
    var row = $(dg).datagrid('getSelected');
    if (!row) {
        $.messager.alert('<?php _e("Delete error"); ?>','<?php _e("There is no judge selected"); ?>',"info");
    	return; // no way to know which juez is selected
    }
    if (row.ID==1) {
    	$.messager.alert('<?php _e("Delete error"); ?>','<?php _e("This entry cannot be deleted"); ?>',"error");
    	return; // cannot delete default juez
    }
    $.messager.confirm('<?php _e('Confirm'); ?>','<?php _e('Delete data on judge'); ?>'+':'+row.Nombre+'\n '+'<?php _e('Sure?'); ?>',function(r){
      	if (!r) return;
        $.get('../ajax/database/juezFunctions.php',{ Operation: 'delete', ID: row.ID },function(result){
            if (result.success){
                $(dg).datagrid('clearSelections');
                $(dg).datagrid('reload');    // reload the juez data
            } else {
            	// show error message
                $.messager.show({width:300,height:200,title: 'Error',msg: result.errorMsg});
            }
        },'json');
    });
}
