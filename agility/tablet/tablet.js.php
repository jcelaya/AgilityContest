<?php header('Content-Type: text/javascript'); ?>
/*
tablet.js

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
require_once(__DIR__ . "/../server/tools.php");
require_once(__DIR__ . "/../server/auth/Config.php");
$config =Config::getInstance();
?>

function tandasStyler(val,row,idx) {
	var str="text-align:left; ";
	str += "font-weight:bold; ";
	str += ((idx&0x01)==0)?'background-color:#ccc;':'background-color:#eee;';
	return str;
}

/******************* funciones de manejo del panel de orden de tandas y orden de salida en el tablet *******************/

/**
 * expande/contrae activa/desactiva entrada de datos en el tablet
 * @param {boolean} flag true if activate; false on deactivate
 */
function setDataEntryEnabled(flag) {
	$('#tablet-layout').layout( (flag)?'collapse':'expand','west');
}

function setStartStopMode(mode) {
	var ssb=$('#tdialog-StartStopBtn');
	ac_clientOpts.StartStopMode=mode;
	if (mode<0) ssb.val("Auto"); // mark running in auto mode
	if (mode==0) ssb.val("Start"); // mark stopped (ready)
	if (mode>0) ssb.val("Stop"); // mark running in manual mode
}

function getStartStopMode() {
	return ac_clientOpts.StartStopMode;
}

/******************* funciones de manejo del panel de entrada de resultados del tablet *****************/

/**
 * send events
 * @param {string} type Event Type
 * @param {object} data Event data
 */
function tablet_putEvent(type,data){
	// setup default elements for this event
	var obj= {
			'Operation':'putEvent',
			'Type': 	type,
			'TimeStamp': Math.floor(Date.now() / 1000),
			'Source':	ac_clientOpts.Source,
            'Destination': ac_clientOpts.Destination, /* not specified: use name or session */
            'Session':	ac_clientOpts.Ring,
            'Name':     ac_clientOpts.Name,
            'SessionName': ac_clientOpts.SessionName,
			'Prueba':	$('#tdialog-Prueba').val(),
			'Jornada':	$('#tdialog-Jornada').val(),
			'Manga':	$('#tdialog-Manga').val(),
			'Tanda':	$('#tdialog-ID').val(),
			'Perro':	$('#tdialog-Perro').val(),
			'Dorsal':	$('#tdialog-Dorsal').val(),
			'Equipo':	$('#tdialog-Equipo').val(),
			'Celo':		$('#tdialog-Celo').val(),
			'Value':	0 // may be overriden with 'data' contents
	};
	// send "update" event to every session listeners
    var dta=$.extend({},obj,data);
    // console.log("putEvent: "+JSON.stringify(dta));
	$.ajax({
		type:'GET',
		url:"../ajax/database/eventFunctions.php",
		dataType:'json',
        timeout: 5000, // response should arrive in this time. more delay usually means connection problem
		data: dta,
        // on system errors ( connection lost, timeouts, or so ) display an alarm
        error: function(XMLHttpRequest,textStatus,errorThrown) {
		    if (errorThrown.indexOf("imeout")>=0) {
		        $.messager.show({
                    title: "Timeout",
                    msg: '<?php _e("No server response");?><br/><?php _e("Please, check connection");?>',
                    timeout: 1500,
                    showType: 'slide',
                    style:{ right:'', bottom:'' }
                });
            } else {
                $.messager.show({
                    title:'putEvent',
                    msg:'tablet::putEvent( '+type+' ) error: '+XMLHttpRequest.status+" - "+XMLHttpRequest.responseText+" - "+textStatus + ' '+ errorThrown,
                    timeout:500,
                    showType:'slide'
                });
            }
        }
	});
}

function handleCommandEvent(event) {
    var sessid=parseInt(event['Session']);
    var source=event['Source'];
    var destination=event['Destination'];
    var name=event['Name'];
    var oper=parseInt(event['Oper']);
    var timeout=parseInt(event['Value'].split(':')[0]);
    var msg=event['Value'].split(':')[1];
    var isForMe=false;
    if (oper!==EVTCMD_MESSAGE) return; /* only handles tablet messaging */
    if (sessid==0) isForMe=true; /* broadcast */
    if (name==="") {
        if (sessid == ac_clientOpts.Ring) isForMe=true;
        if (destination == ac_clientOpts.Source) isForMe=true;
    }
    if (name===ac_clientOpts.Name) isForMe=true;
    if (!isForMe) return;
    $.messager.show({
        title:'Message from: '+source,
        msg: msg,
        timeout: 1000*timeout,
        showType:'slide'
    });
}

