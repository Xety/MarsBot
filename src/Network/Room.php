<?php
namespace Mars\Network;

use Mars\Configure\Configure;
use Mars\Configure\InstanceConfigTrait;
use Mars\Network\Exception\RoomException;
use Mars\Network\Exception\SocketException;
use Mars\Network\Http\Client;
use Mars\Utility\Text;
use Mars\Utility\Xml;

class Room
{
    use InstanceConfigTrait;

    /**
     * Default configuration settings for the room connection.
     *
     * @var array
     */
    protected $_defaultConfig = [
        'room' => 12069390,
        'ips' => [
            '173.255.132.116',
            '173.255.132.117',
            '173.255.132.118',
            '173.255.132.119'
        ]
    ];

    /**
     * Group powers with their configuration assigned to the room.
     *
     * @var array
     */
    public $groupPowers = [];

    /**
     * Informations about the room like background url, language, radio etc.
     *
     * @var array
     */
    public $roomInfos = [];

    /**
     * The response from xat before the j2.
     *
     * @var array
     */
    public $loginInfos = [];

    /**
     * Constructor.
     *
     * @param array $config Room configuration, which will be merged with the base configuration.
     *
     * @see Room::$_defaultConfig
     */
    public function __construct(array $config = [])
    {
        $this->config($config);
    }

    /**
     * Join a room.
     *
     * @param array $network The packet information from the network.
     * @param int $room The room id to join.
     * @param string $host The host of the socket.
     * @param string $port The port of the socket.
     *
     * @return \Mars\Network\Socket
     *
     * @throws \Mars\Network\Exception\RoomException When the network variable is empty.
     * @throws \Mars\Network\Exception\SocketException When the socket has failed to connect.
     */
    public function join(array $network = [], $room = null, $host = null, $port = null)
    {
        $config = $this->config();

        if (empty($network)) {
            throw new RoomException('The network can not be empty.', E_WARNING);
        }

        if (!is_null($room)) {
            $config['room'] = $room['id'];
        }

        $socketPort = static::getPort($config['room']);
        $socketHost = static::getHost($config['room'], $config['ips']);

        if (!is_null($host)) {
            $socketHost = $host;
        }

        if (!is_null($port)) {
            $socketPort = $port;
        }

        $socket = new Socket(['host' => $socketHost, 'port' => $socketPort]);
        $socket->connect();

        if ($socket->connected === false) {
            throw new SocketException('Can not connect to the socket ' . $config['host'] . ':' . $config['port'] . '.', E_WARNING);
        }

        $socket->write($this->_bluidConnectionPacket($config['room']));
        $response = $socket->read();

        if ($response === false) {
            throw new RoomException('Can not read the socket while joining the room.', E_WARNING);
        }
        $result = Xml::toArray(Xml::build(Xml::repair($response)));

        $network = array_merge($network, $result);

        $this->loginInfos = $network;

        $socket->write($this->_buildJoinPacket($result, $network, $config['room']));

        return ['socket' => $socket, 'network' => $network];
    }

    /**
     * Get the host from the IPs list by the ID room.
     *
     * @param int $room The room ID.
     * @param array $ips The list of IPs.
     *
     * @return string THe Ip.
     *
     * @throws \Mars\Network\Exception\RoomException
     */
    public static function getHost($room, array $ips = [])
    {
        if (empty($ips)) {
            throw new RoomException('The IPs list can not be empty.', E_WARNING);
        }

        $index = static::getIndex($room);

        if (!isset($ips[$index])) {
            throw new RoomException('Undefined IP index.', E_WARNING);
        }

        return $ips[$index];
    }

    /**
     * Get the index of the IP array to use.
     *
     * @param int $room The room id.
     *
     * @return int The index.
     */
    public static function getIndex($room)
    {
        if ($room == 8) {
            return 0;
        }

        return ($room < 8) ? 3 : ($room & 96) >> 5;
    }

    /**
     * Get the port for a specific room.
     *
     * @param int $room The room id.
     *
     * @return int The port.
     */
    public static function getPort($room)
    {
        if ($room == 8) {
            return 10000;
        }

        return ($room < 8) ? (10000 - 1) + $room : (10000 + 7) + $room % 32;
    }

