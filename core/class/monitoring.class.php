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
	private $_snmp_cache = array();

	/*     * ***********************Methode static*************************** */

	public static function dependancy_info() {
		$return = array();
		$return['progress_file'] = '/tmp/dependancy_monitoring_in_progress';
		if (exec('sudo dpkg --get-selections | grep -E "php5\-snmp" | grep -v desinstall | wc -l') >= 1) {
			$return['state'] = 'ok';
		} else {
			$return['state'] = 'nok';
		}
		return $return;
	}
	public static function dependancy_install() {
		log::remove('monitoring_update');
		$cmd = 'sudo /bin/bash ' . dirname(__FILE__) . '/../../resources/install.sh';
		$cmd .= ' >> ' . log::getPathToLog('monitoring_dependancy') . ' 2>&1 &';
		exec($cmd);
	}

	public static function cron($_eqLogic_id = null) {
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
		if ($this->getConfiguration('ping') == 1) {
			$ping = $this->getCmd(null, 'ping');
			if (!is_object($ping)) {
				$ping = new monitoringCmd();
				$ping->setLogicalId('ping');
				$ping->setIsVisible(1);
				$ping->setName(__('Ping', __FILE__));
				$ping->setOrder(1);
				$ping->setTemplate('dashboard', 'line');
			}
			$ping->setType('info');
			$ping->setSubType('binary');
			$ping->setEqLogic_id($this->getId());
			$ping->save();

			$latency = $this->getCmd(null, 'latency');
			if (!is_object($latency)) {
				$latency = new monitoringCmd();
				$latency->setLogicalId('latency');
				$latency->setIsVisible(1);
				$latency->setName(__('Latence', __FILE__));
				$latency->setOrder(2);
				$latency->setTemplate('dashboard', 'line');
			}
			$latency->setType('info');
			$latency->setSubType('numeric');
			$latency->setEqLogic_id($this->getId());
			$latency->setUnite('ms');
			$latency->save();
		}
		if ($this->getConfiguration('cli') == 1) {
			$uptime = $this->getCmd(null, 'uptime');
			if (!is_object($uptime)) {
				$uptime = new monitoringCmd();
			}
			$uptime->setName(__('Uptime', __FILE__));
			$uptime->setEqLogic_id($this->getId());
			$uptime->setLogicalId('uptime');
			$uptime->setType('info');
			$uptime->setSubType('string');
			$uptime->save();

			$loadavg15 = $this->getCmd(null, 'loadavg15');
			if (!is_object($loadavg15)) {
				$loadavg15 = new monitoringCmd();
				$loadavg15->setTemplate('mobile', 'line');
				$loadavg15->setTemplate('dashboard', 'line');
				$loadavg15->setName(__('Load', __FILE__));
			}

			$loadavg15->setEqLogic_id($this->getId());
			$loadavg15->setLogicalId('loadavg15');
			$loadavg15->setType('info');
			$loadavg15->setSubType('numeric');
			$loadavg15->save();

			$memfree = $this->getCmd(null, 'memuse');
			if (!is_object($memfree)) {
				$memfree = new monitoringCmd();
				$memfree->setTemplate('mobile', 'line');
				$memfree->setTemplate('dashboard', 'line');
				$memfree->setName(__('Mémoire', __FILE__));
			}

			$memfree->setEqLogic_id($this->getId());
			$memfree->setLogicalId('memuse');
			$memfree->setType('info');
			$memfree->setSubType('numeric');
			$memfree->setUnite('%');
			$memfree->save();

			$memswap = $this->getCmd(null, 'swapuse');
			if (!is_object($memswap)) {
				$memswap = new monitoringCmd();
				$memswap->setTemplate('mobile', 'line');
				$memswap->setTemplate('dashboard', 'line');
				$memswap->setName(__('Swap', __FILE__));
			}

			$memswap->setEqLogic_id($this->getId());
			$memswap->setLogicalId('swapuse');
			$memswap->setType('info');
			$memswap->setSubType('numeric');
			$memswap->setUnite('%');
			$memswap->save();

			$hddfree = $this->getCmd(null, 'hdduse');
			if (!is_object($hddfree)) {
				$hddfree = new monitoringCmd();
				$hddfree->setTemplate('mobile', 'line');
				$hddfree->setTemplate('dashboard', 'line');
				$hddfree->setName(__('Disque', __FILE__));
			}
			$hddfree->setEqLogic_id($this->getId());
			$hddfree->setLogicalId('hdduse');
			$hddfree->setType('info');
			$hddfree->setSubType('numeric');
			$hddfree->setUnite('%');
			$hddfree->save();

			$cpu_temp = $this->getCmd(null, 'cpu_temp');
			if ($this->cli_execCmd('ls /sys/devices/virtual/thermal/thermal_zone0/temp 2>/dev/null | wc -l') == 1) {
				if (!is_object($cpu_temp)) {
					$cpu_temp = new monitoringCmd();
					$cpu_temp->setTemplate('mobile', 'line');
					$cpu_temp->setTemplate('dashboard', 'line');
					$cpu_temp->setName(__('Température cpu', __FILE__));
				}
				$cpu_temp->setEqLogic_id($this->getId());
				$cpu_temp->setLogicalId('cpu_temp');
				$cpu_temp->setType('info');
				$cpu_temp->setSubType('numeric');
				$cpu_temp->setUnite('°C');
				$cpu_temp->save();
			} else {
				if (is_object($cpu_temp)) {
					$cpu_temp->remove();
				}
			}

			$cpu_freq = $this->getCmd(null, 'cpu_freq');
			if ($this->cli_execCmd('ls /sys/devices/system/cpu/cpu0/cpufreq/cpuinfo_cur_freq 2>/dev/null | wc -l') == 1) {
				if (!is_object($cpu_freq)) {
					$cpu_freq = new monitoringCmd();
					$cpu_freq->setTemplate('mobile', 'line');
					$cpu_freq->setTemplate('dashboard', 'line');
					$cpu_freq->setName(__('Fréquence cpu', __FILE__));
				}
				$cpu_freq->setEqLogic_id($this->getId());
				$cpu_freq->setLogicalId('cpu_freq');
				$cpu_freq->setType('info');
				$cpu_freq->setSubType('numeric');
				$cpu_freq->setUnite('Mhz');
				$cpu_freq->save();
			} else {
				if (is_object($cpu_freq)) {
					$cpu_freq->remove();
				}
			}
		}

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
		try {
			if ($this->getConfiguration('cli') == 1) {
				$this->cli_updateSysInfo();
			}
		} catch (Exception $e) {

		}
		try {
			if ($this->getConfiguration('snmp') == 1) {
				$this->snmp_updateSysInfo();
			}
		} catch (Exception $e) {

		}
		try {
			if ($this->getConfiguration('ping') == 1) {
				$this->ping_updateSysInfo();
			}
		} catch (Exception $e) {

		}
		if ($e !== null) {
			throw $e;
		}
	}

	public function ping_updateSysInfo() {
		$latency = $this->ping_exec();
		if ($latency === false) {
			$latency = $this->ping_exec();
		}
		if ($latency === false) {
			usleep(100);
			$latency = $this->ping_exec();
		}
		if ($latency === false) {
			$this->checkAndUpdateCmd('ping', 0);
			$this->checkAndUpdateCmd('latency', -1);
		} else {
			$this->checkAndUpdateCmd('ping', 1);
			$this->checkAndUpdateCmd('latency', $latency);
		}
	}

	public function ping_exec() {
		$latency = false;
		$exec_string = 'sudo ping -n -c 2 -t 255 ' . escapeshellcmd($this->getConfiguration('ping::ip'));
		exec($exec_string, $output, $return);
		$output = array_values(array_filter($output));
		if (!empty($output[1])) {
			$response = preg_match("/time(?:=|<)(?<time>[\.0-9]+)(?:|\s)ms/", $output[1], $matches);
			if ($response > 0 && isset($matches['time'])) {
				$latency = $matches['time'];
			}
		}
		return $latency;
	}

	public function snmp_updateSysInfo() {
		foreach ($this->getCmd('info', 'usercmd', null, true) as $cmd) {
			if ($cmd->getConfiguration('motor') != 'snmp') {
				continue;
			}
			try {
				$key = explode('::', $cmd->getConfiguration('usercmd'));
				$function = 'snmp_' . $key[0];
				if (method_exists($this, $function)) {
					$arguments = array();
					if (count($key) > 1) {
						array_shift($key);
						$arguments = $key;
					}
					$value = call_user_func_array(array($this, $function), $arguments);
				} else {
					$value = $this->snmp_getValue($cmd->getConfiguration('usercmd'));
				}
				$this->checkAndUpdateCmd($cmd, $value);
			} catch (Exception $e) {

			}
		}
	}

	public function snmp_getValue($_key, $_raw = false) {
		switch ($this->getConfiguration('snmp::protocole')) {
			case 1:
				$values = snmpwalk($this->getConfiguration('snmp::ip'), $this->getConfiguration('snmp::community'), $_key);
				if (!is_array($values)) {
					usleep(200);
					$values = snmpwalk($this->getConfiguration('snmp::ip'), $this->getConfiguration('snmp::community'), $_key);
				}
				break;
			case 2:
				$values = snmp2_walk($this->getConfiguration('snmp::ip'), $this->getConfiguration('snmp::community'), $_key);
				if (!is_array($values)) {
					usleep(200);
					$values = snmp2_walk($this->getConfiguration('snmp::ip'), $this->getConfiguration('snmp::community'), $_key);
				}
				break;
			case 3:
				$values = snmp3_walk($this->getConfiguration('snmp::ip'), $this->getConfiguration('snmp::username'), $this->getConfiguration('snmp::security'), $this->getConfiguration('snmp::authmode'), $this->getConfiguration('snmp::password'), $this->getConfiguration('snmp::privprotocole'), $this->getConfiguration('snmp::privpassphrase'), $_key);
				if (!is_array($values)) {
					usleep(200);
					$values = snmp3_walk($this->getConfiguration('snmp::ip'), $this->getConfiguration('snmp::username'), $this->getConfiguration('snmp::security'), $this->getConfiguration('snmp::authmode'), $this->getConfiguration('snmp::password'), $this->getConfiguration('snmp::privprotocole'), $this->getConfiguration('snmp::privpassphrase'), $_key);
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
				$value = self::snmp_manageValue($values[0]);
			} else {
				$value = '';
				foreach ($values as $rvalue) {
					if (is_array($rvalue)) {
						continue;
					}
					$value .= self::snmp_manageValue($rvalue) . ' - ';
				}
				$value = trim(trim(trim($value), '-'));
			}
		}
		return $value;
	}

	public function snmp_diskused($_number = 0) {
		return round(($this->snmp_getValue('iso.3.6.1.2.1.25.2.3.1.6.' . $_number) / $this->snmp_getValue('iso.3.6.1.2.1.25.2.3.1.5.' . $_number)) * 100, 2);
	}

	public function snmp_memoryused() {
		try {
			@$total = $this->snmp_getValue('.1.3.6.1.4.1.2021.4.5.0');
		} catch (Exception $e) {
			return round($this->snmp_getValue('.1.3.6.1.2.1.25.2.3.1.6.6') / $this->snmp_getValue('.1.3.6.1.2.1.25.2.3.1.5.6') * 100, 2);
		}
		$res1 = round(($this->snmp_getValue('.1.3.6.1.4.1.2021.4.6.0') / $total) * 100, 2);
		$res2 = round((($total - $this->snmp_getValue('.1.3.6.1.4.1.2021.4.11.0')) / $total) * 100, 2);
		return ($res1 > $res2) ? $res2 : $res1;
	}

	public function snmp_sysuptime() {
		try {
			@$value = $this->snmp_getValue('.1.3.6.1.2.1.25.1.1.0');
		} catch (Exception $e) {
			$value = $this->snmp_getValue('.1.3.6.1.2.1.1.3.0');
		}
		$values = explode(')', $value);
		if (count($values) == 2) {
			return $values[1];
		}
		return $value;
	}

	public function snmp_cpuused() {
		try {
			@$cpus = $this->snmp_getValue('.1.3.6.1.2.1.25.3.3', true);
		} catch (Exception $e) {
			return 100 - $this->snmp_getValue('.1.3.6.1.4.1.2021.11.11.0');
		}
		$values = array();
		foreach ($cpus as $cpu) {
			$values[] = self::snmp_manageValue($cpu);
		}
		if (count($values) == 0) {
			return 0;
		}
		return array_sum($values) / count($values);
	}

	public function snmp_vmwarerunvm() {
		$vms = $this->snmp_getValue('.1.3.6.1.4.1.6876.2.1.1.6', true);
		$return = 0;
		foreach ($vms as $vm) {
			if (strpos($vm, 'powered on') !== false) {
				$return++;
			}
		}
		return $return;
	}

	public function snmp_networkout($_number = 1) {
		$now = strtotime('now');
		$previous = $this->getCache('networkOut::lastRawValue', 0);
		$value = $this->snmp_getValue('.1.3.6.1.2.1.2.2.1.16.' . $_number);
		$return = ($value - $previous) / ($now - $this->getCache('networkOut::lastRawDate', 0));
		$this->setCache('networkOut::lastRawDate', $now);
		$this->setCache('networkOut::lastRawValue', $value);
		if ($previous < 0) {
			return 0;
		}
		return round($return / 1024 / 1024, 1);
	}

	public function snmp_networkin($_number = 1) {
		$now = strtotime('now');
		$previous = $this->getCache('networkIn::lastRawValue', 0);
		$value = $this->snmp_getValue('.1.3.6.1.2.1.2.2.1.10.' . $_number);
		$return = ($value - $previous) / ($now - $this->getCache('networkIn::lastRawDate', 0));
		$this->setCache('networkIn::lastRawDate', $now);
		$this->setCache('networkIn::lastRawValue', $value);
		if ($previous < 0) {
			return 0;
		}
		return round($return / 1024 / 1024, 1);
	}

	public function snmp_runprocess($_process = 1) {
		$count = 0;
		if (!isset($this->snmp_cache['process'])) {
			$this->snmp_cache['process'] = $this->snmp_getValue('.1.3.6.1.2.1.25.4.2.1.2', true);
		}
		foreach ($this->snmp_cache['process'] as $value) {
			$value = self::snmp_manageValue($value);
			if ($value == $_process) {
				$count++;
			}
		}
		return $count;
	}

	public static function snmp_manageValue($_value) {
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

	public function cli_updateSysInfo() {
		if ($this->cli_execCmd('ls /sys/devices/system/cpu/cpu0/cpufreq/cpuinfo_cur_freq 2>/dev/null | wc -l') == 1) {
			$this->checkAndUpdateCmd('cpu_freq', $this->cli_execCmd('sudo cat /sys/devices/system/cpu/cpu0/cpufreq/cpuinfo_cur_freq') / 1000);
		}

		if ($this->cli_execCmd('ls /sys/devices/virtual/thermal/thermal_zone0/temp 2>/dev/null | wc -l') == 1) {
			$this->checkAndUpdateCmd('cpu_temp', $this->cli_execCmd('sudo cat /sys/devices/virtual/thermal/thermal_zone0/temp') / 1000);
		}

		$meminfo = $this->cli_getSystemMemoryInfo();
		if (isset($meminfo['MemAvailable'])) {
			$mem = round(100 - $meminfo['MemAvailable'] / $meminfo['MemTotal'] * 100, 2);
		} elseif (isset($meminfo['MemFree'])) {
			$mem = round(100 - $meminfo['MemFree'] / $meminfo['MemTotal'] * 100, 2);
		} else {
			$mem = -1;
		}
		$this->checkAndUpdateCmd('memuse', $mem);

		if (isset($meminfo['SwapTotal']) && $meminfo['SwapTotal'] > 0) {
			$swap = round(100 - $meminfo['SwapFree'] / $meminfo['SwapTotal'] * 100, 2);
		} else {
			$swap = -1;
		}
		$this->checkAndUpdateCmd('swapuse', $swap);

		$uptime_string = $this->cli_execCmd('uptime');
		$pattern = '/load average: (.*), (.*), (.*)$/';
		preg_match($pattern, $uptime_string, $matches);
		$this->checkAndUpdateCmd('loadavg15', $matches[3]);

		$pattern = '/up (.*?),/';
		preg_match($pattern, $uptime_string, $matches);
		$this->checkAndUpdateCmd('uptime', $matches[1]);

		$space = $this->cli_execCmd('sudo df -h / | tail -n 1');
		$pattern = '/([1-9]*?)\%/';
		preg_match($pattern, $space, $matches);
		$this->checkAndUpdateCmd('hdduse', $matches[1]);

		foreach ($this->getCmd('info', 'usercmd', null, true) as $cmd) {
			if ($cmd->getConfiguration('motor') != 'cli') {
				continue;
			}
			$this->checkAndUpdateCmd($cmd, $this->cli_execCmd($cmd->getConfiguration('usercmd')));
		}
	}

	public function cli_getSystemMemoryInfo() {
		$keys = array('MemTotal', 'MemFree', 'MemAvailable', 'SwapTotal', 'SwapFree');
		$result = array();
		try {
			$data = explode("\n", $this->cli_execCmd('cat /proc/meminfo'));
			if (is_array($data)) {
				foreach ($data as $d) {
					if (0 == strlen(trim($d))) {
						continue;
					}
					$d = preg_split('/:/', $d);
					$key = trim($d[0]);
					if (!in_array($key, $keys)) {
						continue;
					}
					$value = 1000 * floatval(trim(str_replace(' kB', '', $d[1])));
					$result[$key] = $value;
				}
			}
		} catch (Exception $e) {
			echo $e->getMessage();
		}
		return $result;
	}

	public function cli_execCmd($_cmd) {
		if ($this->getConfiguration('cli::mode', 'local') == 'local') {
			return shell_exec($_cmd);
		}
		if ($this->getConfiguration('cli::mode') == 'ssh') {
			$_cmd = str_replace('sudo ', '', $_cmd);
			$connection = ssh2_connect($this->getConfiguration('ssh::ip'), $this->getConfiguration('ssh::port', 22));
			if ($connection === false) {
				throw new Exception(__('Impossible de se connecter sur :', __FILE__) . ' ' . $this->getConfiguration('ssh::ip') . ':' . $this->getConfiguration('ssh::port', 22));
			}
			$auth = @ssh2_auth_password($connection, $this->getConfiguration('ssh::username'), $this->getConfiguration('ssh::password'));
			if (false === $auth) {
				throw new Exception(__('Echec de l\'authentification SSH', __FILE__));
			}
			$stream = ssh2_exec($connection, $_cmd);
			stream_set_blocking($stream, true);
			return stream_get_contents($stream);
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

	public function preSave() {
		if ($this->getLogicalId() == '') {
			$this->setLogicalId('usercmd');
		}
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
