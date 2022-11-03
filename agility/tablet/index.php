<?php
/*
 tablet/index.php

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
// let the browser make https ajax calls from http
header("Access-Control-Allow-Origin: https://{$_SERVER['SERVER_NAME']}",false);
require_once(__DIR__."/../server/tools.php");
require_once(__DIR__."/../server/auth/Config.php");
require_once(__DIR__."/../server/auth/CertManager.php");

define("SYSTEM_INI",__DIR__ . "/../../config/system.ini");
if (!file_exists(SYSTEM_INI)) {
    die("Missing system configuration file: ".SYSTEM_INI." . Please properly configure and install application");
}
$config =Config::getInstance();

/* check for properly installed xampp */
if( ! function_exists('openssl_get_publickey')) {
	die("Invalid configuration: please uncomment line 'module=php_openssl.dll' in file '\\xampp\\php\\php.ini'");
}

/* Check for https protocol. Previous versions allowed http in linux. This is no longer true*/
if (!is_https()) {
    die("You MUST use https protocol to access this application");
}

/* check for properly installed xampp */
if( ! function_exists('password_verify')) {
    die("Invalid environment: You should have php-5.5.X or higher version installed");
}
$runmode=intval($config->getEnv('running_mode'));
if ( $runmode === AC_RUNMODE_SLAVE ) { // in slave mode restrict access to public directory
    die("Access other than public directory is not allowed");
}

// access to console is forbidden in master mode unless master server with valid certificate
$cm_user="";
$cm_password="";
if ( $runmode === AC_RUNMODE_MASTER) {
    // if not in master server drop connection
    // PENDING: master mode, but not in master server really means configuration error
    if (!inMasterServer($config)) die("Access other than public directory is not allowed in this server");
    // in master server access to console is controlled by mean of SSL certificates
    $cm=new CertManager();
    if ("" !== $cm->hasValidCert()) die("Access to tablet in this server requires valid certificate");
    // ok, valid certificate, so check ACL
    if ($cm->checkCertACL() === "") die("Provided certificate has no rights to access into tablet display");
    // acl ok: retrieve credentials
    $cm_user=$cm->getCertCN();
    $cm_password="CERTIFICATE";
}
?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="application-name" content="Agility Contest" />
<meta name="copyright" content="© 2013-2015 Juan Antonio Martinez" />
<meta name="author" lang="en" content="Juan Antonio Martinez" />
<meta name="description"
        content="A web client-server (xampp) app to organize, register and show results for FCI Dog Agility Contests" />
<meta name="distribution" 
	content="This program is free software; you can redistribute it and/or modify it under the terms of the 
		GNU General Public License as published by the Free Software Foundation; either version 2 of the License, 
		or (at your option) any later version." />
<!-- try to disable zoom in tablet on double click -->
<meta content='width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no' name='viewport' />
<title>AgilityContest (Tablet)</title>
<link rel="stylesheet" type="text/css" href="../lib/jquery-easyui-1.4.2/themes/<?php echo $config->getEnv('easyui_theme'); ?>/easyui.css" />
<link rel="stylesheet" type="text/css" href="../lib/jquery-easyui-1.4.2/themes/icon.css" />
<link rel="stylesheet" type="text/css" href="../css/style.css" />
<link rel="stylesheet" type="text/css" href="../css/datagrid.css" />
<link rel="stylesheet" type="text/css" href="../css/tablet.css" />
<script src="../lib/HackTimer/HackTimer.js" type="text/javascript" charset="utf-8" > </script>
<script src="../lib/jquery-2.2.4.min.js" type="text/javascript" charset="utf-8" > </script>
<script src="../lib/jquery-easyui-1.4.2/jquery.easyui.min.js" type="text/javascript" charset="utf-8" ></script>
<script src="../lib/jquery-easyui-1.4.2/locale/easyui-lang-<?php echo substr($config->getEnv('lang'),0,2);?>.js" type="text/javascript" charset="utf-8" > </script>
<script src="../lib/jquery-easyui-1.4.2/extensions/datagrid-dnd/datagrid-dnd.js" type="text/javascript" charset="utf-8" > </script>
<?php if (toBoolean($config->getEnv('tablet_chrono'))) { ?>
<script src="../lib/jquery-chronometer.js" type="text/javascript" charset="utf-8" > </script>
<?php } ?>
<script src="../lib/jquery-easyui-1.4.2/extensions/datagrid-view/datagrid-detailview.js" type="text/javascript" charset="utf-8" > </script>
<script src="../lib/jquery-easyui-1.4.2/extensions/datagrid-view/datagrid-scrollview.js" type="text/javascript" charset="utf-8" > </script>
<script src="../lib/jquery-fileDownload-1.4.2.js" type="text/javascript" charset="utf-8" > </script>
    <script src="../lib/sprintf.js" type="text/javascript" charset="utf-8" > </script>
    <script src="../lib/queue_and_stack.js" type="text/javascript" charset="utf-8" > </script>