function tablet_updateSession(row) {
	// on user defined mangas, check for wourse walk
	if ( parseInt(row.Manga)==0) {
		if (row.Nombre.toLowerCase().indexOf("econo")>0) tablet_reconocimiento();
		return false;
	} else {
		// in non-user defined rounds, update testDog data
		workingData.testDog.Prueba=row.Prueba;
		workingData.testDog.Jornada=row.Jornada;
		workingData.testDog.Manga=row.Manga;
		workingData.testDog.Tanda=row.Nombre;
		workingData.testDog.Categoria=formatCategoria(row.Categoria);
		workingData.testDog.Grado=formatGrado(row.Grado);
	}
	// update sesion info in database
	var data = {
		Operation: 'update',
		ID: workingData.session,
		Prueba: row.Prueba,
		Jornada: row.Jornada,
		Manga: row.Manga,
		Tanda: row.ID
	};
	// setup infoheader on tablet
	$('#tdialog-InfoLbl').html(workingData.datosPrueba.Nombre + ' - ' + workingData.datosJornada.Nombre + ' - ' + row.Nombre);
	$.ajax({
		type:	'GET',
		url:	"../ajax/database/sessionFunctions.php",
		// dataType:'json',
		data:	data,
		success: function() {
			data.Session=	data.ID;
			data.Operation=	'putEvent';
			data.NombrePrueba= workingData.datosPrueba.Nombre;
			data.NombreJornada= workingData.datosJornada.Nombre;
			data.NombreManga= row.Nombre;
			data.NombreRing= workingData.datosSesion.Nombre;
			data.Perro=0;
			data.Dorsal=0;
			// send proper event
			tablet_putEvent( (parseInt(row.Manga)==0)?'info':'open',data);
		}
	});
}

function tablet_updateResultados(pendiente) {
	// on "Test dog", do not store into database, only allow event handling
	var p=$('#tdialog-Perro').val();
	if (p==0) return;
	// DO NOT STORE WHEN not present,eliminated and time is zero
	var n=$('#tdialog-NoPresentado').val();
	var e=$('#tdialog-Eliminado').val();
	var t=$('#tdialog-Tiempo').val();
	if ( (pendiente==0) && (n==0) && (e==0) && (t==0) ) {
		console.log("tablet_updateResultados() try to mark pending dog:"+p+" with no data entered");
		return;
	}
	// make sure that 'pendiente' is properly sent to server
	$('#tdialog-Pendiente').val(pendiente);
	var frm = $('#tdialog-form');
	$.ajax({
		type: 'GET',
		url: '../ajax/database/resultadosFunctions.php',
		data: frm.serialize(),
		dataType: 'json',
		success: function (result) {
			if (result.errorMsg){
				$.messager.show({ width:300, height:200, title: 'Error', msg: result.errorMsg });
			}
			// NOTE: do not update parent tablet row on success
			// as form('reset') seems not to work as we want, we use it as backup
		}
	});
}

function doBeep() {
	if (isMobileDevice() && ac_config.tablet_beep==="1") setTimeout(function() {beep();},0);
}

function dorsal_add(val) {
    var str_tb=$('#tb_drs');
    var str=str_tb.numberbox('getValue');
    if (parseInt(str)===0) str=''; // clear espurious zeroes
    if(str.length>=4) return false; // dorsals greater than 9999 are not allowed
    str_tb.numberbox('setValue',''+str+val);
    return false;
}

function tablet_add(val) {
    if ($('#tb_drs').numberbox('options').editing===true) return dorsal_add(val);
	doBeep();
	var maxlen=(ac_config.crono_milliseconds==="0")?6:7;
	var declen=(ac_config.crono_milliseconds==="0")?2:3;
	var tdt=$('#tdialog-Tiempo');
	tdt.data("manual", true);
	var str=tdt.val();
	if (parseInt(str)===0) str=''; // clear espurious zeroes
	if(str.length>=maxlen) return false; // sss.xx 6/7 chars according configuration
	var n=str.indexOf('.');
	if (n>=0) {
		var len=str.substring(n).length;
		if (len>declen) return false; // only allowed decimal digits from config
	}
	tdt.val(''+str+val);
	tablet_updateResultados(1);
	// dont send event
	return false;
}

function tablet_dot() {
	doBeep();
    if ($('#tb_drs').numberbox('options').editing===true) return false; // ignore dot in dorsal mode
	var str=$('#tdialog-Tiempo').val();
	if (str.indexOf('.')>=0) return;
	tablet_add('.');
	tablet_updateResultados(1);
	// dont send  event
	return false;
}

function dorsal_del() {
    var str_tb=$('#tb_drs');
    var str=str_tb.numberbox('getValue');
    if (parseInt(str)===0) str=''; // clear espurious zeroes
    if(str.length===0) return false; // no chars to delete
    str_tb.numberbox('setValue',str.substring(0, str.length-1));
    return false;
}

function tablet_del() {
    if ($('#tb_drs').numberbox('options').editing===true) return dorsal_del();
	doBeep();
	var tdt=$('#tdialog-Tiempo');
	tdt.data("manual", true);
	var str=tdt.val();
	if (str==='') return false;
	tdt.val(str.substring(0, str.length-1));
	tablet_updateResultados(1);
	// dont send event
	return false;
}

function tablet_up(id,sendEvent){
	doBeep();
	var n= 1+parseInt($(id).val());
	var lbl = replaceAll('#tdialog-','',id);
	var datos = {};
	$(id).val(''+n);
	tablet_updateResultados(1);
	datos[lbl]=$(id).val();
	if (sendEvent){
		tablet_putEvent( 'datos', datos);
	}
	return false;
}

function tablet_down(id,sendEvent){
	doBeep();
	var n= parseInt($(id).val());
	var m = (n<=0) ? 0 : n-1;
	var lbl = replaceAll('#tdialog-','',id);
	var datos = {};
	$(id).val(''+m);
	tablet_updateResultados(1);
	datos[lbl]=$(id).val();
	if (sendEvent){
		tablet_putEvent( 'datos', datos );
	}
	return false;
}

