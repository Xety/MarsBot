<?php
namespace Mars\Packet\Packet;

use Mars\Configure\Configure;
use Mars\Network\Server;
use Mars\Packet\PacketInterface;
use Mars\Utility\Xavi;

//When xat has finished to send the packets when connecting.
class Done implements PacketInterface
{
    /**
     * The bot has enter in the room and has got all the packet.
     *
     * @param \Mars\Network\Server $server The server instance.
     * @param array $data The data received from the socket.
     *
     * @return bool
     */
    public function onDone(Server $server, $data)
    {
        if (Configure::read('Xavi.enabled') === true) {
            if (!is_numeric(Configure::read('Xavi.id'))) {
                return false;
            }

            $xavi = Xavi::get(Configure::read('Xavi.id'));

            if ($xavi === false) {
                $server->ModuleManager->message('Error to get the xavi of the user ' . Configure::read('Xavi.id'));
                break;
            }

            $result = Xavi::post($xavi, $server->Room->loginInfos);

            if ($result === false) {
                $server->ModuleManager->message('Error to save the xavi.');
                break;
            }

            $server->ModuleManager->message('My xavi has been saved successfully with the MarsBot ! Thanks to Mars. :)');

            $server->Socket->disconnect();

            return true;
        }

        return false;
    }
}