<script src="../scripts/easyui-patches.js" type="text/javascript" charset="utf-8" > </script>
<script src="../scripts/datagrid_formatters.js.php" type="text/javascript" charset="utf-8" > </script>
<script src="../scripts/common.js.php" type="text/javascript" charset="utf-8" > </script>
<script src="../scripts/auth.js.php" type="text/javascript" charset="utf-8" > </script>
<script src="../scripts/competicion.js.php" type="text/javascript" charset="utf-8" > </script>
<script src="../scripts/admin.js.php" type="text/javascript" charset="utf-8" > </script>
<script src="../scripts/events.js" type="text/javascript" charset="utf-8" > </script>
<script src="../tablet/tablet.js.php" type="text/javascript" charset="utf-8" > </script>

<script type="text/javascript" charset="utf-8">

/* make sure configuration is loaded from server before onLoad() event */
loadConfiguration();
getLicenseInfo();
getFederationInfo();

var ac_clientOpts = {
    Source:       'tablet',
    Destination:    '',
	Ring:           0,
    View:           0,
    Mode:           0,
	StartStopMode:  0, // 0:stop, 1:start, -1:auto
	DataEntryEnabled: 0, // 0: roundSelection enabled 1:dataEntry enabled
	CourseWalk:     0, // 0 reconocimiento de pista parado else time
    // nombre del cliente utilizado para control de expire-time
    // after login becomes "tablet_random@ring"
    Name:           '<?php echo http_request("Name","s",getDefaultClientName('tablet')); ?>',
    SessionName:    ""
};
ac_clientOpts.SessionName=composeClientSessionName(ac_clientOpts);

function initialize() {
	// make sure that every ajax call provides sessionKey
	$.ajaxSetup({
	  beforeSend: function(jqXHR,settings) {
		if ( typeof(ac_authInfo.SessionKey)!=="undefined" && ac_authInfo.SessionKey!==null) {
			jqXHR.setRequestHeader('X-Ac-Sessionkey',ac_authInfo.SessionKey);
		}
	    return true;
	  }
	});
}

/**
 * Common rowStyler function for AgilityContest tablet datagrids
 * @param {int} idx Row index
 * @param {object} row Row data
 * @return {string} proper row style for given idx
 */
function myRowStyler(idx,row) {
	var res="height:35px;background-color:";
	var c1='<?php echo $config->getEnv('easyui_rowcolor1'); ?>';
	var c2='<?php echo $config->getEnv('easyui_rowcolor2'); ?>';
	if ( (idx&0x01)===0) { return res+c1+";"; } else { return res+c2+";"; }
}

</script>

<style>
body { font-size: 100%;	background: <?php echo $config->getEnv('easyui_bgcolor'); ?>; }

</style>

</head>

<body style="margin:0;padding:0" onload="initialize();">

<div id="tablet_contenido" style="width:100%;height:100%;margin:0;padding:0"></div>

<!--  CUERPO PRINCIPAL DE LA PAGINA (se modifica con el menu) -->

<div id="seltablet-dialog" style="width:450px;height:auto;padding:10px" class="easyui-dialog"
	data-options="title: '<?php _e('User,Ring,Contest and Journey selection'); ?>',iconCls: 'icon-list',buttons: '#seltablet-Buttons',collapsible:false, minimizable:false,
		maximizable:false, closable:false, closed:false, shadow:false, modal:true">
	<form id="seltablet-form">
       	<div class="fitem">
       		<label for="seltablet-Username"><?php _e('User'); ?>:</label>
       		<input id="seltablet-Username" name="Username" style="width:200px" type="text"
       			class="easyui-textbox" data-options="iconCls:'icon-man',required:true,validType:'length[1,255]'"/>
       	</div>        		
       	<div class="fitem">
       		<label for="seltablet-Password"><?php _e('Password'); ?>:</label>
       		<input id="seltablet-Password" name="Password" style="width:200px" type="password"
       			class="easyui-textbox" data-options="iconCls:'icon-lock',required:true,validType:'length[1,255]'"/>
       	</div>
       	<div>&nbsp;</div>
    	<div class="fitem">
       		<label for="seltablet-Sesion"><?php _e('Session'); ?>:</label>
       		<select id="seltablet-Sesion" name="Sesion" style="width:200px"></select>
    	</div>        		
    	<div class="fitem">
       		<label for="seltablet-Prueba"><?php _e('Contest'); ?>:</label>
       		<select id="seltablet-Prueba" name="Prueba" style="width:200px"></select>
    	</div>        		
    	<div class="fitem">
       		<label for="seltablet-Jornada"><?php _e('Journey'); ?>:</label>
       		<select id="seltablet-Jornada" name="Jornada" style="width:200px"></select>
    	</div>
	</form>
