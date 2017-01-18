<?php
if (!isConnect('admin')) {
	throw new Exception('{{401 - Accès non autorisé}}');
}
$plugin = plugin::byId('monitoring');
sendVarToJS('eqType', $plugin->getId());
sendVarToJS('monitoring_motors', monitoring::$_motors);
$eqLogics = eqLogic::byType($plugin->getId());
?>

<div class="row row-overflow">
	<div class="col-lg-2">
		<div class="bs-sidebar">
			<ul id="ul_eqLogic" class="nav nav-list bs-sidenav">
				<a class="btn btn-default eqLogicAction" style="width : 100%;margin-top : 5px;margin-bottom: 5px;" data-action="add"><i class="fa fa-plus-circle"></i> {{Ajouter un Monitoring}}</a>
				<li class="filter" style="margin-bottom: 5px;"><input class="filter form-control input-sm" placeholder="{{Rechercher}}" style="width: 100%"/></li>
				<?php
foreach ($eqLogics as $eqLogic) {
	echo '<li class="cursor li_eqLogic" data-eqLogic_id="' . $eqLogic->getId() . '"><a>' . $eqLogic->getHumanName(true) . '</a></li>';
}
?>
			</ul>
		</div>
	</div>
	<div class="col-lg-10 col-md-9 col-sm-8 eqLogicThumbnailDisplay" style="border-left: solid 1px #EEE; padding-left: 25px;">
		<legend><i class="fa fa-cog"></i>  {{Gestion}}</legend>
		<div class="eqLogicThumbnailContainer">
			<div class="cursor eqLogicAction" data-action="add" style="background-color : #ffffff; height : 120px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;" >
				<center>
					<i class="fa fa-plus-circle" style="font-size : 6em;color:#94ca02;"></i>
				</center>
				<span style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;color:#94ca02"><center>Ajouter</center></span>
			</div>
			<div class="cursor eqLogicAction" data-action="gotoPluginConf" style="background-color : #ffffff; height : 120px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;">
				<center>
					<i class="fa fa-wrench" style="font-size : 6em;color:#767676;"></i>
				</center>
				<span style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;color:#767676"><center>{{Configuration}}</center></span>
			</div>
		</div>
		<legend><i class="fa fa-table"></i> {{Mes équipements}}</legend>
		<div class="eqLogicThumbnailContainer">
			<?php
