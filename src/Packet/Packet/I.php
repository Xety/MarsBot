<?php
namespace Mars\Packet\Packet;

use Mars\Network\Server;
use Mars\Packet\PacketInterface;

class I implements PacketInterface
{
    /**
     * Xat send us information about the room, like bg url, language, color, radio etc.
     *
     * @param \Mars\Network\Server $server The server instance.
     * @param array $data The data received from the socket.
     *
     * @return bool
     */
    public function onI(Server $server, $data)
    {
        if (isset($data['i']['b'])) {
            $infos = $this->_splitBackground($data['i']['b']);

            $server->Room->roomInfos = $infos;

            return true;
        }

        return false;
    }

    /**
     * Parse a string to get the information about the room.
     *
     * @param string $data String to parse.
     *
     * @return array
     */
    protected function _splitBackground($data = null)
    {
        if (is_null($data) || !is_string($data)) {
            return false;
        }

        $keys = [
            'background',
            'name',
            'id',
            'language',
            'radio',
            'color'
        ];

        $config = explode(';=', $data);

        $back = explode('#', $config[0]);
        $config[0] = $back[0];

        if (empty($config[1])) {
            $config[1] = 'Lobby';
        }

        if ($config[2] < 1) {
            $config[2] = 1;
        }

        if (isset($config[6])) {
            unset($config[6]);
        }

        $roomInfos = array_combine($keys, $config);

        return $roomInfos;
    }
}