</div> <!-- Dialog -->

<div id="seltablet-Buttons" style="text-align:right;padding:5px;">
   	<a id="seltablet-okBtn" href="#" class="easyui-linkbutton" 
   	   	data-options="iconCls:'icon-ok'" onclick="tablet_acceptSelectJornada()"><?php _e('Accept'); ?></a>
</div>	<!-- botones -->

<script type="text/javascript">

workingData.testDog= {
	'Parent':		"",
	'Prueba':		0,
	'Jornada':		0,
	'Manga':		0,
	'Tanda':		"", // nombre
	'ID':			0,
	'Perro':		0,
	'Licencia':		"",
	'Pendiente':	0,
	'Equipo':		0,
	'NombreEquipo': "",
	'Dorsal':		0,
	'Nombre':		"<?php _e('Test dog'); ?>",
	'NombreLargo':	"",
	'Celo':			0,
	'NombreGuia':	"",
	'NombreClub':	"",
	'Categoria':	"-",
	'Grado':		"-",
	'Faltas':		0,
	'Rehuses':		0,
	'Tocados':		0,
	'Tiempo':		0.0,
	'TIntermedio':	0.0,
	'Eliminado':	0,
	'NoPresentado':	0,
	'Observaciones': ""
};

$('#seltablet-form').form();

$('#seltablet-Sesion').combogrid({
	panelWidth: 500,
	panelHeight: 150,
	idField: 'ID',
	textField: 'Nombre',
	url: '../ajax/database/sessionFunctions.php',
	method: 'get',
	mode: 'remote',
	required: true,
	rownumber: true,
	multiple: false,
	fitColumns: true,
	singleSelect: true,
	editable: false,  // to disable tablet keyboard popup
	columns: [[
	   	{ field:'ID',			width:'5%', sortable:false, align:'center', title:'ID' }, // Session ID
		{ field:'Nombre',		width:'25%', sortable:false,   align:'center',  title: '<?php _e('Name');?>' },
		{ field:'Comentario',	width:'60%', sortable:false,   align:'left',  title: '<?php _e('Comments');?>' },
		{ field:'Prueba', hidden:true },
		{ field:'Jornada', hidden:true }
	]],
	onBeforeLoad: function(param) { 
		param.Operation='selectring';
		param.Hidden=0;
		return true;
	},
	onLoadSuccess: function(data) {
		var ts=$('#seltablet-Sesion');
		var def= ts.combogrid('grid').datagrid('getRows')[0].ID; // get first ID
		ts.combogrid('setValue',def);
	},
    onSelect: function(index,row) {
        // update session name and ring information
	    var ri=parseInt(row.ID)-1;
        ac_clientOpts.Ring=row.ID;
        ac_clientOpts.Name= ac_clientOpts.Name.replace(/@.*/g,"@"+ri);
        ac_clientOpts.SessionName= composeClientSessionName(ac_clientOpts);
    }
});

