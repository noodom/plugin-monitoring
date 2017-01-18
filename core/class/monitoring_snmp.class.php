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

class monitoring_snmp {
	/*     * *************************Attributs****************************** */

	private $cache = array();
	private $eqLogic;

	/*     * ***********************Methode static*************************** */

	function __construct($_eqLogic) {
		$this->setEqLogic($_eqLogic);
	}

	public static function manageValue($_value) {
		$values = explode(':', $_value);
		if (count($values) < 2) {
			return trim($_value);
		}
		switch (trim($values[0])) {
			case 'Opaque':
				$return = $values[2];
				break;
			case 'STRING':
				$return = trim(trim(trim($values[1]), '"'));
				break;
			default:
				$return = '';
				for ($i = 1; $i < count($values); $i++) {
					$return .= $values[$i] . ':';
				}
				$return = trim($return, ':');
				break;
		}
		return trim($return);
	}

	/*     * *********************Methode d'instance************************* */

	public function update() {
		foreach ($this->getEqLogic()->getCmd('info') as $cmd) {
			if ($cmd->getConfiguration('motor') != 'snmp') {
				continue;
			}
			try {
				$key = explode('::', $cmd->getConfiguration('usercmd'));
				$function = $key[0];
				if (method_exists($this, $function)) {
					$arguments = array();
					if (count($key) > 1) {
						array_shift($key);
						$arguments = $key;
					}
					$value = call_user_func_array(array($this, $function), $arguments);
				} else {
					$value = $this->getValue($cmd->getConfiguration('usercmd'));
				}
				$this->getEqLogic()->checkAndUpdateCmd($cmd, $value);
			} catch (Exception $e) {

			}
		}
	}

	public function getValue($_key, $_raw = false) {
		switch ($this->getEqLogic()->getConfiguration('snmp::protocole')) {
			case 1:
				$values = snmpwalk($this->getEqLogic()->getConfiguration('snmp::ip'), $this->getEqLogic()->getConfiguration('snmp::community'), $_key);
				if (!is_array($values)) {
					usleep(200);
					$values = snmpwalk($this->getEqLogic()->getConfiguration('snmp::ip'), $this->getEqLogic()->getConfiguration('snmp::community'), $_key);
				}
				break;
			case 2:
				$values = snmp2_walk($this->getEqLogic()->getConfiguration('snmp::ip'), $this->getEqLogic()->getConfiguration('snmp::community'), $_key);
				if (!is_array($values)) {
					usleep(200);
					$values = snmp2_walk($this->getEqLogic()->getConfiguration('snmp::ip'), $this->getEqLogic()->getConfiguration('snmp::community'), $_key);
				}
				break;
			case 3:
				$values = snmp3_walk($this->getEqLogic()->getConfiguration('snmp::ip'), $this->getEqLogic()->getConfiguration('snmp::username'), $this->getEqLogic()->getConfiguration('snmp::security'), $this->getEqLogic()->getConfiguration('snmp::authmode'), $this->getEqLogic()->getConfiguration('snmp::password'), $this->getEqLogic()->getConfiguration('snmp::privprotocole'), $this->getEqLogic()->getConfiguration('snmp::privpassphrase'), $_key);
				if (!is_array($values)) {
					usleep(200);
					$values = snmp3_walk($this->getEqLogic()->getConfiguration('snmp::ip'), $this->getEqLogic()->getConfiguration('snmp::username'), $this->getEqLogic()->getConfiguration('snmp::security'), $this->getEqLogic()->getConfiguration('snmp::authmode'), $this->getEqLogic()->getConfiguration('snmp::password'), $this->getEqLogic()->getConfiguration('snmp::privprotocole'), $this->getEqLogic()->getConfiguration('snmp::privpassphrase'), $_key);
				}
				break;
		}
		if (!is_array($values)) {
			throw new Exception('Can not retrieve SNMP values : ' . print_r($values, true));
		}
		if ($_raw) {
			return $values;
		}
		if (is_array($values)) {
			if (count($values) == 1) {
				$value = self::manageValue($values[0]);
			} else {
				$value = '';
				foreach ($values as $rvalue) {
					if (is_array($rvalue)) {
						continue;
					}
					$value .= self::manageValue($rvalue) . ' - ';
				}
				$value = trim(trim(trim($value), '-'));
			}
		}
		return $value;
	}

