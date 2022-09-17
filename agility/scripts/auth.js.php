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
    if (ac_regInfo.Serial==="00000000") return; // unregistered cannot share data
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
	setFederation(fed);
    // disable accept button to avoid pressing twice
    $('#login-okBtn').linkbutton('disable');
	$.ajax({
		type: 'POST',
  		url: '../ajax/database/userFunctions.php',
   		dataType: 'json',
   		data: {
   			Operation: 'login',
   			Username: user,
   			Password: pass
   		},
   		success: function(data) {
       		if (data.errorMsg) { // error
                $.messager.alert({width:350, height:'auto', title:'<?php _e('Error'); ?>',msg: data.errorMsg,icon:'error' });
       			initAuthInfo();
       		} else {// success:
       		    var wwidth=450;
                var vers=ac_config.version_name+"-"+ac_config.version_date;
       			var str="AgilityContest version: "+vers+"<br />";
       			str =str+'<?php _e("License registered by");?>'+": "+data.User+"<br />";
       			str =str+'<?php _e("For use at club");?>'+": "+data.Club+"<br />";
                if(data.Comments==="Default License") {
                    var msg ='<?php _e("License not found or license parsing error");?><br/>';
                    msg =msg+ '<?php _e("Using default (unregistered license) values");?>';
                    $.messager.show({width:325, height:150, title:'<?php _e('Error'); ?>',msg:msg,timeout:5000});
                }
				if (data.Expired==="1")  {
					str = str+'<br/><strong><span class="blink">'+'<?php _e("License expired");?>'+'</span></strong>';
					str = str+' <input type="button" value="Info..." onclick="window.open(\'https://www.agilitycontest.es/blog/license_update.html\');"/>'
				}
                if (data.Cancelled==="1")  {
                    str = str+'<br/><strong><span class="blink">'+'<?php _e("License cancelled");?>'+'</span></strong>';
                }
				if(parseInt(ac_config.backup_disabled)!==0) {
				    str = str+'<br/><strong><span class="blink">'+'<?php _e("Automatic backup is disabled");?>'+'</span></strong>';
                }
                if (data.LastLogin !== "") {
                    str = str+'<br /><?php _e("Last login");?>: '+ data.LastLogin;
                }
				if (data.NewVersion>vers) {
				    str = str+'<br /><?php _e("New version available");?>: '+data.NewVersion;
                }
                if ( (parseInt(ac_config.search_updatedb)>0) && (parseInt(data.NewEntries)!==0) ){
                    wwidth=550;
                    str = str+'<br />'+data.NewEntries+' <?php _e("new/updated entries from server Database");?>';
                    str = str + '<span style="display:inline-block;width:50px">&nbsp</span>';
                    str = str + '<input type="checkbox" id="login_updatedb" value="1"><?php _e("Update");?></input>';
                } else { // when update is enabled, but no new entries create hidden checkbox to avoid javascript error
                    str = str + '<input type="checkbox" id="login_updatedb" value="0" style="display:none"/>';
                }
       			str =str+'<br /><br />'+'<?php _e("User");?>'+" "+data.Login+": "+'<?php _e("session login success");?>';
       			if(data.Warning!=="") {
                    $.messager.show({ width:300, height:150, title: "<?php _e('Notice');?>", msg: data.Warning });
                }
       			var w=$.messager.alert({
                    closable: false, // do not show close button at upper right corner: fn is needed anyway
       			    title:  "Login",
                    msg: str,
                    icon: "info",
                    width: wwidth,
                    height: 'auto',
                    fn: function() {
                        // if configured try to load latest news from agilitycontest.es
                        if (ac_config.console_news) {
                            loadContents('../console/frm_news.php','<?php _e('Latest news');?>');
                        }
                    },
                    onClose: function() {
                        // change menu message to logout
                        $('#login_menu-text').html('<?php _e("End session");?>' + ": <br />" + data.Login);
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
                    }
				});
                w.window('center');

                // force backup on login success
                autoBackupDatabase(0,"");
                // if configured, trigger autobackup every "n" minutes
                var bp=parseInt(ac_config.backup_period);
                if (bp!=0) ac_config.backup_timeoutHandler=setTimeout(function() {trigger_autoBackup(bp);},60*bp*1000);

                // fire up console event manager
                ac_config.event_handler=console_eventManager;
                ac_clientOpts.Name=user+"@Console"
                ac_clientOpts.SessionName=composeClientSessionName(ac_clientOpts);
                var ce=parseInt(ac_config.console_events);
                if (ce!=0) startEventMgr();
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
			if (parseInt(data.Perms)<=1) window.open("/phpmyadmin","phpMyAdmin");
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

/**
 * @param event key event
 * @returns {boolean}
 */
function activateLicense(event) {

    // do the ajax call
    function handleActivateRemove(serial,remove) {
        $.ajax({
            type: 'GET',
            url: '../ajax/adminFunctions.php',
            dataType: 'json',
            data: {
                Operation: 'activateLicense',
                Serial: serial,
                Mode: remove // 0:activate; 1:remove
            },
            contentType: 'application/x-www-form-urlencoded;charset=UTF-8',
            success: function(data) {
                if (data.errorMsg){
                    $.messager.show({ width:300, height:150, title: 'Error', msg: data.errorMsg });
                } else {
                    var dok=$.messager.defaults.ok;
                    var dcancel=$.messager.defaults.cancel;
                    $.messager.defaults.ok="<?php _e('Restart');?>";
                    $.messager.defaults.cancel="<?php _e('Back');?>";
                    $('#registration_data').form('load',data); // update registration info form
                    // reload logo. note the trick to bypass image catching
                    $('#rd_Logo').attr("src","../ajax/images/getLicenseLogo.php?t="+new Date().getTime());
                    ac_regInfo=data; // store received data as new registration info
                    $('#avail_lic').combobox('reload'); // also reload list of available licenses

                    $.messager.confirm({
                        title:"<?php _e('Activate/Remove');?>",
                        msg:'<?php _e("Process completed");?>'+'<br/>&nbsp;<br/>'+
                            '<?php _e("Restart app to make changes to take effect");?>',
                        top:150,
                        width:450,
                        height:'auto',
                        icon:'info',
                        fn: function(r) {
                            // restore text
                            $.messager.defaults.ok=dok;
                            $.messager.defaults.cancel=dcancel;
                            // on request call save
                            if (r) window.location.reload();
                        }
                    });
                }
            },
            error: function() {
                $.messager.show({ width:350, height:150, title: 'Error', msg: "Error in request for license activation" });
            }
        });
    }

    let remove = (event.ctrlKey || event.metaKey)?1:0;
    let serial=$('#avail_lic').combobox('getValue')
    // check for admin privileges
    if (ac_authInfo.Perms>1) {
        $.messager.alert('Error',
            '<?php _e("Please: to activate/remove license");?>'+'<br/>'+
            '<?php _e("re-init session with a user with admin privileges");?>',
            'error');
        return false;
    }
    // check for invalid or empty serialnumber selected
    if(!serial || serial.trim()==="") {
        $.messager.alert('Error',
            '<?php _e("Please: to activate/remove license");?>'+'<br/>'+
            '<?php _e("must select a value from available ones");?>',
            'error');
        return false;
    }
    // en el caso de borrar licencia, verificar que no está activa
    if (remove===0) {
        handleActivateRemove(serial,remove);
        return false;
    } else {
        if (ac_regInfo.Serial===serial) {
            $.messager.alert('Error',
                '<?php _e("Cannot remove current active license");?>',
                'error');
            return false;
        }
        $.messager.confirm(
            '<?php _e("Delete");?>',
            '<?php _e("Please confirm remove license from list");?>',
            function (r) {
                if (r) handleActivateRemove(serial,remove);
                return false;
            }
        );
        return false;
    }
}

function send_regFile() {
    // check for registration form properly filled
    var ok=true;
    if (ac_authInfo.Perms>1) {
        $.messager.alert('Error',
            '<?php _e("Please: to register license");?>'+'<br/>'+
            '<?php _e("re-init session with a user with admin privileges");?>',
            'error');
        return false;
    }
    if ( ! $('#registration-Email').textbox('isValid') ) ok=false;
    if ( ! $('#registration-AKey').textbox('isValid') ) ok=false;
    if (ok===false) {
        $.messager.alert('Error','<?php _e("Please fill registration form with valid data");?>','error');
        return false;
    }
    // ok, make the call
    $.ajax({
  		type: 'POST',
    	url: '../ajax/adminFunctions.php',
    	dataType: 'json',
    	data: {
    		Operation: 'register',
            Email:$('#registration-Email').textbox('getText'),
            ActivationKey:$('#registration-AKey').textbox('getText')
    		// Data: $('#registrationData').val()
    	},
    	contentType: 'application/x-www-form-urlencoded;charset=UTF-8',
    	success: function(data) {
            if (data.errorMsg){
                $.messager.show({ width:300, height:150, title: 'Error', msg: data.errorMsg });
            } else {
                var dok=$.messager.defaults.ok;
                var dcancel=$.messager.defaults.cancel;
                $.messager.defaults.ok="<?php _e('Restart');?>";
                $.messager.defaults.cancel="<?php _e('Back');?>";
                $('#registration_data').form('load',data); // update registration info form
                // reload logo. note the trick to bypass image catching
                $('#rd_Logo').attr("src","../ajax/images/getLicenseLogo.php?t="+new Date().getTime());
                ac_regInfo=data; // store received data as new registration info
                $('#avail_lic').combobox('reload'); // also reload list of available licenses
                $.messager.confirm({
                    title:"<?php _e('Licensing');?>",
                    msg:'<?php _e("Licensing data successfully loaded");?>'+'<br/>&nbsp;<br/>'+
                        '<?php _e("Restart app to make changes to take effect");?>',
                    top:150,
                    width:450,
                    height:'auto',
                    icon:'info',
                    fn: function(r) {
                        // restore text
                        $.messager.defaults.ok=dok;
                        $.messager.defaults.cancel=dcancel;
                        // on request call save
                        if (r) window.location.reload();
                    }
                });
            }
    	},
    	error: function() {
            $.messager.show({ width:350, height:150, title: 'Error', msg: "Error in request for registration license file" });
  		}
   	});
}

/*
Same as above, but use ajax call to retrieve real permissions from server
 */
function check_permissions(perms, callback) {
	$.ajax({
		type: "GET",
		url: '../ajax/adminFunctions.php',
		data: {
			Operation:  'permissions',
			Perms:      perms
		},
		async: true,
		cache: false,
		dataType: 'json',
		success: function(data){ callback( data); },
		error: function(XMLHttpRequest,textStatus,errorThrown) {
			alert("check_permissions() error: "+XMLHttpRequest.status+" - "+XMLHttpRequest.responseText+" - "+textStatus + " "+ errorThrown );
		}
	});
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