$('#seltablet-Prueba').combogrid({
	panelWidth: 500,
	panelHeight: 150,
	idField: 'ID',
	textField: 'Nombre',
	url: '../ajax/database/pruebaFunctions.php?Operation=enumerate',
	method: 'get',
	mode: 'remote',
	required: true,
	multiple: false,
	fitColumns: true,
	singleSelect: true,
	editable: false,  // to disable tablet keyboard popup
	selectOnNavigation: true, // let use cursor keys to interactive select
	columns: [[
		{field:'ID',			hidden:true},
		{field:'Nombre',		title:'<?php _e('Name');?>',		width:'50%',	align:'right'},
		{field:'Club',			hidden:true},
		{field:'NombreClub',	title:'<?php _e('Club');?>',		width:'30%',	align:'right'},
		{field:'RSCE',			title:'<?php _e('Fed');?>',			width:'10%',	align:'center', formatter:formatFederation},
		{field:'Observaciones',	hidden:true},
        {field:'Inscritos',		hidden:true}
	]],
	onChange:function(value){
		var pru=$('#seltablet-Prueba').combogrid('grid');
		var p=pru.datagrid('getSelected');
		var j=$('#seltablet-Jornada');
		if (p===null) return; // no selection
		setPrueba(p); // retorna jornada, o 0 si la prueba ha cambiado
		j.combogrid('clear');
		j.combogrid('grid').datagrid('load',{Prueba:p.ID});
	},
	onLoadSuccess: function() {
		$('#seltablet-Prueba').combogrid('grid').datagrid('selectRow', 0);
		$('#seltablet-Username').textbox('textbox').focus();
	}
});

$('#seltablet-Jornada').combogrid({
	panelWidth: 550,
	panelHeight: 150,
	idField: 'ID',
	textField: 'Nombre',
	url: '../ajax/database/jornadaFunctions.php',
	method: 'get',
	mode: 'remote',
	required: true,
	multiple: false,
	fitColumns: true,
	singleSelect: true,
	editable: false, // to disable tablet keyboard popup
	columns: [[
	    { field:'ID',			hidden:true }, // ID de la jornada
	    { field:'Prueba',		hidden:true }, // ID de la prueba
	    { field:'Numero',		width:4, sortable:false,	align:'center', title: '#'},
		{ field:'Nombre',		width:40, sortable:false,   align:'right',  title: '<?php _e('Name/Comment');?>' },
		{ field:'Fecha',		hidden:true},
		{ field:'Hora',			hidden:true},
        { field:'PreAgility',	width:7, sortable:false,	align:'center', title: 'PreAg. ', formatter:formatPreAgility },
        { field:'Children',	    width:7, sortable:false,	align:'center', title: 'Children', formatter:formatOk },
        { field:'Junior',	    width:7, sortable:false,	align:'center', title: 'Junior ', formatter:formatOk },
        { field:'Senior',	    width:7, sortable:false,	align:'center', title: 'Senior ', formatter:formatOk },
        { field:'ParaAgility',  width:7, sortable:false,	align:'center', title: 'ParaAg.', formatter:formatOk },
		{ field:'Grado1',		width:7, sortable:false,	align:'center', title: 'G-I    ', formatter:formatGrado1 },
		{ field:'Grado2',		width:7, sortable:false,	align:'center', title: 'G-II   ', formatter:formatOk },
		{ field:'Grado3',		width:7, sortable:false,	align:'center', title: 'G-III  ', formatter:formatOk },
		{ field:'Open',		    width:7, sortable:false,	align:'center', title: 'Open   ', formatter:formatOk },
		{ field:'Equipos3',		width:11, sortable:false,	align:'center', title: 'Equipos', formatter:formatTeamDogs },
		{ field:'Equipos4',		hidden:true},
		{ field:'KO',			width:7, sortable:false,	align:'center', title: 'K.O.   ', formatter:formatOk },
		{ field:'Especial',		width:6, sortable:false,	align:'center', title: 'Show   ', formatter:formatOk }
	]],
	onBeforeLoad: function(param) {
	    if (typeof(workingData.prueba)=="undefined") return false;
		param.Operation='enumerate';
		param.Prueba=workingData.prueba;
		param.AllowClosed=0;
		param.HideUnassigned=1;
		return true;
	}
});

