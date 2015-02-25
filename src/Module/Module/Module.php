<?php
namespace Mars\Module\Module;

use Mars\Configure\Configure;
use Mars\Module\ModuleInterface;
use Mars\Network\Server;
use Mars\Utility\User;

class Module implements ModuleInterface {

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

	protected $_aiStarted = false;

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
			'botedit' => [
				'params' => 2,
				'syntax' => $this->_configCommands['prefix'] . 'BotEdit [Name|Avatar|Home|Admin] [Text|Image|Link||Add|Remove|Show] [UserId]'
			],
			'aistart' => [
				'params' => 0,
				'syntax' => $this->_configCommands['prefix'] . 'AIStart'
			],
			'aistop' => [
				'params' => 0,
				'syntax' => $this->_configCommands['prefix'] . 'AIStop'
			],
			'module' => [
				'params' => 1,
				'syntax' => $this->_configCommands['prefix'] . 'Module [Load|Unload|Reload|Time|Loaded] Optional : [Module]'
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
			if ($this->_aiStarted === true) {
				$response = file_get_contents("http://localhost/NozeAI/chatbot/conversation_start.php?bot_id=1&convo_id=1&say=" . urlencode($message->raw));
				$response = json_decode($response, true);

				$server->ModuleManager->message($response['botsay']);
			}
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
			case 'aistart':
				if ($this->_aiStarted === false) {
					$this->_aiStarted = true;
				}

				$server->ModuleManager->message('Artificial Intelligence started !');
			break;

			case 'aistop':
				if ($this->_aiStarted === true) {
					$this->_aiStarted = false;
				}

				$server->ModuleManager->message('Artificial Intelligence has been stoped !');
			break;

			case 'module':
				$this->_handleModule($server, $message);
			break;
		}
	}

/**
 * Handle the module command.
 *
 * @param \Mars\Network\Server $server The server instance.
 * @param \Mars\Message\Message $message The message instance.
 *
 * @return bool|void
 */
	protected function _handleModule(Server $server, $message) {
		if (!isset($message->arguments[1]) && $message->arguments[0] != 'loaded') {
			$server->ModuleManager->message('Not enough parameters given. Syntax: ' . $this->_commands[$message->command]['syntax']);

			return false;
		}

		switch($message->arguments[0]) {
			case 'load':
				//Load the Module.
				$module = $server->ModuleManager->load($message->arguments[1]);

				switch($module) {
					//AlreadyLoaded
					case 'AL':
						$server->ModuleManager->message('The Module [' . $message->arguments[1] . '] is already loaded.');
					break;

					//Loaded
					case 'L':
						$server->ModuleManager->message('Module [' . $message->arguments[1] . '] loaded successfully.');
					break;

					//NotFound
					case 'NF':
						$server->ModuleManager->message('The Module [' . $message->arguments[1] . '] was not found.');
					break;
				}
			break;

			case 'unload':
				//Unload the Module.
				$module = $server->ModuleManager->unload($message->arguments[1]);

				//AlreadyUnloaded
				if ($module === 'AU') {
					$server->ModuleManager->message('The Module [' . $message->arguments[1] . '] is already unloaded or doesn\'t exist.');
				} else {
					$server->ModuleManager->message('Module [' . $message->arguments[1] . '] unloaded successfully.');
				}
			break;

			case 'reload':
				//Check if we must reload all Modules.
				if ($message->arguments[1] == "all") {
					//Get the list of the loaded Modules.
					$loadedModules = $server->ModuleManager->getLoadedModules();

					//For each Modules, we reload it.
					foreach ($loadedModules as $module) {
						$this->_reloadModule($server, $module);

						//To avoid spam.
						usleep(500000);
					}

					break;
				}

				//Else there is just one Module to reload.
				$this->_reloadModule($server, $message->arguments[1]);
			break;

			case 'time':
				//Get the UNIX time.
				$time = $server->ModuleManager->timeLoaded($message->arguments[1]);

				//If $time is false, that mean the Module is not loaded and/or doesn't exist.
				if ($time === false) {
					$server->ModuleManager->message('This Module is not loaded.');
					break;
				}

				$server->ModuleManager->message('The Module is loaded since : ' . date("H:i:s d/m/Y", $time) . '.');
			break;

			case 'loaded':
				//Get the loaded Modules and implode the array as a string.
				$modules = $server->ModuleManager->getLoadedModules();
				$modules = implode(", ", $modules);

				$server->ModuleManager->message('Modules loaded : ' . $modules . '.');
			break;

			default:
				$server->ModuleManager->message('Unknown command. Syntax: ' . $this->_commands[$message->command]['syntax']);
		}
	}

/**
 * Function to reload a Module and send the response.
 *
 * @param \Mars\Network\Server $server The server instance.
 * @param string $module The module to reload.
 *
 * @return bool
 */
	protected function _reloadModule(Server $server, $module) {
		$moduleStatus = $server->ModuleManager->reload($module);

		switch($moduleStatus) {
			//AlreadyUnloaded
			case 'AU':
				$server->ModuleManager->message('The Module [' . $module . '] doesn\'t exist and cannot be reloaded.');
			break;

			//AlreadyLoaded
			case 'AL':
				$server->ModuleManager->message('The Module [' . $module . '] is already loaded.');
			break;

			//Loaded
			case 'L':
				$server->ModuleManager->message('Module [' . $module . '] reloaded successfully.');
			break;

			//NotFound
			case 'NF':
				$server->ModuleManager->message('Failed to reload the Module [' . $module . '].');
			break;
		}

		return true;
	}
}
