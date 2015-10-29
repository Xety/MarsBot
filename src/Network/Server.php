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
     * Startup the bot, Initialize the UserManager, ModuleManager and PakcketManager.
     *
     * @return void
     */
    public function startup()
    {
        $this->Room = new Room();
        $this->Network = new Network();

        $this->login();
    }

    /**
     * Login to xat.
     *
     * @param bool $connect True if we must also connect to a room.
     *
     * @return void
     */
    public function login($connect = true)
    {
        $this->Network->startup();

        if ($connect === true) {
            $this->connect();
        }
    }

    /**
     * Connect the bot to a room.
     *
     * @param string|int $room The room to join.
     * @param string $host The host to connect to the room.
     * @param int $port The port used to connect to the room.
     *
     * @return void
     */
    public function connect($room = null, $host = null, $port = null)
    {
        if (is_null($room)) {
            $room = Configure::read('Room.name');
        }
        $room = Room::getRoomInfo($room);

        $array = $this->Room->join($this->Network->loginInfos, $room, $host, $port);

        $this->Socket = $array['socket'];
        $this->Network->loginInfos = $array['network'];

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
            $length = strlen($response);

            while ($response{$length - 1} != '>' && $response{$length - 1} != chr(0)) {
                $response .= $this->Socket->read();
                $length = strlen($response);
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