function tablet_np(sendEvent) {
	doBeep();
	var tde=$('#tdialog-Eliminado');
	var tdestr=$('#tdialog-EliminadoStr');
	var tdnp=$('#tdialog-NoPresentado');
	var tdnpstr=$('#tdialog-NoPresentadoStr');
	var tdtime=$('#tdialog-Tiempo');
	var tdtint=$('#tdialog-TIntermedio');
	var tdflt=$('#tdialog-Faltas');
	var tdtoc=$('#tdialog-Rehuses');
	var tdreh=$('#tdialog-Tocados');
	var n= parseInt(tdnp.val());
	if (n==0) {
		tdnp.val(1);
		tdnpstr.val("NP");
		// si no presentado borra todos los demas datos
		tde.val(0);
		tdestr.val("");
		tdflt.val(0);
		tdreh.val(0);
		tdtoc.val(0);
		tdtime.val("0.00");
		tdtint.val(0);
	} else {
		tdnp.val(0);
		tdnpstr.val("");
	}
	tablet_updateResultados(1);
	if (sendEvent){
		tablet_putEvent(
			'datos',
			{
				'NoPresentado'	:	(n==0)?1:0,
				'Faltas'		:	0,
				'Tocados'		:	0,
				'Rehuses'		:	0,
				'Tiempo'		:	0,
				'TIntermedio'	:	0,
				'Eliminado'		:	0
			}
		);
	}
	return false;
}

function tablet_elim(sendEvent) {
	doBeep();
	var tde=$('#tdialog-Eliminado');
	var tdestr=$('#tdialog-EliminadoStr');
	var tdnp=$('#tdialog-NoPresentado');
	var tdtime=$('#tdialog-Tiempo');
	var tdtint=$('#tdialog-Tintermedio');
	var n= parseInt(tde.val());
	if (n==0) {
		tde.val(1);
		tdestr.val("EL");
		// si eliminado, poner nopresentado a cero, conservar lo demas
		tdnp.val(0);
		$('#tdialog-NoPresentadoStr').val("");
		tdtime.val("0.00");
	} else {
		tde.val(0);
		tdestr.val("");
	}
	tablet_updateResultados(1);
	if (sendEvent) {
		tablet_putEvent(
			'datos',
			{
				'NoPresentado'	:	0,
				'Tiempo'		:	tdtime.val(),
				'TIntermedio'	:	tdtint.val(),
				'Eliminado'		:	(n==0)?1:0
			}
		);
	}
	return false;
}

/**
 * Parse data from electronic chronometer
 * @param data data to update
 * @param send on true send event to server
 */
function tablet_updateChronoData(data,send) {
    var str="";
	var f=parseInt(data['Faltas']);
	var r=parseInt(data['Rehuses']);
	var t=parseInt(data['Tocados']);
	var e=parseInt(data['Eliminado']);
	var n=parseInt(data['NoPresentado']);
	if (f>=0) $('#tdialog-Faltas').val(''+f);
	if (t>=0) $('#tdialog-Tocados').val(''+t);
	if (r>=0) $('#tdialog-Rehuses').val(''+r);
	// if (data["Tiempo"]!=-1) $('#chrono_Tiempo').html(data["Tiempo"]);
	if(e>=0) {
		str=(data['Eliminado']==0)?"":"EL";
		$('#tdialog-Eliminado').val(e);
		$('#tdialog-EliminadoStr').val(str);
		$('#tdialog-NoPresentado').val(0);
		$('#tdialog-NoPresentadoStr').val("");
	}
	if (n>=0) {
		str=(data['NoPresentado']==0)?"":"NP";
		$('#tdialog-NoPresentado').val(n);
		$('#tdialog-NoPresentadoStr').val(str);
		$('#tdialog-Eliminado').val(0);
		$('#tdialog-EliminadoStr').val("");
		$('#tdialog-Tiempo').val("0.00");
		$('#tdialog-Tintermedio').val(0);
	}
	// call server to update results
	if (send) tablet_updateResultados(1);
	// DO NOT RESEND EVENT!!!
}

function tablet_cronometro(oper,time) {
	if (ac_config.tablet_chrono==="1") $('#cronometro').Chrono(oper,time);
	// on reset clear all data. on screen, but do not send values to server
	if (oper==='reset') {
        $('#tdialog-Faltas').val("0");
        $('#tdialog-Tocados').val("0");
        $('#tdialog-Rehuses').val("0");
        $('#tdialog-NoPresentado').val(0);
        $('#tdialog-NoPresentadoStr').val("");
        $('#tdialog-Eliminado').val(0);
        $('#tdialog-EliminadoStr').val("");
        $('#tdialog-Chrono').val("0.00");
        $('#tdialog-Tiempo').val("0.00");
		$('#tdialog-Tiempo').data("manual", false);
        $('#tdialog-Tintermedio').val(0);
    }
}

