<?php
namespace Mars\Packet\Packet;

use Mars\Configure\Configure;
use Mars\Message\Message;
use Mars\Network\Server;
use Mars\Packet\PacketInterface;
use Mars\Utility\User;

class M implements PacketInterface {

/**
 * A message has been send in the room.
 *
 * @param \Mars\Network\Server $server The server instance.
 * @param array $response The data received from the socket.
 *
 * @return bool
 */
	public function onM(Server $server, $response) {
		//A message has been posted in main chat.
		$data['message'] = $response['m']['t'];

		//Ignore all commands message starting with / (Like deleting a message, Typing etc).
		if (!isset($data['message']) || substr($data['message'], 0, 1) == '/') {
			return;
		}

		$data['old'] = (isset($response['m']['s'])) ? true : false;

		//Xat send sometimes the old messages, we ignore it so.
		if ($data['old']) {
			return;
		}

		//Get the Id of the user who has sent the message.
		$data['userId'] = ((isset($response['m']['u'])) ? $response['m']['u'] : false);

		if ($data['userId']) {
			$data['userId'] = User::parseId($response['m']['u']);
		}

		$message = new Message($data);
		$server->ModuleManager->onChannelMessage($message);
	}
}
