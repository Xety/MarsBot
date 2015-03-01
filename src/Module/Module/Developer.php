<?php
namespace Mars\Module\Module;

use Mars\Configure\Configure;
use Mars\Module\ModuleInterface;
use Mars\Network\Http\Client;
use Mars\Network\Server;

class Developer implements ModuleInterface {

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
	public function __construct() {
		$this->_configCommands = Configure::read('Commands');

		$prefix = $this->_configCommands['prefix'];

		//List of commands and arguments needed. You must put the name of the command in lowercase.
		$commands = [
			'dev' => [
				'params' => 2,
				'syntax' => $prefix . 'Dev [Info] [Memory|Server]'
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
		//Verify required information.
		if (count($message->arguments) < $this->_commands[$message->command]['params']) {
			//Check if the user has given enough parameters.
			$server->ModuleManager->message('Not enough parameters given. Syntax: ' . $this->_commands[$message->command]['syntax']);
			return false;
		}

		//Handle the command.
		switch($message->command) {
			case 'dev':
				$this->_handleDev($server, $message);
			break;
		}
	}

/**
 * Handle the dev command.
 *
 * @param \Mars\Network\Server $server The server instance.
 * @param \Mars\Message\Message $message The message instance.
 *
 * @return bool|void
 */
	protected function _handleDev(Server $server, $message) {
		switch($message->arguments[0]) {
			case 'info':
				switch($message->arguments[1]) {
					case 'memory':
						$memoryKo = round(memory_get_usage(true) / 1024);
						$memoryMo = round($memoryKo / 1024);

						$server->ModuleManager->message('Memory used : ' . $memoryKo . 'Ko (' . $memoryMo . 'Mo)');
					break;

					case 'server':
						//Work only on windows.
						$serverInfo = $this->_getServerInfo();

						$server->ModuleManager->message($serverInfo);
					break;
				}
			break;

			default:
				$server->ModuleManager->message('Unknown command. Syntax: ' . $this->_commands[$message->command]['syntax']);
		}
	}

/**
 * Get information about the sserver.
 *
 * @return string
 */
	protected function _getServerInfo() {
		if (stristr(PHP_OS, 'win')) {

			$wmi = new \COM("Winmgmts://");
			$processor = $wmi->execquery("SELECT Name FROM Win32_Processor");
			$physicalMemory = $wmi->execquery("SELECT Capacity FROM Win32_PhysicalMemory");
			$baseBoard = $wmi->execquery("SELECT * FROM Win32_BaseBoard");
			$threads = $wmi->execquery("SELECT * FROM Win32_Process");
			$disks = $wmi->execquery("SELECT * FROM Win32_DiskQuota");

			foreach ($processor as $wmiProcessor) {
				$name = $wmiProcessor->Name;
			}

			$memory = 0;
			foreach ($physicalMemory as $wmiPhysicalMemory) {
				$memory += $wmiPhysicalMemory->Capacity;
			}

			$memoryMo = ($memory / 1024 / 1024);
			$memoryGo = $memoryMo / 1024;

			foreach ($baseBoard as $wmiBaseBoard) {
				$boardName = $wmiBaseBoard->Product;
				$boardName .= ' ' . $wmiBaseBoard->Manufacturer;
			}

			$phrase = 'Server Information : ';

			$phrase .= '
Processor : ' . $name;

			$phrase .= '
Memory : ' . round($memoryMo, 2) . 'Mo (' . round($memoryGo, 2) . 'Go)';

			$phrase .= '
MotherBoard : ' . $boardName;

			$phrase .= '
Threads Information :';

			$threadsCount = 0;
			$totalMemoryUsed = 0;

			foreach ($threads as $thread) {
				$phrase .= '
	Name : ' . $thread->Name;

				$phrase .= '
	Threads Count : ' . $thread->ThreadCount;

				$totalMemoryUsed += ($thread->WorkingSetSize / 1024 / 1024);
				$memoryKo = ($thread->WorkingSetSize / 1024);
				$memoryMo = $memoryKo / 1024;

				$phrase .= '
	Memory used : ' . round($memoryKo, 2) . 'Ko (' . round($memoryMo, 2) . 'Mo)';

				$ngProcessTime = ($thread->KernelModeTime + $thread->UserModeTime) / 10000000;

				$phrase .= '
	Processor used by the process : ' . round($ngProcessTime, 2);

				$phrase .= '
	ProcessID : ' . $thread->ProcessID . '

';
				$threadsCount += $thread->ThreadCount;
			}

			$phrase .= '
Total Memory Used : ' . round($totalMemoryUsed, 2) . 'Mo' . '(' . round($totalMemoryUsed / 1024, 2) . 'Go)';

			$phrase .= '
Total Threads Count : ' . $threadsCount . ' threads';

			$http = new Client();
			$response = $http->post('http://pastebin.com/api/api_post.php', [
				'api_option' => 'paste',
				'api_dev_key' => Configure::read('Pastebin.apiDevKey'),
				'api_user_key' => '',
				'api_paste_private' => Configure::read('Pastebin.apiPastePrivate'),
				'api_paste_expire_date' => Configure::read('Pastebin.apiPasteExpireDate'),
				'api_paste_code' => $phrase
			]);

			if (substr($response->body, 0, 15) === 'Bad API request') {
				return 'Erreur to post the paste on Pastebin. Error : ' . $response->body;
			}

			$phrase = 'Server info : ' . $response->body;

			return $phrase;

		} else {
			return 'This function work only on a Windows system. :(';
		}
	}
}
