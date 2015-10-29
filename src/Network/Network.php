<?php
namespace Mars\Network;

use Mars\Configure\Configure;
use Mars\Configure\InstanceConfigTrait;
use Mars\Network\Exception\NetworkException;
use Mars\Network\Exception\SocketException;
use Mars\Utility\Xml;

class Network
{
    use InstanceConfigTrait;

    /**
     * Default configuration settings for the network connection.
     *
     * @var array
     */
    protected $_defaultConfig = [
        'loginRoom' => 8,
        'socket' => [
            'host' => '50.115.127.232',
            'port' => 10000
        ]
    ];

    /**
     * The response from xat when connecting to xat.
     *
     * @var array
     */
    public $loginInfos = [];

    /**
     * Constructor.
     *
     * @param array $config Network configuration, which will be merged with the base configuration.
     *
     * @see Network::$_defaultConfig
     */
    public function __construct(array $config = [])
    {
        $this->config($config);
    }

    /**
     * Get the needed informations from xat by login the bot on xat website.
     *
     * @return void
     *
     * @throws \Mars\Network\Exception\SocketException When the socket is not connected.
     */
    public function startup()
    {
        $config = $this->config()['socket'];

        $config['port'] += floor(rand(0, 38));

        $socket = new Socket($config);
        $socket->connect();

        if ($socket->connected === false) {
            throw new SocketException('Can not connected to the socket ' . $config['host'] . ':' . $config['port'] . '.', E_WARNING);
        }

        //Send the login packet to xat.
        $socket->write($this->_buildLoginPacket());
        $response = $socket->read();

        if ($response === false) {
            throw new NetworkException('Can not read the socket while connecting to xat.', E_WARNING);
        }

        $result = Xml::toArray(Xml::build(Xml::repair($response)));

        //Send private informations to xat.
        $socket->write($this->_buildPrivatePacket());
        $response = $socket->read();

        if ($response === false) {
            throw new NetworkException('Can not read the socket while connecting to xat.', E_WARNING);
        }
        $result += Xml::toArray(Xml::build(Xml::repair($response)));

        $this->loginInfos = $result;
    }

    /**
     * Build the login packet to send it in the socket.
     *
     * @return string
     */
    protected function _buildLoginPacket()
    {
        $packet = [
            'y' => [
                'r' => $this->config()['loginRoom'],
                'v' => 0,
                'u' => Configure::read('Bot.id')
            ]
        ];

        return Xml::build($packet);
    }

    /**
     * Build the private packet to send it in the socket.
     *
     * @return string
     */
    protected function _buildPrivatePacket()
    {
        $packet = [
            'v' => [
                'n' => Configure::read('Bot.username'),
                'p' => Configure::read('Bot.password')
            ]
        ];

        return Xml::build($packet);
    }
}
