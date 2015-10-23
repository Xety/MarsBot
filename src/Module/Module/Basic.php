<?php
namespace Mars\Module\Module;

use Mars\Configure\Configure;
use Mars\Module\ModuleInterface;
use Mars\Network\Server;

class Basic implements ModuleInterface
{
    /**
     * Commands configuration
     *
     * @var array
     */
    protected $_configCommands = [];

    /**
     * List of basic commands
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

        $prefix = $this->_configCommands['prefix'];

        //List of commands and arguments needed. You must put the name of the command in lowercase.
        $commands = [
            'say' => [
                'params' => 1,
                'syntax' => $prefix . 'Say [Message]'
            ],
            'info' => [
                'params' => 0,
                'syntax' => $prefix . 'Info'
            ],
            'version' => [
                'params' => 0,
                'syntax' => $prefix . 'Version'
            ],
            'time' => [
                'params' => 0,
                'syntax' => $prefix . 'Time'
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
     * @return false|void
     */
    protected function _handleQuery(Server $server, $message)
    {
        //Verify required information.
        if (count($message->arguments) < $this->_commands[$message->command]['params']) {
            //Check if the user has given enough parameters.
            $server->ModuleManager->message('Not enough parameters given. Syntax: ' . $this->_commands[$message->command]['syntax']);
            return false;
        }

        //Handle the command.
        switch ($message->command) {
            case 'say':
                $server->ModuleManager->message($message->parts[1]);

                break;

            case 'info':
                $server->ModuleManager->message('I am developed with the Mars Framework.(https://github.com/Xety/MarsBot) The developer is : Mars(1000069).');

                break;

            case 'version':
                $server->ModuleManager->message('The current version is : ' . Configure::version());

                break;

            case 'time':
                $seconds = floor(microtime(true) - TIME_START);
                $start = new \DateTime("@0");
                $end = new \DateTime("@$seconds");
                $server->ModuleManager->message('I\'m running since ' . $start->diff($end)->format('%a days, %h hours, %i minutes and %s seconds.'));

                break;
        }
    }
}
