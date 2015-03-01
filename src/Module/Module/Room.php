<?php
namespace Mars\Module\Module;

use Mars\Configure\Configure;
use Mars\Module\ModuleInterface;
use Mars\Network\Server;
use Mars\Utility\Inflector;
use Mars\Utility\Power;
use Mars\Utility\User;

class Room implements ModuleInterface {

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
	public function __construct() {
		$this->_configCommands = Configure::read('Commands');
		$this->_botAdmins = Configure::read('Bot.admin');

		//List of commands and arguments needed. You must put the name of the command in lowercase.
		$commands = [
			'room' => [
				'params' => 2,
				'syntax' => $this->_configCommands['prefix'] . 'Room [Go] [RoomId]'
			],
			'chat' => [
				'params' => 1,
				'syntax' => $this->_configCommands['prefix'] . 'Chat [Info] Optional : [Group Power Name]'
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
	public function onChannelMessage(Server $server, $message) {
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
	protected function _handleQuery(Server $server, $message) {
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
		switch($message->command) {
			case 'room':
				$this->_handleRoom($server, $message);
			break;

			case 'chat':
				$this->_handleChat($server, $message);
			break;
		}
	}

/**
 * Handle the room command.
 *
 * @param \Mars\Network\Server $server The server instance.
 * @param \Mars\Message\Message $message The message instance.
 *
 * @return bool|void
 */
	protected function _handleRoom(Server $server, $message) {
		switch($message->arguments[0]) {
			case 'go':
				$roomId = $message->arguments[1];

				$server->Socket->disconnect();
				$server->startup($roomId);
			break;

			default:
				$server->ModuleManager->message('Unknown command. Syntax: ' . $this->_commands[$message->command]['syntax']);
		}
	}

/**
 * Handle the chat command.
 *
 * @param \Mars\Network\Server $server The server instance.
 * @param \Mars\Message\Message $message The message instance.
 *
 * @return bool|void
 */
	protected function _handleChat(Server $server, $message) {
		switch($message->arguments[0]) {
			case 'info':
				if (!isset($message->arguments[1])) {
					$phrase = $this->_getChatInfo($server->Room->roomInfos);

					$server->ModuleManager->message($phrase);
					break;
				}

				$powerId = Power::getPowerIdByName($message->arguments[1]);

				if ($powerId === false || !isset($server->Room->groupPowers['g' . $powerId])) {
					$server->ModuleManager->message('This power is not assigned in the room.');
					break;
				}

				$phrase = Power::getPowerInfo($powerId, $server->Room->groupPowers['g' . $powerId]);

				if ($phrase === false) {
					$server->ModuleManager->message('Unable to get the information for the power ' . Inflector::camelize($message->arguments[1]) . '.');
					break;
				}

				$server->ModuleManager->message($phrase);
			break;

			default:
				$server->ModuleManager->message('Unknown command. Syntax: ' . $this->_commands[$message->command]['syntax']);
		}
	}

/**
 * Get information about the room.
 *
 * @param array $info The information about the room.
 *
 * @return string
 */
	protected function _getChatInfo(array $info = []) {
		$phrase = 'Chat info : ';

		$background = (!empty($info['background'])) ? $info['background'] : 'None';
		$phrase .= 'Background : ' . $background;

		$language = (!empty($info['language'])) ? $info['language'] : 'None';
		$phrase .= ', Language : ' . $language;

		$radio = (!empty($info['radio'])) ? $info['radio'] : 'None';
		$phrase .= ', Radio : ' . $radio;

		$color = (!empty($info['color'])) ? $info['color'] : 'None';
		$phrase .= ', Color : ' . substr($color, 1);

		return $phrase;
	}
}