foreach ($eqLogics as $eqLogic) {
	echo '<div class="eqLogicDisplayCard cursor" data-eqLogic_id="' . $eqLogic->getId() . '" style="background-color : #ffffff; height : 200px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;" >';
	echo "<center>";
	echo '<img src="' . $plugin->getPathImgIcon() . '" height="105" width="95" />';
	echo "</center>";
	echo '<span style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;"><center>' . $eqLogic->getHumanName(true, true) . '</center></span>';
	echo '</div>';
}
?>
		</div>
	</div>
	<div class="col-lg-10 eqLogic" style="border-left: solid 1px #EEE; padding-left: 25px;display: none;">
		<a class="btn btn-success eqLogicAction pull-right" data-action="save"><i class="fa fa-check-circle"></i> {{Sauvegarder}}</a>
		<a class="btn btn-danger eqLogicAction pull-right" data-action="remove"><i class="fa fa-minus-circle"></i> {{Supprimer}}</a>
		<a class="btn btn-default eqLogicAction pull-right" data-action="configure"><i class="fa fa-cogs"></i> {{Configuration avancée}}</a>
		<ul class="nav nav-tabs" role="tablist">
			<li role="presentation"><a href="#" class="eqLogicAction" aria-controls="home" role="tab" data-toggle="tab" data-action="returnToThumbnailDisplay"><i class="fa fa-arrow-circle-left"></i></a></li>
			<li role="presentation" class="active"><a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab"><i class="fa fa-tachometer"></i> {{Equipement}}</a></li>
			<li role="presentation"><a href="#commandtab" aria-controls="profile" role="tab" data-toggle="tab"><i class="fa fa-list-alt"></i> {{Commandes}}</a></li>
		</ul>
		<div class="tab-content" style="height:calc(100% - 50px);overflow:auto;overflow-x: hidden;">
			<div role="tabpanel" class="tab-pane active" id="eqlogictab">
				<br/>
				<form class="form-horizontal">
					<fieldset>
						<div class="form-group">
							<label class="col-sm-2 control-label">{{Nom de l'équipement}}</label>
							<div class="col-sm-3">
								<input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display : none;" />
								<input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de l'équipement}}"/>
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-2 control-label" >{{Objet parent}}</label>
							<div class="col-sm-3">
								<select id="sel_object" class="eqLogicAttr form-control" data-l1key="object_id">
									<option value="">{{Aucun}}</option>
									<?php
foreach (object::all() as $object) {
	echo '<option value="' . $object->getId() . '">' . $object->getName() . '</option>';
}
?>
								</select>
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-2 control-label">{{Catégorie}}</label>
							<div class="col-sm-4">
								<?php
foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value) {
	echo '<label class="checkbox-inline">';
	echo '<input type="checkbox" class="eqLogicAttr" data-l1key="category" data-l2key="' . $key . '" />' . $value['name'];
	echo '</label>';
}
?>
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-2 control-label"></label>
							<div class="col-sm-9">
								<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked/>{{Activer}}</label>
								<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" checked/>{{Visible}}</label>
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-2 control-label">{{Fréquence de mise à jour}}</label>
							<div class="col-sm-2">
								<input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="autorefresh" />
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-2 control-label">{{Moteur}}</label>
							<div class="col-sm-10">
							<?php
foreach (monitoring::$_motors as $motor => $value) {
	echo '<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr motor_enable" data-l1key="configuration" data-l2key="' . $motor . '"/>' . $value['name'] . '</label>';
}

?>
							</div>
						</div>
						<div class="motor_config cli" style="display:none;">
							<legend>{{Paramètres Bash/Shell}}</legend>
							<div class="form-group">
								<label class="col-sm-2 control-label">{{Mode}}</label>
								<div class="col-sm-2">
									<select class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="cli::mode">
										<option value="local">{{Local}}</option>
										<option value="ssh">{{SSH}}</option>
									</select>
								</div>
							</div>
							<div class="cli_mode cli_ssh">
								<div class="form-group">
									<label class="col-sm-2 control-label">{{IP}}</label>
									<div class="col-sm-2">
										<input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="ssh::ip" />
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-2 control-label">{{Nom d'utilisateur}}</label>
									<div class="col-sm-2">
										<input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="ssh::username" />
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-2 control-label">{{Mot de passe}}</label>
									<div class="col-sm-2">
										<input type="password" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="ssh::password" />
									</div>
								</div>
							</div>
						</div>

						<div class="motor_config snmp" style="display:none;">
							<legend>{{Paramètres SNMP}}</legend>
							<div class="form-group">
								<label class="col-sm-2 control-label">{{IP}}</label>
								<div class="col-sm-2">
									<input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="snmp::ip" />
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-2 control-label">{{Protocole}}</label>
								<div class="col-sm-2">
									<select class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="snmp::protocole" >
										<option value="1">1</option>
										<option value="2">2</option>
										<option value="3">3</option>
									</select>
								</div>
							</div>
							<div class="snmp_protocole snmp_1 snmp_2">
								<div class="form-group">
									<label class="col-sm-2 control-label">{{Communauté}}</label>
									<div class="col-sm-2">
										<input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="snmp::community" />
									</div>
								</div>
							</div>
							<div class="snmp_protocole snmp_3">
								<div class="form-group">
									<label class="col-sm-2 control-label">{{Nom d'utilisateur}}</label>
									<div class="col-sm-2">
										<input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="snmp::username" />
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-2 control-label">{{Mot de passe}}</label>
									<div class="col-sm-2">
										<input type="password" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="snmp::password" />
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-2 control-label">{{Authentification mode}}</label>
									<div class="col-sm-2">
										<select class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="snmp::authmode" >
											<option value="MD5">MD5</option>
											<option value="SHA">SHA</option>
										</select>
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-2 control-label">{{Sécurité}}</label>
									<div class="col-sm-2">
										<select class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="snmp::security" >
											<option value="noAuthNoPriv">noAuthNoPriv</option>
											<option value="authNoPriv">authNoPriv</option>
											<option value="authPriv">authPriv</option>
										</select>
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-2 control-label">{{Priv protocole}}</label>
									<div class="col-sm-2">
										<select class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="snmp::privprotocole" >
											<option value="AES">AES</option>
											<option value="DES">DES</option>
										</select>
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-2 control-label">{{Priv passphrase}}</label>
									<div class="col-sm-2">
										<input type="password" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="snmp::privpassphrase" />
									</div>
								</div>
							</div>
						</div>
						<div class="motor_config ping" style="display:none;">
							<legend>{{Paramètres ping}}</legend>
							<div class="form-group">
								<label class="col-sm-2 control-label">{{IP}}</label>
								<div class="col-sm-2">
									<input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="ping::ip" />
								</div>
							</div>
						</div>

					</fieldset>
				</form>
			</div>
			<div role="tabpanel" class="tab-pane" id="commandtab">
				<a class="btn btn-success btn-sm cmdAction pull-right" data-action="add" style="margin-top:5px;"><i class="fa fa-plus-circle"></i> {{Ajouter une commande}}</a><br/><br/>
				<table id="table_cmd" class="table table-bordered table-condensed">
					<thead>
						<tr>
							<th style="width:250px;">{{Nom}}</th>
							<th>{{Paramètres}}</th>
							<th>{{Commande}}</th>
							<th>{{Options}}</th>
							<th>{{Parameters}}</th>
							<th style="width:200px;">{{Action}}</th>
						</tr>
					</thead>
					<tbody>

					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>

<?php include_file('desktop', 'monitoring', 'js', 'monitoring');?>
<?php include_file('core', 'plugin.template', 'js');?>