var myCounter = new Countdown({  
	seconds:15,  // number of seconds to count down
	onUpdateStatus: function(tsec){
		$('#tdialog-Chrono').val(toFixedT((tsec/10),1));
	}, // callback for each tenth of second
	// onCounterEnd: function(){  $('#tdialog_Chrono').html('<span class="blink" style="color:red">-out-</span>'); } // final action
	onCounterEnd: function(){  // at end of countdown start timer
		var time = Date.now() - startDate;
		switch (parseInt(ac_config.tablet_countdown)) {
			case 1: /* do nothing */ return;
			case 2: /* start crono */
				tablet_putEvent('start',{ 'Value' : time } );
				setStartStopMode(1);
				break;
			case 3: /* eliminado */
				$('#tdialog-Eliminado').val(0); //make sure that tablet sees not eliminado
				tablet_elim(); // call eliminado handler
				return;
		}
	}
});

function tablet_reconocimiento() {
	var time= (ac_clientOpts.CourseWalk==0)?60 * parseInt(ac_config.crono_rectime):0;
	ac_clientOpts.CourseWalk=time;
	tablet_putEvent('crono_rec',{
		'Session': workingData.session,
		'Value' : Date.now() - startDate,
		'start' : time
	} );
	doBeep();
	return false;
}

function tablet_perroEnBlanco() {
	// verifica que hay manga seleccionada
    var dg=$('#tablet-datagrid');
	var row=dg.datagrid('getSelected');
	if (!row) {
		$.messager.alert("Error",'<?php _e("No round selected");?>',"error");
		return false;
	}
	if (parseInt(row.Tipo)==0) {
		$.messager.alert("Error",'<?php _e("You must select a running round");?>',"error");
		return false;
	}
    // generamos un evento "llamada" con IDPerro=0
    doBeep();
    workingData.testDog.Session=workingData.session;
    workingData.testDog.Parent='#tablet-datagrid-'+row.ID;
    var dg2=$(workingData.testDog.Parent);
    var row2=dg2.datagrid('getSelected');
    workingData.testDog.RowIndex=(row2)?dg2.datagrid('getRowIndex',row2):-1;
    $('#tdialog-form').form('load',workingData.testDog);
    setDataEntryEnabled(true);
}

function tablet_startstop() {
	var time = Date.now() - startDate;
	var ssb=getStartStopMode();
	if (ssb<0) return; // crono auto started: ignore
	if (ssb==0) tablet_putEvent('start',{ 'Value' : time } );
	if (ssb>0) tablet_putEvent('stop',{ 'Value' : time } );
	doBeep();
	return false;
}

function tablet_userfn(val) {
    tablet_putEvent('user',{ 'Value' : val });
    doBeep();
    return false;
}

function tablet_salida() { // 15 seconds countdown
	var time = Date.now() - startDate;
	var ssb=getStartStopMode();
	if (ssb<0) return; // crono auto started. ignore
	if (ssb>0) return; // crono manual started. ignore
	tablet_putEvent('salida',{ 'Value' : time } );
	doBeep();
	return false;
}

function tablet_resetchrono() {
	var time = Date.now() - startDate;
	tablet_putEvent('crono_reset',{ 'Value' : time } );
	doBeep();
	return false;
}

function dorsal_edit() {
    doBeep();
    let tb_drs= $('#tb_drs');
    tb_drs.numberbox('textbox').css('backgroundColor','#ffcccc')
    tb_drs.numberbox('options').editing=true;
    tb_drs.numberbox('setValue','');
}
function dorsal_cancel() {
    let tb_drs=$('#tb_drs');
    tb_drs.numberbox('options').editing=false;
    tb_drs.numberbox('textbox').css('backgroundColor','#ffffff')
    tb_drs.numberbox('setValue','<?php _e("Dorsal");?>');
    return false;
}

function tablet_cancel() {
	doBeep();
	if ($('#tb_drs').numberbox('options').editing===true) return dorsal_cancel();
	// retrieve original data from parent datagrid
	var dgname=$('#tdialog-Parent').val();
	var dg=$(dgname);
	var row =dg.datagrid('getSelected');
	// on Test dog no need to pre-select dog entry. so take care on it
	if (!row || ( $('#tdialog-Perro').val()==="0" ) ) {
		tablet_putEvent(
			'cancelar',
			{
				'NoPresentado'	:	0,
				'Faltas'		:	0,
				'Tocados'		:	0,
				'Rehuses'		:	0,
				'Tiempo'		:	0,
				'TIntermedio'	:	0,
				'Eliminado'		:	0
			}
		);
		// no dog selection, so no result to store nor nextdog to select
		setDataEntryEnabled(false);
		return false;
	}
	var idx=dg.datagrid('getRowIndex',row);
	// update database according row data
	row.Operation='update';
	$.ajax({
		type:'GET',
		url:"../ajax/database/resultadosFunctions.php",
		dataType:'json',
		data: row,
		success: function () {
			// and fire up cancel event
			tablet_putEvent(
				'cancelar',
				{
					'NoPresentado'	:	row.NoPresentado,
					'Faltas'		:	row.Faltas,
					'Tocados'		:	row.Tocados,
					'Rehuses'		:	row.Rehuses,
					'Tiempo'		:	row.Tiempo,
					'TIntermedio'	:	row.TIntermedio,
					'Eliminado'		:	row.Eliminado
				}
			);
			dg.datagrid('scrollTo',{
				index : idx,
				callback: function(index) {
					setDataEntryEnabled(false);
					dg.datagrid('refreshRow',idx);
				}
			});
		}
	});
}