	public function diskused($_number = 0) {
		return round(($this->getValue('iso.3.6.1.2.1.25.2.3.1.6.' . $_number) / $this->getValue('iso.3.6.1.2.1.25.2.3.1.5.' . $_number)) * 100, 2);
	}

	public function memoryused() {
		try {
			@$total = $this->getValue('.1.3.6.1.4.1.2021.4.5.0');
		} catch (Exception $e) {
			return round($this->getValue('.1.3.6.1.2.1.25.2.3.1.6.6') / $this->getValue('.1.3.6.1.2.1.25.2.3.1.5.6') * 100, 2);
		}
		$res1 = round(($this->getValue('.1.3.6.1.4.1.2021.4.6.0') / $total) * 100, 2);
		$res2 = round((($total - $this->getValue('.1.3.6.1.4.1.2021.4.11.0')) / $total) * 100, 2);
		if ($res1 < 0) {
			$res1 = $res2;
		}
		if ($res2 < 0) {
			$res2 = $res1;
		}
		return ($res1 > $res2) ? $res2 : $res1;
	}

	public function sysuptime() {
		try {
			@$value = $this->getValue('.1.3.6.1.2.1.25.1.1.0');
		} catch (Exception $e) {
			$value = $this->getValue('.1.3.6.1.2.1.1.3.0');
		}
		$values = explode(')', $value);
		if (count($values) == 2) {
			return $values[1];
		}
		return $value;
	}

	public function cpuused() {
		try {
			@$cpus = $this->getValue('.1.3.6.1.2.1.25.3.3', true);
		} catch (Exception $e) {
			return 100 - $this->getValue('.1.3.6.1.4.1.2021.11.11.0');
		}
		$values = array();
		foreach ($cpus as $cpu) {
			$values[] = self::manageValue($cpu);
		}
		if (count($values) == 0) {
			return 0;
		}
		return array_sum($values) / count($values);
	}

	public function vmwarerunvm() {
		$vms = $this->getValue('.1.3.6.1.4.1.6876.2.1.1.6', true);
		$return = 0;
		foreach ($vms as $vm) {
			if (strpos($vm, 'powered on') !== false) {
				$return++;
			}
		}
		return $return;
	}

	public function networkout($_number = 1) {
		$now = strtotime('now');
		$previous = $this->getEqLogic()->getCache('networkOut::lastRawValue', 0);
		$value = $this->getValue('.1.3.6.1.2.1.2.2.1.16.' . $_number);
		$return = ($value - $previous) / ($now - $this->getEqLogic()->getCache('networkOut::lastRawDate', 0));
		$this->getEqLogic()->setCache('networkOut::lastRawDate', $now);
		$this->getEqLogic()->setCache('networkOut::lastRawValue', $value);
		if ($return < 0) {
			return 0;
		}
		return round($return / 1024 / 1024, 1);
	}

	public function networkin($_number = 1) {
		$now = strtotime('now');
		$previous = $this->getEqLogic()->getCache('networkIn::lastRawValue', 0);
		$value = $this->getValue('.1.3.6.1.2.1.2.2.1.10.' . $_number);
		$return = ($value - $previous) / ($now - $this->getEqLogic()->getCache('networkIn::lastRawDate', 0));
		$this->getEqLogic()->setCache('networkIn::lastRawDate', $now);
		$this->getEqLogic()->setCache('networkIn::lastRawValue', $value);
		if ($return < 0) {
			return 0;
		}
		return round($return / 1024 / 1024, 1);
	}

	public function runprocess($_process = 1) {
		$count = 0;
		if (!isset($this->cache['process'])) {
			$this->cache['process'] = $this->getValue('.1.3.6.1.2.1.25.4.2.1.2', true);
		}
		foreach ($this->cache['process'] as $value) {
			$value = self::manageValue($value);
			if ($value == $_process) {
				$count++;
			}
		}
		return $count;
	}

	/*     * **********************Getteur Setteur*************************** */

	public function getEqLogic() {
		return $this->eqLogic;
	}

	public function setEqLogic($eqLogic) {
		$this->eqLogic = $eqLogic;
		return $this;
	}

}