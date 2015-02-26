<?php
namespace Mars\Packet\Packet;

use Mars\Configure\Configure;
use Mars\Network\Server;
use Mars\Packet\PacketInterface;

class Gp implements PacketInterface {

/**
 * Xat send us the powers information about this room.
 *
 * @param \Mars\Network\Server $server The server instance.
 * @param array $data The data received from the socket.
 *
 * @return void
 */
	public function onGp(Server $server, $data) {
		$gpa = explode('|', $data['gp']['p']);
		$ll = 0;
		$groupPowers = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
		$gConfig = [];

		$countGpa = count($gpa);

		while ($ll < $countGpa) {
			$groupPowers[$ll] = $gpa[$ll];
			$ll++;
		}

		foreach ($data['gp'] as $key => $g) {
			$index = $key;

			if (substr($key, 0, 1) === "g") {
				if ($this->_hasPower($groupPowers, (int)substr($key, 1))) {
					$gConfig[$key] = $g;
					if (substr($gConfig[$key], 0, 1) === "{") {
						$gConfig[$key] = json_decode($this->_fixJSON($g), true);
					}
				}
			}
		}

		debug($gConfig);
	}


	protected function _hasPower($group, $powerId, $arg3 = null) {
		if (!$group) {
			return false;
		}

		$local4 = $powerId >> 5;

		if ($powerId < 0) {
			$local4 = -1;
		}

		if (!isset($group[$local4])) {
			return false;
		}

		$local5 = $powerId % 32;

		if ($powerId < 0) {
			$locale5 = -(($powerIs % 32) - 1);
		}

		$local6 = $group[$local4];

		if (!is_null($arg3) && isset($arg3[$local4])) {
			$local6 = ($local6 & ~($local3[$local4]));
		}

		return (!(($local6 & (1 << $local5)) == 0));
	}

	protected function _fixJSON($json) {
		//@codingStandardsIgnoreStart
		$regex = <<<'REGEX'
~
	"[^"\\]*(?:\\.|[^"\\]*)*"
	(*SKIP)(*F)
  | '([^'\\]*(?:\\.|[^'\\]*)*)'
~x
REGEX;
		//@codingStandardsIgnoreEnd
		return preg_replace_callback($regex, function($matches) {
			return '"' . preg_replace('~\\\\.(*SKIP)(*F)|"~', '\\"', $matches[1]) . '"';
		}, $json);
	}
}
