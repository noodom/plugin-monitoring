<?php

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

/* * ***************************Includes********************************* */
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

class monitoring extends eqLogic {
	/*     * *************************Attributs****************************** */

	public static $_widgetPossibility = array('custom' => true);
	public static $_motors = array(
		'cli' => array('name' => 'Bash'),
		'snmp' => array('name' => 'SNMP'),
		'ping' => array('name' => 'Ping'),
		'url' => array('name' => 'URL'),
	);

	/*     * ***********************Methode static*************************** */

	public static function dependancy_info() {
		$return = array();
		$return['progress_file'] = jeedom::getTmpFolder('monitoring') . '/dependance';
		if (exec(system::getCmdSudo() . system::get('cmd_check') . '-E "php5\-snmp" | wc -l') >= 1) {
			$return['state'] = 'ok';
		} else {
			$return['state'] = 'nok';
		}
		return $return;
	}
	public static function dependancy_install() {
		log::remove(__CLASS__ . '_update');
		return array('script' => dirname(__FILE__) . '/../../resources/install_#stype#.sh ' . jeedom::getTmpFolder('monitoring') . '/dependance', 'log' => log::getPathToLog(__CLASS__ . '_update'));
	}

	public static function update($_eqLogic_id = null) {
		if ($_eqLogic_id == null) {
			$eqLogics = eqLogic::byType('monitoring');
		} else {
			$eqLogics = array(eqLogic::byId($_eqLogic_id));
		}
		foreach ($eqLogics as $monitoring) {
			$autorefresh = $monitoring->getConfiguration('autorefresh', '*/15 * * * *');
			if ($autorefresh != '') {
				try {
					$c = new Cron\CronExpression($autorefresh, new Cron\FieldFactory);
					if ($c->isDue()) {
						try {
							$monitoring->updateSysInfo();
						} catch (Exception $e) {
							log::add('monitoring', 'error', $e->getMessage());
						}
					}
				} catch (Exception $exc) {
					log::add('monitoring', 'error', __('Expression cron non valide pour ', __FILE__) . $eqLogic->getHumanName() . ' : ' . $autorefresh);
				}
			}
		}
	}

	/*     * *********************Methode d'instance************************* */

	public function preSave() {
		if ($this->getConfiguration('autorefresh') == '') {
			$this->setConfiguration('autorefresh', '*/15 * * * *');
		}
	}

	public function postSave() {
		$refresh = $this->getCmd(null, 'refresh');
		if (!is_object($refresh)) {
			$refresh = new monitoringCmd();
		}
		$refresh->setName(__('Rafraîchir', __FILE__));
		$refresh->setEqLogic_id($this->getId());
		$refresh->setLogicalId('refresh');
		$refresh->setType('action');
		$refresh->setSubType('other');
		$refresh->save();
		if ($this->getIsEnable()) {
			$this->updateSysInfo();
		}
	}

	public function updateSysInfo() {
		$e = null;
		foreach (self::$_motors as $motor => $value) {
			try {
				if ($this->getConfiguration($motor) == 1) {
					$class = 'monitoring_' . $motor;
					$monitor = new $class($this);
					$monitor->update();
				}
			} catch (Exception $e) {

			}
		}
		if ($e !== null) {
			throw $e;
		}
	}
}

class monitoringCmd extends cmd {

/*     * *************************Attributs****************************** */

/*     * *********************Methode d'instance************************* */

	public function imperihomeGenerate($ISSStructure) {
		$eqLogic = $this->getEqLogic();
		$object = $eqLogic->getObject();
		$info_device = array(
			"id" => $this->getId(),
			"name" => $eqLogic->getName() . ' - ' . $this->getName(),
			"room" => (is_object($object)) ? $object->getId() : 99999,
			"type" => imperihome::convertType($this, $ISSStructure, true),
			'params' => array(),
		);
		$cmd_params = imperihome::generateParam($this, $info_device['type'], $ISSStructure);
		$info_device['params'] = $cmd_params['params'];
		return $info_device;

	}

	public function execute($_options = null) {
		$eqLogic = $this->getEqLogic();
		if ($this->getLogicalId() == 'refresh') {
			$this->getEqLogic()->updateSysInfo();
		} else if ($this->type == 'action') {
			$eqLogic->cli_execCmd($this->getConfiguration('usercmd'));
		}
		return true;
	}
}

?>