/* dg round selection datagrid, idx row index */
function tablet_markSelectedDog(idx) {
	var dg2=$('#tdialog-tnext');
	dg2.datagrid('scrollTo',idx);
	dg2.datagrid('selectRow',idx);
	$('#tdialog-NumberLbl').html('<p>'+(idx+1)+'</p>');
}

function nextRow(dg,row,index, cb){
	var opts = dg.datagrid('options');
	index++;
	if (index>=(opts.numRows)) return false; // at the end
	dg.datagrid('scrollTo', {
		index: index, // to allow view up to 4 next rows
		callback: function(idx){
			dg.datagrid('selectRow', idx);
			cb(idx, dg.datagrid('getRows')[idx]);
		}
	});
	return true;
}

/**
 * 
 * @param {object} dg selected round list datagrid
 * @return current row index
 */
function tablet_save(dg) {
	tablet_updateResultados(0); // mark as result no longer pending

	var row = dg.datagrid('getSelected');
	var rowindex=(row)?dg.datagrid("getRowIndex", row):-1;
	// on white dog do not propagate results to datagrid. just send fake event
	if ( parseInt($('#tdialog-Perro').val())===0) {
		tablet_putEvent(
			'aceptar',
			{
				'NoPresentado': 0,
				'Faltas': 0,
				'Tocados': 0,
				'Rehuses': 0,
				'Tiempo': 0,
				'Eliminado': 0
			}
		);
		return rowindex;
	}
	if (rowindex<0) {
		console.log("INTERNAL ERROR tablet_save(): no selected row");
		return rowindex;
	}

	// send back data to parent tablet datagrid form. mark no pending
	var obj = formToObject('#tdialog-form');
	obj.Pendiente = 0;
	dg.datagrid('updateRow', {index: rowindex, row: obj});

	// and fire up accept event
	tablet_putEvent(
		'aceptar',
		{
			// notice pass-by-reference: row now points to new values
			'NoPresentado': row.NoPresentado,
			'Faltas': row.Faltas,
			'Tocados': row.Tocados,
			'Rehuses': row.Rehuses,
			'Tiempo': row.Tiempo,
			'Eliminado': row.Eliminado
		}
	);
	return rowindex;
}

function dorsal_accept(dorsal) {
    let tb_drs= $('#tb_drs');

    // miramos si hay algun dorsal pendiente en cola
    // si lo hay se actualiza el numberbox de proximo dorsal
    // si no lo hay se deja el nuevo perro
    let next =(dorsalList.isEmpty())?dorsal:dorsalList.dequeue()[0];
    tb_drs.numberbox('setValue',next);

    // Salvamos datos
    // check for store before change dog. dorsal textbox has same behaviour than doubleclick
    if (parseInt(ac_config.tablet_dblclick)===1){
        // retrieve parent datagrid to update results
        var dgname = $('#tdialog-Parent').val();
        var dg = $(dgname);
        tablet_save(dg);
    }

    // damos entrada al perro con dorsal especificado
    $('#tablet-datagrid-search').val(parseInt(dorsal));
    tablet_editByDorsal();
    return false;
}

function tablet_accept() {
	doBeep();
	// reset editing condition
    var tb_drs=$('#tb_drs');
    tb_drs.numberbox('options').editing=false;
    tb_drs.numberbox('textbox').css('backgroundColor','#ffffff');

	// retrieve parent datagrid to update results
	var dgname = $('#tdialog-Parent').val();
	// on initial call no value, so just return
    if (dgname==="") return false;
	var dg = $(dgname);

	// save current data and send "accept" event
	var rowindex=tablet_save(dg);
	if (rowindex<0) return false;

	// check "accept" behaviour in config. If 'tablet_next' = false, just return to round selection
	if (ac_config.tablet_next==="0") { // no go to next row entry
		setDataEntryEnabled(false);
		dg.datagrid('refreshRow',rowindex);
		return false;
	}

	// vemos cual es el dorsal actual y el siguiente
    var current=$('#tdialog-Dorsal').val();
    var next=tb_drs.numberbox('getValue');
	// si tb_drs es es distinto del perro actual y no esta vacio salta al perro indicado
    // notar que dorsal_accept debe actualizar tambien la cola de pendientes de entrada
    if ( (current!==next) && (next!=="") ) {
        console.log("Jump to dorsal "+next);
        return dorsal_accept(next);
    }

	// arriving here go to next row (if available)
	rowindex++; // 0..len-1
	if ( rowindex >= dg.datagrid('getRows').length) {
		// at end. Close panel and return
		var time = Date.now() - startDate;
		setDataEntryEnabled(false);
		dg.datagrid('refreshRow',rowindex-1);
		dg.datagrid('clearSelections');
		dg = $('#tablet-datagrid');
		var oldRow = dg.datagrid('options').expandedRow;
		dg.datagrid('collapseRow', oldRow).datagrid('expandRow', oldRow + 1);
		tablet_putEvent('close',{ 'Value' : time } );
	} else {
		// not at end scrollTo, markSelected and update dataentry panel
		dg.datagrid('scrollTo',rowindex);
		dg.datagrid('selectRow',rowindex);
		var data=dg.datagrid('getSelected');
		data.Session=workingData.session;
		data.RowIndex=rowindex;
		data.Parent=dgname;
		data.Categoria=formatCategoria(data.Categoria)
		data.Grado=formatGrado(data.Grado)
		$('#tdialog-form').form('load',data);
		tb_drs.numberbox('setValue',''+data.Dorsal);
		tablet_markSelectedDog(parseInt(data.RowIndex));
	}
	return false; // prevent follow onClick event chain
}

