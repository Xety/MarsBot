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

class Server
{
    /**
     * The network instance.
     *
     * @var Mars\Network\Network
     */
    public $Network;

    /**
     * The network instance.
     *
     * @var Mars\Network\Room
     */
    public $Room;

    /**
     * The network instance.
     *
     * @var Mars\Network\Socket
     */
    public $Socket;

    /**
     * The network instance.
     *
     * @var Mars\Module\ModuleManager
     */
    public $ModuleManager;

    /**
     * The network instance.
     *
     * @var Mars\Packet\PacketManager
     */
    public $PacketManager;

    /**
     * The network instance.
     *
     * @var Mars\User\UserManager
     */
    public $UserManager;

    /**
     * Initialize the network and room instance.
     */
    public function __construct()
    {
        $this->Network = new Network();
        $this->Room = new Room();
    }

    /**
     * Connect to xat, join a room and Initialize the UserManager, ModuleManager and PakcketManager.
     *
     * @param string|int $room The room to join.
     * @param string $host The host to connect to the room.
     * @param int $port The port used to connect to the room.
     *
     * @return void
     */
    public function startup($room = null, $host = null, $port = null)
    {
        if (is_null($room)) {
            $room = Configure::read('Room.name');
        }
        $room = Room::getRoomInfo($room);

        $this->Socket = $this->Room->join($this->Network->startup(), $room, $host, $port);

        //Initialize the UserManager.
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

        //Handle the loop.
        $this->_handleWhile();
    }

    /**
     * The loop of the bot to read from the socket.
     *
     * @return void
     */
    protected function _handleWhile()
    {
        while ($this->Socket->connected === true) {
            $this->_handleResponse($this->Socket->read());
        }
    }

    /**
     * Handle the response from the socket.
     *
     * @param bool|string $response The response from the socket.
     *
     * @return void
     */
    protected function _handleResponse($response)
    {
        if ($response != false) {
            if ($response{(strlen($response) - 2)} != '>') {
                $response .= $this->Socket->read();
            }

            $packets = [];
            preg_match_all("/<([\w]+)[^>]*>/", $response, $packets, PREG_SET_ORDER);

            foreach ($packets as $packet) {
                $packet[0] = Xml::toArray(Xml::build(Xml::repair($packet[0])));
                debug($packet);
                $this->PacketManager->{'on' . Inflector::camelize($packet[1])}($packet[0]);
            }
        }
    }
}
