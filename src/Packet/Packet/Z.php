<?php
namespace Mars\Packet\Packet;

use Mars\Network\Server;
use Mars\Packet\PacketInterface;
use Mars\Utility\User;

class Z implements PacketInterface {

/**
 * The bot has been tickled by someone.
 *
 * @param \Mars\Network\Server $server The server instance.
 * @param array $data The data received from the socket.
 *
 * @return bool
 */
	public function onZ(Server $server, $data) {
		debug($data);
		if (isset($data['z']['u']) && !empty($data['z']['u'])) {
			$id = User::parseId($data['z']['u']);

			$server->ModuleManager->answerTickle((int)$id);

			return true;
		}

		return false;
	}
}