/**
 * retrieve from server data row on provided dorsal
 * call to callback(idx,row) provided function
 * @param {object} tanda current selected tanda
 * @param {object} dgname datagrid nams for current selected tanda
 * @param {int} dorsal Dog dorsal to search for
 */
function tablet_gotoDorsal(tanda,dgname,dorsal) {
    doBeep();
	$.ajax({
		type:	'GET',
		url:	"../ajax/database/tandasFunctions.php",
		dataType:'json',
		data: {
			Operation: 'getDataByDorsal',
			Prueba:		tanda.Prueba,
			Jornada:	tanda.Jornada,
			Sesion:		tanda.Sesion,
			ID:			tanda.ID,
			Dorsal:		dorsal
		},
		success: function(row) {
			var idx=row.RowIndex;
			if (idx<0) {
				$.messager.alert("Not found",'<?php _e("Dog with dorsal");?>'+": "+dorsal+" "+'<?php _e("does not run in this series");?>',"info");
				$('#tablet-datagrid-search').val('---- <?php _e("Dorsal"); ?> ----');
				$('#tb_drs').numberbox('setValue',$('#tdialog-Dorsal').val()); // restore dorsal value in main panel
				return false;
			}
			dg=$(dgname);
			dg.datagrid('selectRow', idx);
			dg.datagrid('scrollTo', idx);
			var data = dg.datagrid('getRows')[idx];
			data.Session = workingData.session;
			data.Parent = dgname; // store datagrid reference
			data.RowIndex=idx;
			data.Categoria=formatCategoria(data.Categoria)
			data.Grado=formatGrado(data.Grado)
			$('#tdialog-form').form('load', data);
			tablet_markSelectedDog(parseInt(idx));
			setDataEntryEnabled(true);
		},
		error: function(XMLHttpRequest,textStatus,errorThrown) {
			alert("tablet_gotoDorsal() error: "+XMLHttpRequest.status+" - "+XMLHttpRequest.responseText+" - "+textStatus + " "+ errorThrown );
		}
	});
}

/**
 called on 'llamada' event when it comes from external "device"  (ie: qrcode reader at course start )
 @param {int} drs (parseInt'd before call)
  */
function tablet_nextDorsal(drs) {
    // verificamos que el perro está en esta manga
    var rows=$('#tdialog-tnext').datagrid('getRows');
    for (n=0;n<rows.length;n++) if (parseInt(rows[n]['Dorsal'])===drs) break;
    if (n===rows.length) { // dorsal is not in current tanda
        console.log("Dorsal "+drs+" does not belongs to this round");
        return;
    }

    // if dorsalList is not empty, just insert at queue tail and return
    if ( ! dorsalList.isEmpty() ) {
        console.log("Dorsal queue not empty: Enqueing dorsal "+drs);
        dorsalList.enqueue(drs);
        return;
    }
    // si la lista de dorsales esta vacia pero la ventana de edición de dorsal está activa
    // quiere decir que el operador esta metiendo a mano un dorsal. En ese caso encolamos
    var tb_drs=$('#tb_drs');
    if (tb_drs.numberbox('options').editing) {
        console.log("User editing dorsal textbox: Enqueing dorsal "+drs);
        dorsalList.enqueue(drs);
        return;
    }
    // si no esta en edicion, vemos el contenido
    var next=tb_drs.numberbox('getValue');
    // si no tiene numero, quiere decir que el perro debe entrar a pista directamente
    if (isNaN(parseInt(next))) {
        console.log("No data in dorsal textbox: call to accept dorsal "+drs);
        tb_drs.numberbox('setValue',''+drs);
        dorsal_accept(drs);
        return;
    }
    var current=$('#tdialog-Dorsal').val();
    if (next!==current) {
        console.log("current data "+current+" in dorsal textbox is not active: enqueue new dorsal "+drs);
        // si el numero que tiene NO es el perro que esta corriendo, encolamos y ya esta
        dorsalList.enqueue(drs);
    } else {
        // si el numero que tiene SI es el perro que esta corriendo, quiere decir que
        // o esta en pista o que todavía no han dado paso al siguiente. En ese caso
        console.log("Mark next dorsal to be "+drs);
        // metemos como siguiente perro ( y esperamos a que el usuario pulse enter )
        tb_drs.numberbox('setValue',''+drs);
    }
}

function tablet_editByDorsal() {
	var i,len;
	var dg=$('#tablet-datagrid');
	var drs=$('#tablet-datagrid-search');
	var rows=dg.datagrid('getRows');
	var dorsal=parseInt(drs.val());
    doBeep();
	drs.blur();// remove focus to hide tooltip
	// si no hay tandas activas muestra error e ignora
	for (i=0,len=rows.length;i<len;i++) {
		if (typeof(rows[i].expanded)==="undefined") continue;
		if (parseInt(rows[i].expanded)===0) continue;
		// obtenemos el datagrid y buscamos el dorsal
		var dgname='#tablet-datagrid-'+rows[i].ID;
		tablet_gotoDorsal(rows[i],dgname,dorsal);
		drs.val('---- <?php _e("Dorsal"); ?> ----');
		return false;
	}
	// arriving here means that there are no expanded row
	$.messager.alert("No selection",'<?php _e("There is no selected round");?>',"error");
	drs.val('---- <?php _e("Dorsal"); ?> ----');
}

