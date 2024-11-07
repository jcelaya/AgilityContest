<?php header('Content-Type: text/javascript'); ?>
/*
auth.js

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

/*
* Client-side uthentication related functions
*/

/**
 * Abre el frame de login o logout dependiendo de si se ha iniciado o no la sesion
 */
function showLoginWindow() {
	if (typeof(ac_authInfo.SessionKey)==="undefined" || (ac_authInfo.SessionKey==null) ) {
		$('#login-window').remove();
		loadContents('../console/frm_login.php','<?php _e('Init session');?>');
	} else {
		$('#logout-window').remove();
		loadContents('../console/frm_logout.php','<?php _e('End session');?>');
	}
}

function showMyAdminWindow() {
	$('#myAdmin-window').remove();
	loadContents('../console/frm_myAdmin.php','<?php _e('Direct database access');?>');
}

function askForUpdateDB() {
    if (!checkForAdmin(true)) return; // check for valid admin user
    var str1='<?php _e("Do you want to enable remote database updates?");?>';
    var str2='<?php _e('Before accept, please read legal terms and conditions');?>';
    var str3='<a target="lopd" href="http://www.agilitycontest.es/lopd.html"><?php _e(" at this link");?></a>';
    var str4='<input type="checkbox" id="askForUpdateDBChk" value="0"> ';
    var str5='<label for="askForUpdate"><?php _e("Do not show this message again");?>';
    $.messager.confirm({
        closable: false,
        title:  '<?php _e("Enable sharing");?>',
        msg:    str1+'<br/>'+str2+" "+str3+'<br/>&nbsp;<br/>'+str4+" "+str5,
        width:  500,
        fn: function(r){
                var st=$('#askForUpdateDBChk').prop('checked');
                if (r || ( !r && st)) {
                    ac_config.search_updatedb=(r)?"1":"0";
                    // call server to update ac_config.search_updatedb
                    $.ajax({
                        type:'GET',
                        url:"../ajax/adminFunctions.php",
                        dataType:'json',
                        data: {
                            Operation: 'setEnv',
                            Key: "search_updatedb",
                            Value: ac_config.search_updatedb
                        },
                        success: function(res) {
                            $.messager.alert({ width:300, height:'auto', title: '<?php _e('Done.'); ?>', msg: '<?php _e('Configuration saved');?>' });
                        },
                        error: function(XMLHttpRequest,textStatus,errorThrown) {
                            $.messager.alert("Error: "+oper,"Error: "+XMLHttpRequest.status+" - "+XMLHttpRequest.responseText+" - "+textStatus + " "+ errorThrown,'error' );
                        }
                    });

                    return true;
                }
                return true;
            }
    });
}

function acceptLogin() {
	var user= $('#login-Username').val();
	var pass=$('#login-Password').val();
	var fed=$('#login-Federation').combogrid('getValue');
	if (!user || !user.length) {
		$.messager.alert("Invalid data",'<?php _e("There is no user chosen");?>',"error");
		return;
	}
	// set federation
    if (parseInt(fed)<0) {
        $.messager.alert("Invalid data",'<?php _e("There is no chosen federation");?>',"error");
        return;
    }
    // disable accept button to avoid pressing twice
    $('#login-okBtn').linkbutton('disable');
	$.ajax({
		type: 'POST',
  		url: '../ajax/database/userFunctions.php',
   		dataType: 'json',
   		data: {
   			Operation: 'login',
   			Username: user,
   			Password: pass,
			Federation: fed
   		},
   		success: function(data) {
       		if (data.errorMsg) { // error
                $.messager.alert({width:350, height:'auto', title:'<?php _e('Error'); ?>',msg: data.errorMsg,icon:'error' });
       			initAuthInfo();
				unsetSessionCookie();
       		} else {
				consoleLoginSuccessful(data);
       		} 
       	},
        error: function(XMLHttpRequest,textStatus,errorThrown) {
            // connection error: show an slide message error at bottom of the screen
            $.messager.show({
                title:"<?php _e('Error');?>",
                msg: "<?php _e('Error');?>: acceptLogin() "+XMLHttpRequest.status+" - "+XMLHttpRequest.responseText+" - "+textStatus + " "+ errorThrown,
                timeout: 5000,
                showType: 'slide',
                height:200
            });
        },
        complete: function(data) {
            $('#login-okBtn').linkbutton('enable');
        }
	});
	$('#login-window').window('close');
}

