<!-- 
frm_about.php

Copyright  2013-2018 by Juan Antonio Martinez ( juansgaviota at gmail dot com )

This program is free software; you can redistribute it and/or modify it under the terms 
of the GNU General Public License as published by the Free Software Foundation; 
either version 2 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; 
without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. 
See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program; 
if not, write to the Free Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 -->

<?php
require_once(__DIR__ . "/../server/tools.php");
require_once(__DIR__ . "/../server/auth/Config.php");
$config =Config::getInstance();
?>

<div id="dlg_register" style="width:800px;padding:10px">
	<img src="../images/AgilityContest.png"
		width="150" height="100" alt="AgilityContest Logo" 
		style="border:1px solid #000000;margin:10px;float:right;padding:5px">
	<dl>
		<dt>
			<strong><?php _e('Version'); ?>: </strong><span id="reg_version">version</span> - <span id="reg_date">date</span>
		</dt>
		<dt>
			<strong>AgilityContest</strong> <?php _e('is Copyright &copy; 2013-2018 by'); ?> <em> Juan Antonio Martínez &lt;juansgaviota@gmail.com&gt;</em>
		</dt>
		<dd>
		<?php _e('Source code is available at'); ?> <a href="https://github.com/jonsito/AgilityContest">https://github.com/jonsito/AgilityContest</a><br />
		<?php _e('You can use, copy, modify and re-distribute under terms of'); ?>
		<a target="license" href="../License"><?php _e('GNU General Public License'); ?></a>
		</dd>
	</dl>
	<p>
	<?php _e('Registered at'); ?> 'Registro Territorial de la Propiedad Intelectual de Madrid'. <em>Expediente: 09-RTPI-09439.4/2014</em>
	</p>
	<div>
		<span style="float:right">
            &nbsp;<br/>&nbsp;<br/>
			<a id="registration-cancelButton" href="#" class="easyui-linkbutton"
   			data-options="iconCls:'icon-cancel'"
   			onclick="$('#dlg_register').window('close');"><?php _e('Close'); ?></a>
		</span>
	</div>
</div>

<script type="text/javascript">
    $('#dlg_register').window({
        title: '<?php _e("Licensing information"); ?>',
        collapsible:false,
        minimizable:false,
        maximizable:false,
        resizable:false,
        closable:false,
        modal:true,
        iconCls: 'icon-dog',
        onOpen: function() {
            $('#reg_version').html(ac_config.version_name);
            $('#reg_date').html(ac_config.version_date);
            $('#registration_data').form('load','../ajax/adminFunctions.php?Operation=reginfo');
        },
        onClose: function() {loadContents('../console/frm_main.php','',{'registration':'#dlg_register'});
        }
    });
</script>