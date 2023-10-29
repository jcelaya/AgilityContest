<!-- 
infomanga.inc

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
require_once(__DIR__ . "/../../server/tools.php");
require_once(__DIR__ . "/../../server/auth/Config.php");
require_once(__DIR__ . "/../../server/modules/Federations.php");
require_once(__DIR__ . "/../../server/modules/Competitions.php");
$config =Config::getInstance();
// retrieve federation info
$f=intval(http_request("Federation","i",0));
$m=intval(http_request("Manga","i",0));
$fed=Federations::getFederation($f);
if (!$fed) die ("Internal error::Invalid Federation ID: $f");
$heights=Competitions::getHeights(0,0,$m);
?>

<!-- Formulario que contiene los datos de una manga -->

<form id="competicion-formdatosmanga">
	<input type="hidden" id="dmanga_Operation" name="Operation" value=""/>
	<input type="hidden" id="dmanga_Jornada" name="Jornada" value=""/>
	<input type="hidden" id="dmanga_Manga" name="Manga" value=""/>
	<input type="hidden" id="dmanga_ID" name="ID" value=""/>
	<input type="hidden" id="dmanga_Tipo" name="Tipo" value=""/>
	<table id="competicion-tabladatosmanga">
		<tr>
			<td colspan="10">&nbsp;</td>
		</tr>
		<tr> <!-- fila 0: datos de los jueces -->
			<td colspan="3">
				<label for="dmanga_Juez1"><span style="text-align:right"><?php _e('Judge'); ?> 1:</span></label>
				<select id="dmanga_Juez1" name="Juez1" style="width:165px"></select>
			</td>
			<td colspan="3">
				<label for="dmanga_Juez2"><span style="text-align:right"><?php _e('Judge'); ?> 2:</span></label>
				<select id="dmanga_Juez2" name="Juez2" style="width:165px"></select>
			</td>
			<td align="left">
                <a href="#" class="easyui-linkbutton" data-options="iconCls:'icon-add'"
                   id="dmanga_AddJuez" onclick="newJuez('','')"><?php _e("New");?></a>
            </td>
			<td colspan="2" align="center">
				<a href="#" class="easyui-linkbutton" data-options="iconCls:'icon-whistle'" 
					id="dmanga_SameJuez" onclick="dmanga_shareJuez();"><?php _e('Replicate'); ?></a>
			</td>
		</tr>
		<tr>
			<td colspan="10">&nbsp;</td>
		</tr>
        <!-- JAMC Agosto 2020 Usamos el campo "Observaciones" para indicar si la manga de grado 1 es agility o jumping -->
        <tr id="dmanga_grado1_modality"><!-- fila 1: modalidad para Grado 1 (agility/jumping/otra) -->
            <td colspan="1"><?php _e('Modality'); ?>: </td>
            <td colspan="2"> <!-- Agility -->
                <input type="radio" id="dmanga_grado1_agility" name="Observaciones" value="Agility" onclick="mark_modified();"/>
                <label for="dmanga_grado1_agility">Agility</label>
            </td>
                <td colspan="2"> <!-- Jumping -->
                    <input type="radio" id="dmanga_grado1_jumping" name="Observaciones" value="Jumping" onclick="mark_modified();"/>
                    <label for="dmanga_grado1_jumping">Jumping</label>
                </td>
            <td colspan="3"> <!-- Otros (especificar) -->
                <input type="radio" id="dmanga_grado1_other" name="Observaciones" value="Other" onclick="mark_modified();"/>
                <label for="dmanga_grado1_other"><?php _e("Other"); ?>: </label>
                <input type="text" id="dmanga_grado1_other_value" value="" size="16"/>
            </td>
        </tr>
		<tr> <!-- fila 2 tipos de recorrido -->
			<td colspan="1"><?php _e('Courses'); ?>: </td>
			<td colspan="2"> <!-- comun -->
				<input type="radio" id="dmanga_Recorrido_0" name="Recorrido" value="2" onClick="mark_modified();dmanga_setRecorridos();"/>
				<label for="dmanga_Recorrido_0"><?php echo $fed->getRecorrido(0); ?></label>
			</td>
            <?php if ($heights==5) { ?>
                <td colspan="2"> <!-- mixto 3 grupos (5 alturas) -->
                    <input type="radio" id="dmanga_Recorrido_3" name="Recorrido" value="3" onClick="mark_modified();dmanga_setRecorridos();"/>
                    <label for="dmanga_Recorrido_3"><?php echo $fed->getRecorrido(3);  ?></label>
                </td>
            <?php } ?>
			<td colspan="2"> <!-- mixto 2 grupos -->
				<input type="radio" id="dmanga_Recorrido_1" name="Recorrido" value="1" onClick="mark_modified();dmanga_setRecorridos();"/>
				<label for="dmanga_Recorrido_1"><?php echo $fed->getRecorrido(1); ?></label>
			</td>
            <td colspan="2"> <!-- recorridos separados -->
                <input type="radio" id="dmanga_Recorrido_2" name="Recorrido" value="0" onClick="mark_modified();dmanga_setRecorridos();"/>
                <label for="dmanga_Recorrido_2"><?php echo $fed->getRecorrido(2);  ?></label>
            </td>
		</tr>
		<tr>
			<td colspan="10">&nbsp;</td>
		</tr>
		<tr style="background-color:#c0c0c0"> <!-- fila 2: titulos  -->
			<td><?php _e('Category'); ?></td>
			<td><?php _e('Distance'); ?></td>
			<td><?php _e('Obstacles'); ?></td>
			<td colspan="4"><?php _e('Standard Course Time'); ?></td>
			<td colspan="3"><?php _e('Maximum Course Time'); ?></td>
		</tr>

        <!-- fila 3: recorrido comun datos eXtra Large -->
        <tr id="dmanga_XLargeRow">
            <td id="dmanga_XLargeLbl">X-Large</td>
            <td>
                <label for="dmanga_DistX"></label>
                <input type="text" id="dmanga_DistX" name="Dist_X" size="4" value="0"/>
            </td>
            <td>
                <label for="dmanga_ObstX"></label>
                <input type="text" id="dmanga_ObstX" name="Obst_X" size="4" value="0"/>
            </td>
            <!-- datos para TRS X-Large -->
            <td>
                <label for="dmanga_TRS_X_Tipo"></label>
                <select id="dmanga_TRS_X_Tipo" name="TRS_X_Tipo">
                    <option value="0" selected="selected"><?php _e('Fixed SCT');?></option>
                    <option value="1"><?php _e('Best result');?> + </option>
                    <option value="2"><?php _e('3 best average');?> + </option>
                    <option value="6"><?php _e('Velocity');?> </option>
                </select>
            </td>
            <td>
                <label for="dmanga_TRS_X_Factor"></label>
                <input type="text" id="dmanga_TRS_X_Factor" name="TRS_X_Factor" size="4" value="0"/>
            </td>
            <td>
                <label for="dmanga_TRS_X_Unit"></label>
                <select id="dmanga_TRS_X_Unit" name="TRS_X_Unit">
                    <option value="s" selected="selected"><?php _e('Secs');?>.</option>
                    <option value="%">%</option>
                    <option value="m">m/s</option>
                </select>
            </td>
            <td>
                <input type="text" id="dmanga_TRS_X_TimeSpeed" name="TRS_X_TimeSpeed" readonly="readonly" disabled="disabled" size="10" value=""/>
            </td>
            <!-- datos para TRM X-Large -->
            <td>
                <label for="dmanga_TRM_X_Tipo"></label>
                <select id="dmanga_TRM_X_Tipo" name="TRM_X_Tipo">
                    <option value="0" selected="selected"><?php _e('Fixed MCT');?></option>
                    <option value="1"><?php _e('SCT');?> + </option>
                    <option value="6"><?php _e('Velocity');?> </option>
                </select>
            </td>
            <td>
                <label for="dmanga_TRM_X_Factor"></label>
                <input type="text" id="dmanga_TRM_X_Factor" name="TRM_X_Factor" size="4" value="0"/>
            </td>
            <td>
                <label for="dmanga_TRM_X_Unit"></label>
                <select id="dmanga_TRM_X_Unit" name="TRM_X_Unit" >
                    <option value="s" selected="selected"><?php _e('Secs');?>.</option>
                    <option value="%">%</option>
                    <option value="m">m/s</option>
                </select>
            </td>
        </tr>

        <!-- fila 4: recorrido comun datos standard -->
		<tr id="dmanga_LargeRow">
			<td id="dmanga_LargeLbl">Large</td>
			<td>
                <label for="dmanga_DistL"></label>
                <input type="text" id="dmanga_DistL" name="Dist_L" size="4" value="0"/>
            </td>
			<td>
                <label for="dmanga_ObstL"></label>
                <input type="text" id="dmanga_ObstL" name="Obst_L" size="4" value="0"/>
            </td>
			<!-- datos para TRS standard -->
			<td>
                <label for="dmanga_TRS_L_Tipo"></label>
				<select id="dmanga_TRS_L_Tipo" name="TRS_L_Tipo">
				<option value="0" selected="selected"><?php _e('Fixed SCT');?></option>
				<option value="1"><?php _e('Best result');?> + </option>
				<option value="2"><?php _e('3 best average');?> + </option>
                <?php if ($heights==5) { ?>
                    <option value="7"><?php _e('SCT XLarge');?> + </option>
                <?php } ?>
				<option value="6"><?php _e('Velocity');?> </option>
				</select>
			</td>
			<td>
                <label for="dmanga_TRS_L_Factor"></label>
                <input type="text" id="dmanga_TRS_L_Factor" name="TRS_L_Factor" size="4" value="0"/>
            </td>
			<td>
                <label for="dmanga_TRS_L_Unit"></label>
				<select id="dmanga_TRS_L_Unit" name="TRS_L_Unit">
				<option value="s" selected="selected"><?php _e('Secs');?>.</option>
				<option value="%">%</option>
				<option value="m">m/s</option>
				</select>
			</td>
            <td>
                <input type="text" id="dmanga_TRS_L_TimeSpeed" name="TRS_L_TimeSpeed" readonly="readonly" disabled="disabled" size="10" value=""/>
            </td>
			<!-- datos para TRM standard -->
			<td>
                <label for="dmanga_TRM_L_Tipo"></label>
				<select id="dmanga_TRM_L_Tipo" name="TRM_L_Tipo">
				<option value="0" selected="selected"><?php _e('Fixed MCT');?></option>
				<option value="1"><?php _e('SCT');?> + </option>
                <option value="6"><?php _e('Velocity');?> </option>
				</select>
			</td>
			<td>
                <label for="dmanga_TRM_L_Factor"></label>
                <input type="text" id="dmanga_TRM_L_Factor" name="TRM_L_Factor" size="4" value="0"/>
            </td>
			<td>
                <label for="dmanga_TRM_L_Unit"></label>
				<select id="dmanga_TRM_L_Unit" name="TRM_L_Unit" >
				<option value="s" selected="selected"><?php _e('Secs');?>.</option>
				<option value="%">%</option>
                <option value="m">m/s</option>
				</select>
			</td>
		</tr>

        <!-- fila 5: recorrido std / mini+midi datos midi -->
		<tr id="dmanga_MediumRow">
			<td id="dmanga_MediumLbl">Medium</td>
			<td>
                <label for="dmanga_DistM"></label>
                <input type="text" id="dmanga_DistM" name="Dist_M" size="4" value="0"/>
            </td>
			<td>
                <label for="dmanga_ObstM"></label>
                <input type="text" id="dmanga_ObstM" name="Obst_M" size="4" value="0"/>
            </td>
			<!-- datos para TRS medium -->
			<td>
                <label for="dmanga_TRS_M_Tipo"></label>
				<select id="dmanga_TRS_M_Tipo" name="TRS_M_Tipo">
				<option value="0" selected="selected"><?php _e('Fixed SCT');?></option>
				<option value="1"><?php _e('Best result');?> + </option>
				<option value="2"><?php _e('3 best average');?> + </option>
                <?php if ($heights==5) { ?>
                    <option value="7"><?php _e('SCT XLarge');?> + </option>
                <?php } ?>
				<option value="3"><?php _e('SCT Standard');?> + </option>
				<option value="6"><?php _e('Velocity');?> </option>
				</select>
			</td>
			<td>
                <label for="dmanga_TRS_M_Factor"></label>
                <input type="text" id="dmanga_TRS_M_Factor" name="TRS_M_Factor" size="4" value="0"/>
            </td>
			<td>
                <label for="dmanga_TRS_M_Unit"></label>
				<select id="dmanga_TRS_M_Unit" name="TRS_M_Unit">
				<option value="s"><?php _e('Secs');?>.</option>
				<option value="%">%</option>
				<option value="m">m/s</option>
				</select>
			</td>
            <td>
                <input type="text" id="dmanga_TRS_M_TimeSpeed" name="TRS_M_TimeSpeed" readonly="readonly" disabled="disabled" size="10" value=""/>
            </td>
			<!-- datos para TRM medium -->
			<td>
                <label for="dmanga_TRM_M_Tipo"></label>
				<select id="dmanga_TRM_M_Tipo" name="TRM_M_Tipo">
				<option value="0" selected="selected"><?php _e('Fixed MCT');?></option>
				<option value="1"><?php _e('SCT');?> + </option>
                <option value="6"><?php _e('Velocity');?> </option>
				</select>
			</td>
			<td>
                <label for="dmanga_TRM_M_Factor"></label>
                <input type="text" id="dmanga_TRM_M_Factor" name="TRM_M_Factor" size="4" value="0"/>
            </td>
			<td>
                <label for="dmanga_TRM_M_Unit"></label>
				<select id="dmanga_TRM_M_Unit" name="TRM_M_Unit">
				<option value="s" selected="selected"><?php _e('Secs');?>.</option>
				<option value="%">%</option>
                <option value="m">m/s</option>
				</select>
			</td>		
		</tr>

        <!-- fila 6: recorrido std / mini / midi + datos mini -->
		<tr id="dmanga_SmallRow">
			<td id="dmanga_SmallLbl">Small</td>
			<td>
                <label for="dmanga_DistS"></label>
                <input type="text" id="dmanga_DistS" name="Dist_S" size="4" value="0"/>
            </td>
			<td>
                <label for="dmanga_ObstS"></label>
                <input type="text" id="dmanga_ObstS" name="Obst_S" size="4" value="0"/>
            </td>
			<!-- datos para TRS small -->
			<td>
                <label for="dmanga_TRS_S_Tipo"></label>
				<select id="dmanga_TRS_S_Tipo" name="TRS_S_Tipo">
				<option value="0" selected="selected"><?php _e('Fixed SCT');?></option>
				<option value="1"><?php _e('Best result');?> + </option>
				<option value="2"><?php _e('3 best average');?> + </option>
                <?php if ($heights==5) { ?>
                    <option value="7"><?php _e('SCT XLarge');?> + </option>
                <?php } ?>
				<option value="3"><?php _e('SCT Standard');?> + </option>
				<option value="4"><?php _e('SCT Medium');?> + </option>
				<option value="6"><?php _e('Velocity');?> </option>
				</select>
			</td>
			<td>
                <label for="dmanga_TRS_S_Factor"></label>
                <input type="text" id="dmanga_TRS_S_Factor" name="TRS_S_Factor" size="4" value="0"/>
            </td>
			<td>
                <label for="dmanga_TRS_S_Unit"></label>
				<select id="dmanga_TRS_S_Unit" name="TRS_S_Unit">
				<option value="s"><?php _e('Secs');?>.</option>
				<option value="%">%</option>
				<option value="m">m/s</option>
				</select>
			</td>
            <td>
                <input type="text" id="dmanga_TRS_S_TimeSpeed" name="TRS_S_TimeSpeed" readonly="readonly" disabled="disabled" size="10" value=""/>
            </td>
			<!-- datos para TRM small -->
			<td>
                <label for="dmanga_TRM_S_Tipo"></label>
				<select id="dmanga_TRM_S_Tipo" name="TRM_S_Tipo">
				<option value="0" selected="selected"><?php _e('Fixed MCT');?></option>
				<option value="1"><?php _e('SCT');?> + </option>
                <option value="6"><?php _e('Velocity');?> </option>
				</select>
			</td>
			<td>
                <label for="dmanga_TRM_S_Factor"></label>
                <input type="text" id="dmanga_TRM_S_Factor" name="TRM_S_Factor" size="4" value="0"/>
            </td>
			<td>
                <label for="dmanga_TRM_S_Unit"></label>
				<select id="dmanga_TRM_S_Unit" name="TRM_S_Unit">
				<option value="s" selected="selected"><?php _e('Secs');?>.</option>
				<option value="%">%</option>
                <option value="m">m/s</option>
				</select>
			</td>
		</tr>

        <!-- fila 7: recorrido std / mini / midi / tiny datos tiny -->
		<tr id="dmanga_TinyRow">
			<td id="dmanga_TinyLbl">Tiny</td>
			<td>
                <label for="dmanga_DistT"></label>
                <input type="text" id="dmanga_DistT" name="Dist_T" size="4" value="0"/>
            </td>
			<td>
                <label for="dmanga_ObstT"></label>
                <input type="text" id="dmanga_ObstT" name="Obst_T" size="4" value="0"/>
            </td>
			<!-- datos para TRS tiny -->
			<td>
                <label for="dmanga_TRS_T_Tipo"></label>
				<select id="dmanga_TRS_T_Tipo" name="TRS_T_Tipo">
				<option value="0" selected="selected"><?php _e('Fixed SCT'); ?></option>
				<option value="1"><?php _e('Best result'); ?> + </option>
				<option value="2"><?php _e('3 best average'); ?> + </option>
                <?php if ($heights==5) { ?>
                    <option value="7"><?php _e('SCT XLarge');?> + </option>
                <?php } ?>
				<option value="3"><?php _e('SCT Standard'); ?> + </option>
                <option value="4"><?php _e('SCT Medium'); ?> + </option>
                <option value="5"><?php _e('SCT Small'); ?> + </option>
				<option value="6"><?php _e('Velocity'); ?> </option>
				</select>
			</td>
			<td>
                <label for="dmanga_TRS_T_Factor"></label>
                <input type="text" id="dmanga_TRS_T_Factor" name="TRS_T_Factor" size="4" value="0"/>
            </td>
			<td>
                <label for="dmanga_TRS_T_Unit"></label>
				<select id="dmanga_TRS_T_Unit" name="TRS_T_Unit">
				<option value="s"><?php _e('Secs'); ?>.</option>
				<option value="%">%</option>
				<option value="m">m/s</option>
				</select>
			</td>
            <td>
                <input type="text" id="dmanga_TRS_T_TimeSpeed" name="TRS_T_TimeSpeed" readonly="readonly" disabled="disabled" size="10" value=""/>
            </td>
			<!-- datos para TRM tiny -->
			<td>
                <label for="dmanga_TRM_T_Tipo"></label>
				<select id="dmanga_TRM_T_Tipo" name="TRM_T_Tipo">
				<option value="0" selected="selected"><?php _e('Fixed MCT'); ?></option>
				<option value="1"><?php _e('SCT'); ?> + </option>
                <option value="6"><?php _e('Velocity');?> </option>
				</select>
			</td>
			<td>
                <label for="dmanga_TRM_T_Factor"></label>
                <input type="text" id="dmanga_TRM_T_Factor" name="TRM_T_Factor" size="4" value="0"/>
            </td>
			<td>
                <label for="dmanga_TRM_T_Unit"></label>
				<select id="dmanga_TRM_T_Unit" name="TRM_T_Unit">
				<option value="s" selected="selected"><?php _e('Secs'); ?>.</option>
				<option value="%">%</option>
                <option value="m">m/s</option>
				</select>
			</td>
		</tr>

        <!-- fila 8: observaciones JAMC Agosto 2020 ahora se usa para indicar si grado 1 es agility o jumping -->
		<tr>
            <td colspan="10">&nbsp;</td>
            <!--
			<td colspan="2"><label for="dmanga_Observaciones"><?php _e('Comments'); ?></label></td>
			<td colspan="8"><input type="text" id="dmanga_Observaciones" name="Observaciones" size="75" value=""/></td>
			-->
		</tr>
		<tr> <!-- fila 7: botones reset y save -->
            <td>
                <a href="#" class="easyui-linkbutton" data-options="iconCls:'icon-print'"
                   id="dmanga_Templates" onclick="print_commonDesarrollo(3);"><?php _e('Templates'); ?></a>
            </td>
			<td colspan="1">&nbsp;</td>
            <td>
                <a href="#" class="easyui-linkbutton" data-options="iconCls:'icon-edit'"
                   id="dmanga_Inscripciones" onclick="open_inscripciones();"><?php _e('Inscriptions'); ?></a>
            </td>
            <td>
                <a href="#" class="easyui-linkbutton" data-options="iconCls:'icon-flag'"
                   id="dmanga_Clasificaciones" onclick="open_clasificaciones();"><?php _e('Scores'); ?></a>
            </td>
            <td colspan="2">&nbsp;</td>
			<td align="center">
				<a href="#" class="easyui-linkbutton" data-options="iconCls:'icon-reload'" 
					id="dmanga_Restaurar" onclick="reload_manga(workingData.manga);"><?php _e('Restore'); ?></a>
			</td>
			<td colspan="1">&nbsp;</td>
			<td colspan="2" align="left">
				<a href="#" class="easyui-linkbutton" data-options="iconCls:'icon-save'" 
					id="dmanga_Guardar" onclick="save_manga(workingData.manga);"><?php _e('Save'); ?></a>
			</td>
		</tr>
	</table>
