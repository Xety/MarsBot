<?php
namespace Mars\Packet\Packet;

use Mars\Network\Server;
use Mars\Packet\PacketInterface;
use Mars\Utility\User;

class U implements PacketInterface {

/**
 * An user has joined the room.
 *
 * @param \Mars\Network\Server $server The server instance.
 * @param array $data The data received from the socket.
 *
 * @return bool
 */
	public function onU(Server $server, $data) {
		if (isset($data['u']['u'])) {

			$result = $server->UserManager->load($data);
			debug($result);

			return true;
		}

		return false;
	}
}
