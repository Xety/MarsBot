<?php
namespace Mars\Network;

use Mars\Configure\Configure;
use Mars\Module\ModuleManager;
use Mars\Network\Network;
use Mars\Network\Room;
use Mars\Packet\PacketManager;
use Mars\User\UserManager;
use Mars\Utility\Inflector;
use Mars\Utility\Xml;

class Server {

	public $Network;

	public $Room;

	public $Socket;

	public $ModuleManager;

	public $PacketManager;

	public $UserManager;

	public function __construct() {
		$this->Network = new Network();
		$this->Room = new Room(['room' => Configure::read('Room.id')]);
	}

	public function startup($room = null, $host = null, $port = null) {
		$this->Socket = $this->Room->join($this->Network->startup(), $room, $host, $port);

		//Initialize the PacketManager.
		$this->UserManager = new UserManager();

		//Initialize the ModuleManager.
		$modulesPriorities = [];
		if (Configure::check('Modules.priority')) {
			$modulesPriorities = Configure::read('Modules.priority');
		}

		$this->ModuleManager = new ModuleManager($modulesPriorities);

		//Initialize the PacketManager.
		$packetsPriorities = [];
		if (Configure::check('Packets.priority')) {
			$packetsPriorities = Configure::read('Packets.priority');
		}

		$this->PacketManager = new PacketManager($packetsPriorities);

		$this->ModuleManager->addPrefixArgument([$this]);
		$this->PacketManager->addPrefixArgument([$this]);

		$this->_handleWhile();
	}

	protected function _handleWhile() {
		while ($this->Socket->connected === true) {
			$this->_handleResponse($this->Socket->read());
		}
	}

	protected function _handleResponse($response) {
		if ($response != false) {
			if ($response{(strlen($response) - 2)} != '>') {
				$response .= $this->Socket->read();

			}

			$packets = [];
			preg_match_all("/(<([\w]+)[^>]*>)/", $response, $packets, PREG_SET_ORDER);

			foreach ($packets as $packet) {
				$packet[0] = Xml::toArray(Xml::build($packet[0]));
				debug($packet);
				$this->PacketManager->{'on' . Inflector::camelize($packet[2])}($packet[0]);
			}
		}
	}

}