function acceptLogout() {
	var user=ac_authInfo.Login;
	$.ajax({
		type: 'POST',
   		url: '../ajax/database/userFunctions.php',
   		dataType: 'json',
   		data: {
   			Operation: 'logout',
   			Username: user,
   			Password: ""
   		},
   		contentType: 'application/x-www-form-urlencoded;charset=UTF-8',
   		success: function(data) {
       		if (data.errorMsg) { // error
       			$.messager.alert("Error",data.errorMsg,"error");
       		} else {// success: 
       			$.messager.alert('<?php _e("User");?>'+" "+user,'<?php _e("Session has been closed by user");?>',"info");
           		$('#login_menu-text').html('<?php _e("Init session");?>');
				unsetSessionCookie();
           		initAuthInfo();
           		setFederation(0); // on logout defaults to RSCE
                // fire named backup
                autoBackupDatabase(0,"");
                // disable timer based auto-backup
                if (ac_config.backup_timeoutHandler!==null) clearTimeout(ac_config.backup_timeoutHandler);
                // disable console event handler
                if (ac_config.event_timeoutHandler!==null) clearTimeout(ac_config.event_timeoutHandler);
       		} 
       	},
   		error: function() { alert("error");	}
	});
	$('#logout-window').window('close');	
}

function consoleLoginSuccessful(data) {
	setFederation(data.Federacion);
	// change menu message to logout
	$('#login_menu-text').html('<?php _e("End session");?>' + ": " + data.Login);
	// initialize auth info
	initAuthInfo(data);
	if (checkForAdmin(false)) { // do not handle syncdb unless admin login
		// if not configured ( value<0 ) ask user to enable autosync database
		var up = parseInt(ac_config.search_updatedb);
		if (up < 0) {
			setTimeout(function () {
				askForUpdateDB();
			}, 500);
		}
		if ((up > 0) && ($('#login_updatedb').prop('checked') == true)) {
			setTimeout(function () {
				synchronizeDatabase(false)
			}, 500);
		}
	}

	// force backup on login success
	autoBackupDatabase(0,"");
	// if configured, trigger autobackup every "n" minutes
	var bp=parseInt(ac_config.backup_period);
	if (bp!=0) ac_config.backup_timeoutHandler=setTimeout(function() {trigger_autoBackup(bp);},60*bp*1000);

	// fire up console event manager
	ac_config.event_handler=console_eventManager;
	ac_clientOpts.Name=data.Login+"@Console"
	ac_clientOpts.SessionName=composeClientSessionName(ac_clientOpts);
	var ce=parseInt(ac_config.console_events);
	if (ce!=0) startEventMgr();
}

function unsetSessionCookie() {
	document.cookie = "ACSID=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
}

function tryCurrentSession(successCallback) {
	$.ajax({
		type: 'POST',
		url: '../ajax/database/userFunctions.php',
        async: false,
		dataType: 'json',
		data: {
			Operation: 'me'
   		},
   		success: function(data) {
			if (data.errorMsg) { // error
				initAuthInfo();
				unsetSessionCookie();
			} else {
				successCallback(data);
			}
       	},
        error: function(XMLHttpRequest,textStatus,errorThrown) {
            // connection error: show an slide message error at bottom of the screen
            $.messager.show({
                title:"<?php _e('Error');?>",
                msg: "<?php _e('Error');?>: acceptLogin() "+XMLHttpRequest.status+" - "+XMLHttpRequest.responseText+" - "+textStatus + " "+ errorThrown,
                timeout: 5000,
                showType: 'slide',
                height:200
            });
        }
	});
}