</form>

<p>
	<span id="infomanga_readonly" class="blink" style="display:none;color:#ff0000;text-align:center;font-size:17px">
		<?php _e('Current user has NO WRITE PERMISSIONS');?>
	</span>
    <span id="infomanga_closed" class="blink" style="display:none;color:#ff0000;text-align:center;font-size:17px">
		<?php _e('Journey closed. CANNOT ADD/MODIFY DATA');?>
	</span>
    <span id="infomanga_description" style="display:inline-block;font-size:1.1vw;text-align:center;width:50%">
        <br/><span id="infomanga_tipo"></span> - <span id="infomanga_alturas"></span>
    </span>
</p>
<script type="text/javascript">
    var myKeyHandler = $.extend({},$.fn.combobox.defaults.keyHandler,{
        down:function(q){
            if( $(this).combobox('panel').panel('options').closed===true ) {
                $(this).combobox('showPanel');
            } else {
                $.fn.combobox.defaults.keyHandler.down.call(this,q);
            }
        }
    });

    function mark_modified() { workingData.datosManga.modified=1; }

    $('#dmanga_grado1_other_value').textbox({
        onChange: function(newval,oldval) {
            var rb=$('#dmanga_grado1_other');
            rb.prop('checked',true);
            rb.val(newval);
        }
    });
    //stupid easyui that does not parse from markup
    $('#dmanga_DistX').textbox({onChange:function(n,o){mark_modified();dmanga_setRecorridos();}});
    $('#dmanga_DistL').textbox({onChange:function(n,o){mark_modified();dmanga_setRecorridos();}});
    $('#dmanga_DistM').textbox({onChange:function(n,o){mark_modified();dmanga_setRecorridos();}});
    $('#dmanga_DistS').textbox({onChange:function(n,o){mark_modified();dmanga_setRecorridos();}});
    $('#dmanga_DistT').textbox({onChange:function(n,o){mark_modified();dmanga_setRecorridos();}});
    $('#dmanga_ObstX').textbox({onChange:function(n,o){mark_modified();dmanga_setRecorridos();}});
    $('#dmanga_ObstL').textbox({onChange:function(n,o){mark_modified();dmanga_setRecorridos();}});
    $('#dmanga_ObstM').textbox({onChange:function(n,o){mark_modified();dmanga_setRecorridos();}});
    $('#dmanga_ObstS').textbox({onChange:function(n,o){mark_modified();dmanga_setRecorridos();}});
    $('#dmanga_ObstT').textbox({onChange:function(n,o){mark_modified();dmanga_setRecorridos();}});
    $('#dmanga_TRS_X_Tipo').combobox({valueField:'value',editable:false,keyHandler:myKeyHandler,panelHeight:'auto',panelWidth:130,onChange:function(n,o){mark_modified();round_setUnit(n,'#dmanga_TRS_X_Unit')}});
    $('#dmanga_TRS_L_Tipo').combobox({valueField:'value',editable:false,keyHandler:myKeyHandler,panelHeight:'auto',panelWidth:130,onChange:function(n,o){mark_modified();round_setUnit(n,'#dmanga_TRS_L_Unit')}});
    $('#dmanga_TRS_M_Tipo').combobox({valueField:'value',editable:false,keyHandler:myKeyHandler,panelHeight:'auto',panelWidth:130,onChange:function(n,o){mark_modified();round_setUnit(n,'#dmanga_TRS_M_Unit')}});
    $('#dmanga_TRS_S_Tipo').combobox({valueField:'value',editable:false,keyHandler:myKeyHandler,panelHeight:'auto',panelWidth:130,onChange:function(n,o){mark_modified();round_setUnit(n,'#dmanga_TRS_S_Unit')}});
    $('#dmanga_TRS_T_Tipo').combobox({valueField:'value',editable:false,keyHandler:myKeyHandler,panelHeight:'auto',panelWidth:130,onChange:function(n,o){mark_modified();round_setUnit(n,'#dmanga_TRS_T_Unit')}});
    $('#dmanga_TRS_X_Factor').textbox({onChange:function(n,o){mark_modified();dmanga_evalTimeSpeed();}});
    $('#dmanga_TRS_L_Factor').textbox({onChange:function(n,o){mark_modified();dmanga_evalTimeSpeed();}});
    $('#dmanga_TRS_M_Factor').textbox({onChange:function(n,o){mark_modified();dmanga_evalTimeSpeed();}});
    $('#dmanga_TRS_S_Factor').textbox({onChange:function(n,o){mark_modified();dmanga_evalTimeSpeed();}});
    $('#dmanga_TRS_T_Factor').textbox({onChange:function(n,o){mark_modified();dmanga_evalTimeSpeed();}});
    $('#dmanga_TRS_X_Unit').combobox({valueField:'value',editable:false,keyHandler:myKeyHandler,panelHeight:'auto',onChange:function(n,o){mark_modified();round_setMode(n,'#dmanga_TRS_X_Tipo')}});
    $('#dmanga_TRS_L_Unit').combobox({valueField:'value',editable:false,keyHandler:myKeyHandler,panelHeight:'auto',onChange:function(n,o){mark_modified();round_setMode(n,'#dmanga_TRS_L_Tipo')}});
    $('#dmanga_TRS_M_Unit').combobox({valueField:'value',editable:false,keyHandler:myKeyHandler,panelHeight:'auto',onChange:function(n,o){mark_modified();round_setMode(n,'#dmanga_TRS_M_Tipo')}});
    $('#dmanga_TRS_S_Unit').combobox({valueField:'value',editable:false,keyHandler:myKeyHandler,panelHeight:'auto',onChange:function(n,o){mark_modified();round_setMode(n,'#dmanga_TRS_S_Tipo')}});
    $('#dmanga_TRS_T_Unit').combobox({valueField:'value',editable:false,keyHandler:myKeyHandler,panelHeight:'auto',onChange:function(n,o){mark_modified();round_setMode(n,'#dmanga_TRS_T_Tipo')}});
    $('#dmanga_TRS_X_TimeSpeed').textbox({onChange:function(n,o){mark_modified();}});
    $('#dmanga_TRS_L_TimeSpeed').textbox({onChange:function(n,o){mark_modified();}});
    $('#dmanga_TRS_M_TimeSpeed').textbox({onChange:function(n,o){mark_modified();}});
    $('#dmanga_TRS_S_TimeSpeed').textbox({onChange:function(n,o){mark_modified();}});
    $('#dmanga_TRS_T_TimeSpeed').textbox({onChange:function(n,o){mark_modified();}});
    $('#dmanga_TRM_X_Tipo').combobox({valueField:'value',editable:false,keyHandler:myKeyHandler,panelHeight:'auto',onChange:function(n,o){mark_modified();round_setUnit(n,'#dmanga_TRM_X_Unit')}});
    $('#dmanga_TRM_L_Tipo').combobox({valueField:'value',editable:false,keyHandler:myKeyHandler,panelHeight:'auto',onChange:function(n,o){mark_modified();round_setUnit(n,'#dmanga_TRM_L_Unit')}});
    $('#dmanga_TRM_M_Tipo').combobox({valueField:'value',editable:false,keyHandler:myKeyHandler,panelHeight:'auto',onChange:function(n,o){mark_modified();round_setUnit(n,'#dmanga_TRM_M_Unit')}});
    $('#dmanga_TRM_S_Tipo').combobox({valueField:'value',editable:false,keyHandler:myKeyHandler,panelHeight:'auto',onChange:function(n,o){mark_modified();round_setUnit(n,'#dmanga_TRM_S_Unit')}});
    $('#dmanga_TRM_T_Tipo').combobox({valueField:'value',editable:false,keyHandler:myKeyHandler,panelHeight:'auto',onChange:function(n,o){mark_modified();round_setUnit(n,'#dmanga_TRM_T_Unit')}});
    $('#dmanga_TRM_X_Factor').textbox({onChange:function(n,o){mark_modified();}});
    $('#dmanga_TRM_L_Factor').textbox({onChange:function(n,o){mark_modified();}});
    $('#dmanga_TRM_M_Factor').textbox({onChange:function(n,o){mark_modified();}});
    $('#dmanga_TRM_S_Factor').textbox({onChange:function(n,o){mark_modified();}});
    $('#dmanga_TRM_T_Factor').textbox({onChange:function(n,o){mark_modified();}});
    $('#dmanga_TRM_X_Unit').combobox({valueField:'value',editable:false,keyHandler:myKeyHandler,panelHeight:'auto',onChange:function(n,o){mark_modified();round_setMode(n,'#dmanga_TRM_X_Tipo')}});
    $('#dmanga_TRM_L_Unit').combobox({valueField:'value',editable:false,keyHandler:myKeyHandler,panelHeight:'auto',onChange:function(n,o){mark_modified();round_setMode(n,'#dmanga_TRM_L_Tipo')}});
    $('#dmanga_TRM_M_Unit').combobox({valueField:'value',editable:false,keyHandler:myKeyHandler,panelHeight:'auto',onChange:function(n,o){mark_modified();round_setMode(n,'#dmanga_TRM_M_Tipo')}});
    $('#dmanga_TRM_S_Unit').combobox({valueField:'value',editable:false,keyHandler:myKeyHandler,panelHeight:'auto',onChange:function(n,o){mark_modified();round_setMode(n,'#dmanga_TRM_S_Tipo')}});
    $('#dmanga_TRM_T_Unit').combobox({valueField:'value',editable:false,keyHandler:myKeyHandler,panelHeight:'auto',onChange:function(n,o){mark_modified();round_setMode(n,'#dmanga_TRM_T_Tipo')}});
    $('#dmanga_Observaciones').textbox({onChange:function(n,o){mark_modified();}});


