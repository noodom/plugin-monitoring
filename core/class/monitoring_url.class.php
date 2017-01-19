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

class monitoring_url {
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
			if ($cmd->getConfiguration('motor') != 'url') {
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

	public function access($_url, $_username = null, $_password = null) {
		if ($_username != null && $_password != null) {
			$request_http = new com_http($_url, $_username, $_password);
		} else {
			$request_http = new com_http($_url);
		}
		$request_http->setAllowEmptyReponse(true);
		$request_http->setNoSslCheck(true);
		try {
			$request_http->exec();
		} catch (Exception $e) {
			slee(1);
			try {
				$request_http->exec();
			} catch (Exception $e) {
				return 0;
			}
		}
		return 1;
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