function checkPassword(user,pass,callback) {
	$.ajax({
		type: 'POST',
        url: '../ajax/database/userFunctions.php',
		dataType: 'json',
		data: {
			Operation: 'pwcheck',
			Username: user,
			Password: pass
		},
		contentType: 'application/x-www-form-urlencoded;charset=UTF-8',
		success: function(data) { callback(data); },
		error: function() { alert("error");	}
	});
}

function acceptMyAdmin() {
	var user= $('#myAdmin-Username').val();
	var pass=$('#myAdmin-Password').val();
	if (!user || !user.length) {
		$.messager.alert("Invalid data",'<?php _e("No user specified");?>',"error");
		return;
	}
	checkPassword(user,pass,function(data) {
		if (data.errorMsg) { // error
			$.messager.alert("Error",data.errorMsg,"error");
		} else { // success:
			if (parseInt(data.Perms)<=1) {
				var loc = window.location.pathname;
				var dir = loc.substring(0, loc.lastIndexOf('agility'));
				window.open(dir + "agility/phpmyadmin","phpMyAdmin");
			}
			else $.messager.alert("Error",'<?php _e("Current user has no <em>admin</em> privileges");?>',"error");
		}
	});
	$('#myAdmin-window').window('close');
}

function cancelLogin() {
	$('#login-Usuario').val('');
	$('#login-Password').val('');
	setFederation(0); // defaults to first federation (rsce)
	var w=$.messager.alert("Login","<?php _e('No user provided');?>"+"<br />"+"<?php _e('Starting session read-only (guest)');?>","warning",function(){
		// close window
		$('#login-window').window('close');
	});
}

function cancelLogout() {
	// close window
	$('#logout-window').window('close');
}

function cancelMyAdmin() {
	// close window
	$('#myAdmin-window').window('close');
}

function read_regFile(input) {
	if (input.files && input.files[0]) {
		var reader = new FileReader();
		reader.onload = function(e) {
			$('#registrationData').val(e.target.result);
		};
		reader.readAsDataURL(input.files[0]);
	}
}

/*
 Comprueba si el usuario tiene privilegios suficientes para realizar la operacion indicada en callback
 (admin,operator,assistant,guest)
 En caso de no tener privilegios avisa, pero deja continuar
 Si callback es null, simplemente retorna true o false
 */
function check_softLevel(perm,callback) {
	if (typeof(callback) !== 'function') {
		return (ac_authInfo.Perms<=perm);
	}
	if (ac_authInfo.Perms>perm) {
		$.messager.alert(
			'<?php _e("User level");?>',
			'<?php _e("Current user has not enought level to make changes <br/>Read-only access enabled");?>',
			'warning',
			null
		).window('resize',{width:400});
	}
	callback();
}

/*
Comprueba si la licencia tiene habilitado el permiso para acceder a la funcionalidad deseada
( pruebas por equipos, ko, videomarcador, etc )
Por seguridad, los permisos no se comprueban nunca en el cliente, por lo que es necesaria una llamada
al servidor
 */
function check_access(perms,callback) {
    $.ajax({
        type:'GET',
        url:"../ajax/adminFunctions.php",
        dataType:'json',
        data: {
            Operation:	'userlevel',
            Prueba:	workingData.prueba,
            ID:workingData.jornada,
            Perms : perms
        },
        success: function(res) { callback(res); },
        error: function(XMLHttpRequest,textStatus,errorThrown) {
            $.messager.alert("Restricted","Error: "+XMLHttpRequest.status+" - "+XMLHttpRequest.responseText+" - "+textStatus + " "+ errorThrown,'error' );
        }
    });
}