$('#dmanga_Juez1').combogrid({
	panelWidth: 400,
	panelHeight: 150,
	idField: 'ID',
	textField: 'Nombre',
	url: '../ajax/database/juezFunctions.php',
	queryParams: {
		Operation: 'enumerate',
		Federation: workingData.federation
	},
	method: 'get',
	mode: 'remote',
	required: false,
	columns: [[
	    {field:'ID', hidden:true},
        {field:'Nombre',title:"<?php _e('Judge name'); ?>",width:70,align:'left'},
        {field:'Internacional',title:"<?php _e('Intl'); ?>",width:10,align:'center',formatter:juecesInternacional},
        {field:'Practicas',title:"<?php _e('Pract'); ?>",width:10,align:'center',formatter:juecesPracticas},
		{field:'Email',title:"<?php _e('E-mail'); ?>",width:50,align:'right'}
    ]],
	multiple: false,
	fitColumns: true,
	selectOnNavigation: false,
    onChange: function(newval,oldval) {
        mark_modified();
        let valid= $.isNumeric(newval) && (parseInt(newval)>=2); // in juez1 "-- Sin asignar --" is not valid
        $('#dmanga_Juez1').combogrid('textbox').css('background',(valid)?'white':'#ffcccc');
    }
});

$('#dmanga_Juez2').combogrid({
	panelWidth: 400,
	panelHeight: 150,
	idField: 'ID',
	textField: 'Nombre',
	url: '../ajax/database/juezFunctions.php',
	queryParams: {
		Operation: 'enumerate',
		Federation: workingData.federation
	},
	method: 'get',
	mode: 'remote',
	required: false,
	columns: [[
	   	{field:'ID', hidden:true},
		{field:'Nombre',title:"<?php _e('Judge name'); ?>",width:70,align:'left'},
        {field:'Internacional',title:"<?php _e('Intl'); ?>",width:10,align:'center',formatter:juecesInternacional},
        {field:'Practicas',title:"<?php _e('Pract'); ?>",width:10,align:'center',formatter:juecesPracticas},
		{field:'Email',title:"<?php _e('E-mail'); ?>",width:50,align:'right'}
    ]],
	multiple: false,
	fitColumns: true,
	selectOnNavigation: false,
    onChange: function(newval,oldval) {
        mark_modified();
        let valid= $.isNumeric(newval) && (parseInt(newval)>=1); // in juez2 "-- Sin asignar --" is valid
        $('#dmanga_Juez2').combogrid('textbox').css('background',(valid)?'white':'#ffcccc');
    }
});

