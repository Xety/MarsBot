<?php
namespace Mars\Packet\Packet;

use Mars\Network\Server;
use Mars\Packet\PacketInterface;
use Mars\Utility\User;

class L implements PacketInterface {

/**
 * An user has quited the room.
 *
 * @param \Mars\Network\Server $server The server instance.
 * @param array $data The data received from the socket.
 *
 * @return bool
 */
	public function onL(Server $server, $data) {
		if (isset($data['l']['u'])) {

			$result = $server->UserManager->unload($data);
			debug($result);

			return true;
		}

		return false;
	}
}
