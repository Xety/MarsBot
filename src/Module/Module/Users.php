<?php
namespace Mars\Module\Module;

use Mars\Configure\Configure;
use Mars\Module\ModuleInterface;
use Mars\Network\Server;
use Mars\Utility\User;

class Users implements ModuleInterface {

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
			'user' => [
				'params' => 1,
				'syntax' => $this->_configCommands['prefix'] . 'User [Time|Loaded|Count] Optional : [UserId]'
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
			case 'user':
				$this->_handleUser($server, $message);
			break;
		}
	}

/**
 * Handle the user command.
 *
 * @param \Mars\Network\Server $server The server instance.
 * @param \Mars\Message\Message $message The message instance.
 *
 * @return bool|void
 */
	protected function _handleUser(Server $server, $message) {
		if (!isset($message->arguments[1]) && $message->arguments[0] != 'loaded' && $message->arguments[0] != 'count') {
			$server->ModuleManager->message('Not enough parameters given. Syntax: ' . $this->_commands[$message->command]['syntax']);

			return false;
		}

		switch($message->arguments[0]) {
			case 'count':
				//Count the number of Users in the room.
				$users = $server->UserManager->count();
				$server->ModuleManager->message("This is $users user(s) in this room.");
			break;

			case 'time':
				//Get the UNIX time.
				$time = $server->UserManager->timeLoaded($message->arguments[1]);

				//If $time is false, that mean the User is not loaded and/or doesn't exist.
				if ($time === false) {
					$server->ModuleManager->message('This User is not loaded.');
					break;
				}

				$server->ModuleManager->message('The User is loaded since : ' . date("H:i:s d/m/Y", $time) . '.');
			break;

			case 'loaded':
				//Get the loaded Users and implode the array as a string.
				$users = $server->UserManager->getLoadedUsers();

				$list = [];

				foreach ($users as $user) {
					$displayName = $user['registeredName'] . ' (' . $user['id'] . ')';
					array_push($list, $displayName);
				}

				$users = implode(", ", $list);

				$server->ModuleManager->message('Users loaded : ' . $users . '.');
			break;

			default:
				$server->ModuleManager->message('Unknown command. Syntax: ' . $this->_commands[$message->command]['syntax']);
		}
	}
}
