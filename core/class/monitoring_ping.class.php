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

class monitoring_ping {

	/*     * *************************Attributs****************************** */

	private $eqLogic;

	/*     * ***********************Methode static*************************** */

	function __construct($_eqLogic) {
		$this->setEqLogic($_eqLogic);
	}

	/*     * *********************Methode d'instance************************* */

	public function update() {
		$latency = $this->exec();
		if ($latency === false) {
			$latency = $this->exec();
		}
		if ($latency === false) {
			usleep(100);
			$latency = $this->exec();
		}
		if ($latency === false) {
			$latency = -1;
			$ping = 0;
		} else {
			$ping = 1;
		}
		foreach ($this->getEqLogic()->getCmd('info') as $cmd) {
			if ($cmd->getConfiguration('motor') != 'ping') {
				continue;
			}
			if ($cmd->getConfiguration('usercmd') == 'ping') {
				$this->getEqLogic()->checkAndUpdateCmd($cmd, $ping);
			}
			if ($cmd->getConfiguration('usercmd') == 'latency') {
				$this->getEqLogic()->checkAndUpdateCmd($cmd, $latency);
			}
		}
	}

	public function exec() {
		$latency = false;
		$exec_string = 'ping -n -c 2 -t 255 ' . escapeshellcmd($this->getEqLogic()->getConfiguration('ping::ip'));
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

	/*     * **********************Getteur Setteur*************************** */

	public function getEqLogic() {
		return $this->eqLogic;
	}

	public function setEqLogic($eqLogic) {
		$this->eqLogic = $eqLogic;
		return $this;
	}

}