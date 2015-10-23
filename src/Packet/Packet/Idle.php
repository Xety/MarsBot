<?php
namespace Mars\Packet\Packet;

use Mars\Configure\Configure;
use Mars\Network\Server;
use Mars\Packet\PacketInterface;

class Idle implements PacketInterface
{
    /**
     * Xat's telling us that we was not active for a while.
     *
     * @param \Mars\Network\Server $server The server instance.
     * @param array $data The data received from the socket.
     *
     * @return bool
     */
    public function onIdle(Server $server, $data)
    {
        if (isset($data['idle']['e'])) {
            //We can also send a message to the user 1 every X minutes.
            $server->Socket->disconnect();
            $server->startup();

            return true;
        }

        return false;
    }
}
