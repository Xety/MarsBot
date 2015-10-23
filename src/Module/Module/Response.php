<?php
namespace Mars\Module\Module;

use Mars\Configure\Configure;
use Mars\Module\ModuleInterface;
use Mars\Network\Server;
use Mars\Utility\Xml;

class Response implements ModuleInterface
{
    /**
     * Send a normal message.
     *
     * Note :
     *  - 256 characters maximum per message.
     *
     * @param \Mars\Network\Server $server The server instance.
     * @param string $message The message to send.
     *
     * @return bool
     *
     * Split the message if the message is too long.
     */
    public function message(Server $server, $message = null)
    {
        if (is_null($message)) {
            return false;
        }

        $server->Socket->write(Xml::build(['m' => ['t' => $message, 'u' => Configure::read('Bot.id')]]));

        return true;
    }

    /**
     * When an user tickle the bot, you must send back this packet to display
     * the bot's powers and others information about the bot.
     *
     * @param \Mars\Network\Server $server The server instance.
     * @param int $id The user id.
     *
     * @return bool
     */
    public function answerTickle(Server $server, $id)
    {
        $server->Socket->write(Xml::build(['z' => ['d' => $id, 'u' => Configure::read('Bot.id') . '_0', 't' => '/a_NF']]));

        return true;
    }
}
