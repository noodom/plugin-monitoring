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

class monitoring_cli {

	/*     * *************************Attributs****************************** */
	private $cache = array();
	private $eqLogic;

	/*     * ***********************Methode static*************************** */

	function __construct($_eqLogic) {
		$this->setEqLogic($_eqLogic);
	}

	/*     * *********************Methode d'instance************************* */

	public function update() {
		foreach ($this->getEqLogic()->getCmd('info') as $cmd) {
			if ($cmd->getConfiguration('motor') != 'cli') {
				continue;
			}
			try {
				$key = explode('::', $cmd->getConfiguration('usercmd'));
				$function = '' . $key[0];
				if (method_exists($this, $function)) {
					$arguments = array();
					if (count($key) > 1) {
						array_shift($key);
						$arguments = $key;
					}
					$value = call_user_func_array(array($this, $function), $arguments);
				} else {
					$value = $this->execCmd($cmd->getConfiguration('usercmd'));
				}
				$this->getEqLogic()->checkAndUpdateCmd($cmd, $value);
			} catch (Exception $e) {

			}
		}
	}

	public function cpufreq() {
		if ($this->execCmd('ls /sys/devices/system/cpu/cpu0/cpufreq/cpuinfo_cur_freq 2>/dev/null | wc -l') == 1) {
			return $this->execCmd('sudo cat /sys/devices/system/cpu/cpu0/cpufreq/cpuinfo_cur_freq') / 1000;
		}
		return 0;
	}

	public function cputemp() {
		if ($this->execCmd('ls /sys/devices/virtual/thermal/thermal_zone0/temp 2>/dev/null | wc -l') == 1) {
			return $this->execCmd('sudo cat /sys/devices/virtual/thermal/thermal_zone0/temp') / 1000;
		}
		return 0;
	}

	public function memuse() {
		$meminfo = $this->getSystemMemoryInfo();
		if (isset($meminfo['MemAvailable'])) {
			return round(100 - $meminfo['MemAvailable'] / $meminfo['MemTotal'] * 100, 2);
		} elseif (isset($meminfo['MemFree'])) {
			return round(100 - $meminfo['MemFree'] / $meminfo['MemTotal'] * 100, 2);
		} else {
			return -1;
		}
	}

	public function swap() {
		if (isset($meminfo['SwapTotal']) && $meminfo['SwapTotal'] > 0) {
			return round(100 - $meminfo['SwapFree'] / $meminfo['SwapTotal'] * 100, 2);
		} else {
			return -1;
		}
	}

	public function loadavg15() {
		$uptime_string = $this->execCmd('uptime');
		$pattern = '/load average: (.*), (.*), (.*)$/';
		preg_match($pattern, $uptime_string, $matches);
		return $matches[3];
	}

	public function uptime() {
		$uptime_string = $this->execCmd('uptime');
		$pattern = '/up (.*?),/';
		preg_match($pattern, $uptime_string, $matches);
		return $matches[1];
	}

	public function hdduse($_mount = '/') {
		$space = $this->execCmd('sudo df -h ' . $_mount . ' | tail -n 1');
		$pattern = '/([1-9]*?)\%/';
		preg_match($pattern, $space, $matches);
		return $matches[1];
	}

	public function getSystemMemoryInfo() {
		if (isset($this->cache['memoryInfo'])) {
			return $this->cache['memoryInfo'];
		}
		$keys = array('MemTotal', 'MemFree', 'MemAvailable', 'SwapTotal', 'SwapFree');
		$result = array();
		$data = explode("\n", $this->execCmd('cat /proc/meminfo'));
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
		$this->cache['memoryInfo'] = $result;
		return $this->cache['memoryInfo'];
	}

	public function execCmd($_cmd) {
		if ($this->getEqLogic()->getConfiguration('cli::mode', 'local') == 'local') {
			return shell_exec($_cmd);
		}
		if ($this->getEqLogic()->getConfiguration('cli::mode') == 'ssh') {
			$_cmd = str_replace('sudo ', '', $_cmd);
			$connection = ssh2_connect($this->getEqLogic()->getConfiguration('ssh::ip'), $this->getEqLogic()->getConfiguration('ssh::port', 22));
			if ($connection === false) {
				throw new Exception(__('Impossible de se connecter sur :', __FILE__) . ' ' . $this->getEqLogic()->getConfiguration('ssh::ip') . ':' . $this->getEqLogic()->getConfiguration('ssh::port', 22));
			}
			$auth = @ssh2_auth_password($connection, $this->getEqLogic()->getConfiguration('ssh::username'), $this->getEqLogic()->getConfiguration('ssh::password'));
			if (false === $auth) {
				throw new Exception(__('Echec de l\'authentification SSH', __FILE__));
			}
			$stream = ssh2_exec($connection, $_cmd);
			stream_set_blocking($stream, true);
			return stream_get_contents($stream);
		}
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