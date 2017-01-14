
/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

 $('.eqLogicAttr.motor_enable').on('change click',function(){
 	$('.eqLogicAttr.motor_enable').each(function(){
 		var type=$(this).attr('data-l2key');
 		if($(this).value() == 1){
 			$('.motor_config.'+type).show();
 		}else{
 			$('.motor_config.'+type).hide();
 		}
 	});
 });

 $('.eqLogicAttr[data-l1key=configuration][data-l2key="cli::mode"]').on('change',function(){
 	$('.cli_mode').hide();
 	$('.cli_'+$(this).value()).show();
 });

 $('.eqLogicAttr[data-l1key=configuration][data-l2key="snmp::protocole"]').on('change',function(){
 	$('.snmp_protocole').hide();
 	$('.snmp_'+$(this).value()).show();
 });

 function addCmdToTable(_cmd) {
 	if (!isset(_cmd)) {
 		var _cmd = {configuration: {}};
 	}
 	if(!isset(_cmd.logicalId)){
 		_cmd.logicalId = 'usercmd';
 	}
 	var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';
 	tr += '<td>';
 	tr += '<span class="cmdAttr" data-l1key="id" style="display:none;"></span>';
 	tr += '<input class="cmdAttr form-control input-sm" data-l1key="name"">';
 	tr += '<div>';
 	tr += '<a class="cmdAction btn btn-default btn-sm" data-l1key="chooseIcon"><i class="fa fa-flag"></i> Icone</a>';
 	tr += '<span class="cmdAttr" data-l1key="display" data-l2key="icon" style="margin-left : 10px;"></span>';
 	tr += '</div>';
 	tr += '</td>'; 
 	tr += '<td>';
 	if(_cmd.logicalId == 'usercmd'){
 		tr += '<select class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="motor">';
 		tr += '<option value="cli">{{Bash/Shell}}</option>';
 		tr += '<option value="snmp">{{SNMP}}</option>';
 		tr += '</select>';
 		tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="usercmd" placeholder="{{Commande}}" style="margin-top:5px;" />';
 	}
 	tr += '</td>';
 	tr += '<td class="expertModeVisible">';
 	tr += '<span class="type" type="' + init(_cmd.type) + '">' + jeedom.cmd.availableType() + '</span>';
 	tr += '<span class="subType" subType="' + init(_cmd.subType) + '"></span>';
 	tr += '</td>';
 	 tr += '<td>';
    tr += '<input class="cmdAttr form-control input-sm" data-l1key="unite" placeholder="Unité" title="{{Unité}}">';
    tr += '<input class="cmdAttr form-control input-sm expertModeVisible" data-l1key="configuration" data-l2key="minValue" placeholder="{{Min}}" title="{{Min}}" style="margin-top : 5px;"> ';
    tr += '<input class="cmdAttr form-control input-sm expertModeVisible" data-l1key="configuration" data-l2key="maxValue" placeholder="{{Max}}" title="{{Max}}" style="margin-top : 5px;">';
    tr += '</td>';
 	tr += '<td style="width: 150px;">';
 	tr += '<span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isVisible" checked/>{{Afficher}}</label></span> ';
 	tr += '<span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isHistorized" checked/>{{Historiser}}</label></span> ';
 	tr += '</td>';
 	tr += '<td>';
 	if (is_numeric(_cmd.id)) {
 		tr += '<a class="btn btn-default btn-xs cmdAction expertModeVisible" data-action="configure"><i class="fa fa-cogs"></i></a> ';
 		tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fa fa-rss"></i> {{Tester}}</a>';
 	}
 	tr += '<i class="fa fa-minus-circle pull-right cmdAction cursor" data-action="remove"></i></td>';
 	tr += '</td>';
 	tr += '</tr>';
 	$('#table_cmd tbody').append(tr);
 	var tr = $('#table_cmd tbody tr:last');
 	tr.setValues(_cmd, '.cmdAttr');
 	jeedom.cmd.changeType(tr, init(_cmd.subType));
 }




 $("#table_cmd").sortable({axis: "y", cursor: "move", items: ".cmd", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true});
