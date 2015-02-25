<?php
namespace Mars\Packet\Packet;

use Mars\Network\Server;
use Mars\Packet\PacketInterface;

class Q implements PacketInterface {

/**
 * XAT notice to change Ip and/or Port.
 *
 * @param \Mars\Network\Server $server The server instance.
 * @param array $data The data received from the socket.
 *
 * @return void
 */
	public function onQ(Server $server, $data) {
		if (isset($data['q']['d']) && isset($data['q']['p'])) {
			$server->Socket->disconnect();
			$server->startup($data['q']['d'], $data['q']['p']);

			return true;
		}

		return false;
	}
}