function bindKeysToTablet() {

	// disable key handling on tablet/mobile phone
	if (isMobileDevice()) return;
	// if configuration states keyboard disabled, ignore
	if (parseInt(ac_config.tablet_keyboard)===0) return false;

	// parse keypress event on every  button
	$(document).keydown(function(e) {
		// on round selection window focused, ignore
		if ($('#tdialog-fieldset').prop('disabled')) return true;
		if ( ! $('#tb_drs').numberbox('options').editing) doBeep();
		switch(e.which) {
			/* you can check keycodes at http://www.cambiaresearch.com/articles/15/javascript-char-codes-key-codes */
			// numbers (querty/keypad)
			case 48:    /* 0 */
			case 96:	/* numpad 0 */ tablet_add(0); break;
			case 49:    /* 1 */
			case 97:	/* numpad 1 */	tablet_add(1); break;
			case 50:    /* 2 */
			case 98:	/* numpad 2 */	tablet_add(2); break;
			case 51:    /* 3 */
			case 99:	/* numpad 3 */	tablet_add(3); break;
			case 52:    /* 4 */
			case 100:	/* numpad 4 */	tablet_add(4); break;
			case 53:    /* 5 */
			case 101:	/* numpad 5 */	tablet_add(5); break;
			case 54:    /* 6 */
			case 102:	/* numpad 6 */	tablet_add(6); break;
			case 55:    /* 7 */
			case 103:	/* numpad 7 */	tablet_add(7); break;
			case 56:    /* 8 */
			case 104:	/* numpad 8 */	tablet_add(8); break;
			case 57:    /* 9 */
			case 105:	/* numpad 9 */	tablet_add(9); break;
			case 8:		/* del */
			case 46:	/* numpad supr */	tablet_del(); break;
            case 188:   /* comma */
            case 190:   /* dot */
			case 110:	/* numpad dot */	tablet_dot(); break;
			// teclas de funcion para user defined events
            case 112:   /* F1 */        tablet_userfn(0);break;
            case 113:   /* F2 */        tablet_userfn(1);break;
            case 114:   /* F3 */        tablet_userfn(2);break;
            case 115:   /* F4 */        tablet_userfn(3);break;
			// entrada de datos desde tablet
			case 70: // 'F' -> falta
			case 32: // ' ' -> space also works as fault
				if (e.ctrlKey || e.metaKey) tablet_down('#tdialog-Faltas',true);
				else 	tablet_up('#tdialog-Faltas',true);
				break;
			case 82: // 'R' -> rehuse
			case 225: // 'AltGr' -> also works as refusal
				if (e.ctrlKey || e.metaKey) tablet_down('#tdialog-Rehuses',true);
				else 	tablet_up('#tdialog-Rehuses',true);
				break;
			case 84: // 'T' -> tocado
			// case 18: // 'Alt' -> also works as "touch"
				if (e.ctrlKey || e.metaKey) tablet_down('#tdialog-Tocados',true);
				else 	tablet_up('#tdialog-Tocados',true);
				break;
			case 69:	tablet_elim(true); break; // 'E' -> eliminado
			case 78:	tablet_np(true); break; // 'N' -> no presentado
			// arranque parada del crono
			case 67:	tablet_resetchrono(); break; // 'C' -> (Cero/Clear) chrono reset
			case 83:	tablet_startstop();	break; // 'S' -> chrono start/Stop
			case 71:	tablet_salida();	break; // 'G' - (go) > 15 seconds countdown
			// aceptar cancelar
			case 13:	tablet_accept(); break; // 'Enter' -> Accept
            // use click event to make sure focus is properly set
			case 27:	$('#tdialog-CancelBtn').click(); break; // 'ESC' -> Cancel
                // tablet_cancel(); break; // 'Esc' -> Cancel
			default:
				// alert("Unknow key code: "+ e.which);
				// pass to upper layer to caught and process
				return true;
		}
		return false;
	});
}

/**
 * El tablet no es "consumidor" de eventos ( salvo los de cronómetro )
 * Más bien al contrario: es "generador" de eventos. Las operaciones relacionadas con dichos
 * eventos se generan "antes" de enviar los eventos al sistema
 * Por ello, casi todos los eventos recibidos se ignoran, salvo los de cronómetro que únicamente
 * se usan para gestionar el crono local
 * @param id event ID
 * @param evt event Data
 */
