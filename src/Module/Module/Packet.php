<?php
namespace Mars\Module\Module;

use Mars\Configure\Configure;
use Mars\Module\ModuleInterface;
use Mars\Network\Server;
use Mars\Utility\User;

class Packet implements ModuleInterface
{
    /**
     * Commands configuration.
     *
     * @const array
     */
    protected $_configCommands = [];

    /**
     * All administrators of the Bot.
     *
     * @var array
     */
    protected $_botAdmins = [];

    /**
     * List of admin commands.
     *
     * @var array
     */
    protected $_commands = [];

    /**
     * Constructor.
     *
     * Set up the configuration.
     */
    public function __construct()
    {
        $this->_configCommands = Configure::read('Commands');
        $this->_botAdmins = Configure::read('Bot.admin');

        //List of commands and arguments needed. You must put the name of the command in lowercase.
        $commands = [
            'packet' => [
                'params' => 1,
                'syntax' => $this->_configCommands['prefix'] . 'Packet [Load|Unload|Reload|Time|Loaded] Optional : [Packet]'
            ]
        ];

        $this->_commands = $commands;
    }

    /**
     * Called when a message is posted in the chat.
     *
     * @param \Mars\Network\Server $server The server instance.
     * @param \Mars\Message\Message $message The message instance.
     *
     * @return bool
     */
    public function onChannelMessage(Server $server, $message)
    {
        //Put the command and all the arguments in lowercase.
        $message->command = strtolower($message->command);
        $message->arguments = array_map('strtolower', $message->arguments);

        //We check if we have the commandCode, and if the command exist,
        //else it's just a simple phrase or an admin command and we do nothing here. :)
        if ($message->commandCode != $this->_configCommands['prefix']) {
            return true;
        } elseif (!isset($this->_commands[$message->command])) {
            return true;
        }

        //Do the handles commands.
        $this->_handleQuery($server, $message);

        return true;
    }

    /**
     * Handles commands.
     *
     * @param \Mars\Network\Server $server The server instance.
     * @param \Mars\Message\Message $message The message instance.
     *
     * @return bool|void
     */
    protected function _handleQuery(Server $server, $message)
    {
        //Verify all required information.
        if (!User::hasPermission($message->userId, $this->_botAdmins)) {
            $server->ModuleManager->message('You are not administrator of the bot.');
            return false;
        } elseif (count($message->arguments) < $this->_commands[$message->command]['params']) {
            //Check if the user has given enough parameters.
            $server->ModuleManager->message('Not enough parameters given. Syntax: ' . $this->_commands[$message->command]['syntax']);
            return false;
        }

        //Handle the command.
        switch ($message->command) {
            case 'packet':
                $this->_handlePacket($server, $message);
                break;
        }
    }

    /**
     * Handle the packet command.
     *
     * @param \Mars\Network\Server $server The server instance.
     * @param \Mars\Message\Message $message The message instance.
     *
     * @return bool|void
     */
    protected function _handlePacket(Server $server, $message)
    {
        if (!isset($message->arguments[1]) && $message->arguments[0] != 'loaded') {
            $server->ModuleManager->message('Not enough parameters given. Syntax: ' . $this->_commands[$message->command]['syntax']);

            return false;
        }

        switch ($message->arguments[0]) {
            case 'load':
                //Load the Packet.
                $packet = $server->PacketManager->load($message->arguments[1]);

                switch ($packet) {
                    //AlreadyLoaded
                    case 'AL':
                        $server->ModuleManager->message('The Packet [' . $message->arguments[1] . '] is already loaded.');
                        break;

                    //Loaded
                    case 'L':
                        $server->ModuleManager->message('Packet [' . $message->arguments[1] . '] loaded successfully.');
                        break;

                    //NotFound
                    case 'NF':
                        $server->ModuleManager->message('The Packet [' . $message->arguments[1] . '] was not found.');
                        break;
                }
                break;

            case 'unload':
                //Unload the Packet.
                $packet = $server->PacketManager->unload($message->arguments[1]);

                //AlreadyUnloaded
                if ($packet === 'AU') {
                    $server->ModuleManager->message('The Packet [' . $message->arguments[1] . '] is already unloaded or doesn\'t exist.');
                } else {
                    $server->ModuleManager->message('Packet [' . $message->arguments[1] . '] unloaded successfully.');
                }
                break;

            case 'reload':
                //Check if we must reload all Packets.
                if ($message->arguments[1] == "all") {
                    //Get the list of the loaded Packets.
                    $loadedPackets = $server->PacketManager->getLoadedPackets();

                    //For each Packets, we reload it.
                    foreach ($loadedPackets as $packet) {
                        $this->_reloadPacket($server, $packet);

                        //To avoid spam.
                        usleep(500000);
                    }

                    break;
                }

                //Else there is just one Packet to reload.
                $this->_reloadPacket($server, $message->arguments[1]);
                break;

            case 'time':
                //Get the UNIX time.
                $time = $server->PacketManager->timeLoaded($message->arguments[1]);

                //If $time is false, that mean the Packet is not loaded and/or doesn't exist.
                if ($time === false) {
                    $server->ModuleManager->message('This Packet is not loaded.');
                    break;
                }

                $server->ModuleManager->message('The Packet is loaded since : ' . date("H:i:s d/m/Y", $time) . '.');
                break;

            case 'loaded':
                //Get the loaded Packets and implode the array as a string.
                $packets = $server->PacketManager->getLoadedPackets();
                $packets = implode(", ", $packets);

                $server->ModuleManager->message('Packets loaded : ' . $packets . '.');
                break;

            default:
                $server->ModuleManager->message('Unknown command. Syntax: ' . $this->_commands[$message->command]['syntax']);
        }
    }

    /**
     * Function to reload a Packet and send the response.
     *
     * @param \Mars\Network\Server $server The server instance.
     * @param string $packet The packet to reload.
     *
     * @return bool
     */
    protected function _reloadPacket(Server $server, $packet)
    {
        $packetStatus = $server->PacketManager->reload($packet);

        switch ($packetStatus) {
            //AlreadyUnloaded
            case 'AU':
                $server->ModuleManager->message('The Packet [' . $packet . '] doesn\'t exist and cannot be reloaded.');
                break;

            //AlreadyLoaded
            case 'AL':
                $server->ModuleManager->message('The Packet [' . $packet . '] is already loaded.');
                break;

            //Loaded
            case 'L':
                $server->ModuleManager->message('Packet [' . $packet . '] reloaded successfully.');
                break;

            //NotFound
            case 'NF':
                $server->ModuleManager->message('Failed to reload the Packet [' . $packet . '].');
                break;
        }

        return true;
    }
}