    /**
     * Get the ID by the room name or the name by the ID.
     *
     * @param string|int $name The name/id of the room.
     *
     * @return false|bool
     */
    public static function getRoomInfo($name)
    {
        if (is_numeric($name)) {
            $url = 'http://xat.com/xat' . $name;
        } else {
            $url = 'http://xat.com/' . $name;
        }

        $http = new Client();
        $response = $http->get($url);

        $roomId = Text::getBetween($response->body, '<a href="http://xat.com/web_gear/chat/embed.php?id=', '&GroupName=');
        $roomName = Text::getBetween($response->body, '&GroupName=', '"');
        $title = Text::getBetween($response->body, '<title>', '</title>');
        $title = explode(' ', $title);

        if (!is_numeric($roomId) || $title[0] === 'xat') {
            return false;
        } else {
            Configure::write('Room.id', $roomId);
            Configure::write('Room.name', $roomName);

            $config = [
                'id' => $roomId,
                'name' => $roomName
            ];

            return $config;
        }
    }

    /**
     * Build the connection packet.
     *
     * - Order attributes :
     * w, r, m, s, p, v, u, z
     *
     * @param int $room The room ID.
     *
     * @return string The packet generated.
     */
    protected function _bluidConnectionPacket($room = null)
    {
        if (is_null($room)) {
            $room = $this->config()['room'];
        }

        $packet = [
            'y' => [
                'r' => $room,
                'm' => 1,
                'v' => 0,
                'u' => Configure::read('Bot.id'),
                'z' => 759873996
            ]
        ];

        return Xml::build($packet);
    }

    /**
     * Build the join packet (J2).
     *
     * - Order attributes :
     * cb,Y,l5,l4,l3,l2,q,y,k,k3,d1,z,p,c,b,r,f,e,u,m,d0,d[i],dO,sn,dx,dt,N,n,a,h,v
     *
     * @param array $connection The connection array.
     * @param array $network The login array.
     * @param int $room The room id.
     *
     * @return string The packet generated.
     *
     * @throws \Mars\Network\Exception\RoomException When the $connection and/or $network variables are empty.
     */
    protected function _buildJoinPacket(array $connection = [], array $network = [], $room = null)
    {
        if (empty($connection) || empty($network)) {
            throw new RoomException('The connection and/or network variable(s) can not be empty to build the J2 packet.', E_WARNING);
        }

        if (is_null($room)) {
            $room = $this->config()['room'];
        }

        $j2 = [
            'j2' => [
                'cb' => $connection['y']['c']
            ]
        ];

        if (isset($connection['y']['au'])) {
            $j2['j2']['Y'] = 2;
        }

        $j2['j2'] += [
            'l5' => '65535',
            'l4' => rand(10, 500),
            'l3' => rand(10, 500),
            'l2' => 0,
            'q' => 1,
            'y' => $connection['y']['i'],
            'k' => $network['v']['k1'],
            'k3' => $network['v']['k3']
        ];

        if (isset($network['v']['d1'])) {
            $j2['j2']['d1'] = $network['v']['d1'];
        }

        $j2['j2'] += [
            'z' => 12,
            'p' => 0,
            'c' => $room,
            'r' => '',
            'f' => 0, //6
            'e' => 1,
            'u' => $network['v']['i'],
            'd0' => $network['v']['d0']
        ];

        for ($x = 2; $x <= 15; $x++) {
            if (isset($network['v']['d' . $x])) {
                $j2['j2']['d' . $x] = $network['v']['d' . $x];
            }
        }

        if (isset($network['v']['dO'])) {
            $j2['j2']['dO'] = $network['v']['dO'];
        }

        if (isset($network['v']['dx'])) {
            $j2['j2']['dx'] = $network['v']['dx'];
        }

        if (isset($network['v']['dt'])) {
            $j2['j2']['dt'] = $network['v']['dt'];
        }

        $j2['j2'] += [
            'N' => $network['v']['n'],
            'n' => Configure::read('Bot.name'),
            'a' => Configure::read('Bot.avatar'),
            'h' => Configure::read('Bot.home'),
            'v' => 0
        ];

        return Xml::build($j2);
    }
}