function tablet_eventManager(id,evt) {
	var tbox=$('#tdialog-Chrono');
	var crm=$('#cronometro');
	var event=parseEvent(evt); // remember that event was coded in DB as an string
	event['ID']=id; // fix real id on stored eventData
	var time=event['Value']; // miliseconds 
	switch (event['Type']) {
	case 'null': // null event: no action taken
		return;
	case 'init': // operator starts tablet application
		return;
	case 'open': // operator select tanda: just fire backup if enabled
        autoBackupDatabase(1,"");
		return;
    case 'close': // no more dogs in tanda: fire backup if enabled
        autoBackupDatabase(1,"");
		return;
	case 'datos': // actualizar datos (si algun valor es -1 o nulo se debe ignorar)
		return;
	case 'llamada':	// llamada a pista
        // solo aceptamos el evento si no lo hemos generado nosotros (p.e. Si viene de un lector de dorsales
        // adicionalmente hay que tener en cuenta la interrelación con el dobleclick y el botón aceptar
        // del tablet, y el hecho de que normalmente se da la entrada a pista antes de que
        // el anterior participante haya terminado el recorrido ( aunque realmente hay que considerar los dos casos )
        if (event['Source']==='tablet') return;

        // tenemos dos casos:
        // - Normalmente lo que haremos será modificar el botón "aceptar" para que en lugar de buscar
        // el siguiente perro mire si se ha seleccionado otro a través de este evento.
        // - Adicionalmente habrá que contemplar el caso de que el asistente haya dado a "aceptar" _antes_ que el
        // control de entrada a pista, con lo que habría que simular un nuevo "aceptar"
        //
        tablet_nextDorsal(parseInt(event['Drs']));
		return;
	case 'salida': // orden de salida (15 segundos)
		tablet_cronometro('stop');
		tablet_cronometro('reset');
		setStartStopMode(0); // mark ready to start
		myCounter.start();
		return;
	case 'start': // arranque manual del cronometro
		myCounter.stop();
		switch(getStartStopMode()) {
			case -1: // crono auto: ignore
				break;
			case 1: // crono manual arrancado: vuelve a contar
				crm.Chrono('stop',time);
				crm.Chrono('reset');
				// no break;
			case 0: // crono parado:arranca
				setStartStopMode(1); // mark running in manual mode
				crm.Chrono('start',time);
				break;
		}
		return;
	case 'stop': // parada manual del cronometro
		setStartStopMode(0); // mark stopped
		tablet_cronometro('stop',time);
		return;// Value contiene la marca de tiempo
	case 'crono_start': // arranque crono electronico
		myCounter.stop();
		switch( getStartStopMode() ) {
			case 1: // crono arrancado manual: resync with provided time
				if (parseInt(ac_config.crono_resync) !== 0) {
					// TODO: fix resync event to properly change from manual to auto mode
					// when resync mode is on, we should retain elapsed time and go to auto mode
					// send restart event. Use event queue to avoid blocking event parsing
					/*
					 setTimeout(
					 function() {
						var stopTime=(Date.now()-startDate)
					 	tablet_putEvent("crono_restart",{'stop':stopTime, 'start':time } );
					 	console.log("Stop Time:"+stopTime+" StartTime:"+time);
					 	},0);
					 }
					 */
					return;
				}
				// no break
			case -1: // crono arrancado automatico: restart
				crm.Chrono('stop',time);
				// no break
			case 0: // crono parado
				setStartStopMode(-1); // mark automatic crono start
				crm.Chrono('reset');
				crm.Chrono('start',time);
		}
		return;
	case 'crono_restart': // paso de tiempo manual a automatico
		crm.Chrono('resync',event['stop'],event['start']);
		return;
	case 'crono_int':	// tiempo intermedio crono electronico
		// para el crono
		crm.Chrono('pause',time);
		// guarda tiempo intermedio
		$('#tdialog-TIntermedio').val(crm.Chrono('getValue')/1000.0);
		tablet_updateResultados(1);
		// re-arranca crono en cinco segundos
		setTimeout(function(){crm.Chrono('resume');},5000);
		return;
    case 'crono_stop':	// parada crono electronico
		setStartStopMode(0); // mark chrono stopped
		crm.Chrono('stop',time);
		return;
	case 'crono_reset': // puesta a cero incondicional
		myCounter.stop();
		tablet_cronometro('stop',time);
		tablet_cronometro('reset');
		tbox.removeClass('blink');
		setStartStopMode(0); // mark chrono stopped
		return;
	case 'crono_dat':	// datos desde el crono electronico
		tablet_updateChronoData(event,false);
		return;
	case 'crono_rec':	// reconocimiento de pista desde crono electronico
		// ignored, just for get noticed at chrono display
		return;
	case 'crono_error': // sensor alignment failed
		// show error message. Use reset to clear
		if (parseInt(event['Value'])===1) tbox.addClass('blink');
		else tbox.removeClass('blink');
		return;
	case 'crono_ready':	// el crono esta activo y escuchando
		var value=(parseInt(event['Value'])===0)?"Not Listening":"Ready";
		setTimeout(function(){
			$.messager.show({
				title:'Chronometer',
				msg:'Chronometer state now is:'+value,
				timeout:5000,
				showType:'slide'
			});
		},0);
		return;
    case 'user': // user defined event
        return;
	case 'cancelar': // operador pulsa cancelar
        autoBackupDatabase(1,"");
		return;
    case 'aceptar':	// operador pulsa aceptar
        // increase dog count for autobackup. trigger on success
        if (ac_config.backup_dogs==="0") return; // no trigger
        ac_config.dogs_before_backup++;
        // on limit fire backup. this proccess will reset counter
        if (ac_config.dogs_before_backup>=ac_config.backup_dogs) autoBackupDatabase(1,"");
		return;
	case 'info':	// click on user defined tandas
		return;
	case 'camera': // video source for live stream has changed
		return;
    case 'command': // handle remote control commands
        handleCommandEvent(event);
        return;
	case 'reconfig':	// reload configuration from server
		loadConfiguration();
		return;
	default:
		alert("Unknow Event type: "+event['Type']);
		return;
	}
}