$('#competicion-formdatosmanga').form({
	onLoadSuccess: function(data) {
		// fix appearance according mode, federation, recorrido and so
        dmanga_setAgilityOrJumping(data); // JAMC Agosto 2020
		dmanga_setRecorridos();
		workingData.datosManga.modified=0;
	},
	onLoadError: function() { alert("<?php _e('Error loading round information'); ?>"); }
});

//tooltips
addTooltip($('#dmanga_Juez1').combogrid('textbox'),'<?php _e("Main judge data"); ?>');
addTooltip($('#dmanga_Juez2').combogrid('textbox'),'<?php _e("Auxiliar/Practice judge data"); ?>');
<?php
    $ttr1="";
    $ttr3="";
    if ($heights==3) {
        $ttr1=_("Separate courses Standard and Midi/mini");
    }
    if ($heights==4) {
        $ttr1=_("Separate courses Standard/Medium and Small/Tiny");
    }
    if ($heights==5) {
        $ttr1=_("2 courses: XLarge/Large and Medium/Small/Toy");
        $ttr3=_("3 courses: XLarge/Large, Medium and Small/Toy");
    }
?>
addTooltip($('#dmanga_Recorrido_0'),'<?php _e("Same course for every categories"); ?>');
addTooltip($('#dmanga_Recorrido_1'),'<?php echo $ttr1; ?>');
addTooltip($('#dmanga_Recorrido_2'),'<?php _e("Independent courses for all categories"); ?>');
addTooltip($('#dmanga_Recorrido_3'),'<?php echo $ttr3; ?>');
addTooltip($('#dmanga_Restaurar').linkbutton(),'<?php _e("Restore original round info from database"); ?>');
addTooltip($('#dmanga_Templates').linkbutton(),'<?php _e("Open print form selection dialog"); ?>');
addTooltip($('#dmanga_Inscripciones').linkbutton(),'<?php _e("Jump to Inscriptions window"); ?>');
addTooltip($('#dmanga_Clasificaciones').linkbutton(),'<?php _e("Jump to Result and Scores window"); ?>');
addTooltip($('#dmanga_Guardar').linkbutton(),'<?php _e("Save round technical data into database"); ?>');
addTooltip($('#dmanga_AddJuez').linkbutton(),'<?php _e("Add a new judge into database"); ?>');
addTooltip($('#dmanga_SameJuez').linkbutton(),'<?php _e("Clone judge information on every rounds for this journey"); ?>');

// if user has no write permission, show proper message info
// TODO: force reload on logout session
$('#infomanga_readonly').css('display',(check_softLevel(access_level.PERMS_OPERATOR,null))?'none':'inline-block');
$('#infomanga_closed').css('display',(parseInt(workingData.datosJornada.Cerrada)===0)?'none':'inline-block');
$('#infomanga_tipo').html(workingData.datosCompeticion.Nombre);
$('#infomanga_alturas').html('<?php echo $heights ." ". _("Heights");?>');
</script>