function tablet_acceptSelectJornada() {
	// si prueba invalida cancelamos operacion
	var s=$('#seltablet-Sesion').combogrid('grid').datagrid('getSelected');
	var p=$('#seltablet-Prueba').combogrid('grid').datagrid('getSelected');
	var j=$('#seltablet-Jornada').combogrid('grid').datagrid('getSelected');
	var user=$('#seltablet-Username').val();
	var pass=$('#seltablet-Password').val();
	if ( (p===null) || (j===null) ) {
		// indica error
		$.messager.alert("Error","<?php _e('You must'); ?><br />- <?php _e('Select session/ring for videowall and chrono'); ?><br />- <?php _e('Select contest/journey to play with'); ?>","error");
		return;
	}
	if (!user || !user.length) {
		$.messager.alert("Invalid data",'<?php _e("No user has been selected");?>',"error");
		return;
    }
    var parameters={
		'Operation':'login',
		'Username': user,
		'Password': pass,
		'Session' : s.ID,
		'Nombre'  : s.Nombre,
		'Source'  : 'tablet_'+s.ID,
		'Prueba'  : p.ID,
		'Jornada' : j.ID,
		'Manga'   : 0,
		'Tanda'   : 0,
		'Perro'   : 0
	};
	// de-activate accept button during ajax call
    $('#seltablet-okBtn').linkbutton('disable');
	$.ajax({
		type: 'POST',
        url: '../ajax/database/userFunctions.php',
   		dataType: 'json',
   		data: parameters,
   		success: function(data) {
    		if (data.errorMsg) { 
        		$.messager.alert("Error",data.errorMsg,"error"); 
        		initAuthInfo(); // initialize to null
        	} else {
    		    var ll="";
    		    if (data.LastLogin!=="") {
    		        ll = "<?php _e('Last login');?>:<br/>"+data.LastLogin;
                }
                // close dialog
                $('#seltablet-dialog').dialog('close');
        		$.messager.alert(
        	    	"<?php _e('User');?>: "+data.Login,
        	    	'<?php _e("Session successfully started");?><br/>'+ll,
        	    	"info",
        	    	function() {
        	    	   	initAuthInfo(data);
        	    	   	initWorkingData(s.ID,tablet_eventManager); // synchronous ajax call inside :-(
        	    	   	// los demas valores se actualizan en la linea anterior
        	    		workingData.nombrePrueba=p.Nombre;
						workingData.datosPrueba=p;
						workingData.nombreJornada=j.Nombre;
                        workingData.datosJornada=j;
                        // jornadas "normales", equipos3 e Individual-Open comparten el mismo fichero
        	    		var page="../tablet/tablet_main.php";
        	    		if (parseInt(workingData.datosJornada.Equipos4)>1) {
        	    			page="../tablet/tablet_main.php"; // parche temporal. Pendiente de implementar tablet Equipos4
        	    		}
        	    		if (parseInt(workingData.datosJornada.KO)===1) {
        	    		    $.messager.alert("Not available yet","Tablet layout for KO rounds is not yet available<br/>Usin std one","info");
                            // page="../tablet/tablet_main_ko.php";
                            page="../tablet/tablet_main.php";
        	    		}
                        // load requested page
        	    		$('#tablet_contenido').load(	
        	    				page,
        	    				function(response,status,xhr){
        	    					if (status==='error') $('#tablet_contenido').load('../frm_notavailable.php');
        	        	    		// start event manager
        	        	    		startEventMgr();
									setDataEntryEnabled(false);
                                    $('#tablet-layout').layout('panel','west').panel('setTitle',p.Nombre+" - "+ j.Nombre);
									$('#tdialog-InfoLbl').html(p.Nombre + ' - ' + j.Nombre);
									bindKeysToTablet();
        	    				}
        	    			); // load
        	    	} // close dialog; open main window
        	    ); // alert calback
        	} // if no ajax error
    	}, // success function
        error: function(XMLHttpRequest,textStatus,errorThrown) {
            // connection error: show an slide message error at bottom of the screen
            $.messager.show({
                title:"<?php _e('Error');?>",
                msg: "<?php _e('Error');?>: acceptSelectJornada(): "+XMLHttpRequest.status+" - "+XMLHttpRequest.responseText+" - "+textStatus + " "+ errorThrown,
                timeout: 5000,
                showType: 'slide',
                height:200
            });
        },
        // after ajax call re-enable "Accept" button
        complete: function(data) {
            $('#seltablet-okBtn').linkbutton('enable');
        }
	}); // ajax call
}

$('#seltablet-Username').textbox({
    required:true,
    value:'<?php echo $cm_user;?>',
    validType:'length[1,255]',
    disabled: <?php echo ($cm_user!=="")? 'true':'false';?>,
    iconCls:'icon-man'
}).bind('keypress', function (evt) {
    //on Enter key on login field focus on password
    if (evt.keyCode !== 13) return true;
    $('#seltablet-Password').focus();
    return false;
});

$('#seltablet-Password').textbox({
    required:true,
    value:'<?php echo $cm_password;?>',
    validType:'length[1,255]',
    disabled: <?php echo ($cm_password!=="")? 'true':'false';?>,
    iconCls:'icon-lock'
}).bind('keypress', function (evt) {
//on Enter key on password field jump to Session selection
    if (evt.keyCode !== 13) return true;
    $('#seltablet-Sesion').next().find('input').focus();
    return false;
});


</script>
</body>
</html> 
