<?php
namespace Mars\Module\Module;

use Mars\Configure\Configure;
use Mars\Module\ModuleInterface;
use Mars\Network\Http\Client;
use Mars\Network\Server;
use Mars\Utility\Xavi;

class Developer implements ModuleInterface
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
            'dev' => [
                'params' => 2,
                'syntax' => $prefix . 'Dev [Info] [Memory|Server|Files]'
            ],
            'xavi' => [
                'params' => 2,
                'syntax' => $prefix . 'Xavi [Use] [UserId]'
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
        //Verify required information.
        if (count($message->arguments) < $this->_commands[$message->command]['params']) {
            //Check if the user has given enough parameters.
            $server->ModuleManager->message('Not enough parameters given. Syntax: ' . $this->_commands[$message->command]['syntax']);
            return false;
        }

        //Handle the command.
        switch ($message->command) {
            case 'dev':
                $this->_handleDev($server, $message);
                break;

            case 'xavi':
                $this->_handleXavi($server, $message);
                break;
        }
    }

    /**
     * Handle the xavi command.
     *
     * @param \Mars\Network\Server $server The server instance.
     * @param \Mars\Message\Message $message The message instance.
     *
     * @return bool|void
     */
    protected function _handleXavi(Server $server, $message)
    {
        switch ($message->arguments[0]) {
            case 'use':
                $xavi = Xavi::get($message->arguments[1]);

                if ($xavi === false) {
                    $server->ModuleManager->message('Error to get the xavi of the user ' . $message->arguments[1]);
                    break;
                }

                $result = Xavi::post($xavi, $server->Room->loginInfos);

                if ($result === false) {
                    $server->ModuleManager->message('Error to save the xavi.');
                    break;
                }

                $server->ModuleManager->message('The xavi has been saved successfully !');
                break;

            default:
                $server->ModuleManager->message('Unknown command. Syntax: ' . $this->_commands[$message->command]['syntax']);
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
    protected function _handleDev(Server $server, $message)
    {
        switch ($message->arguments[0]) {
            case 'info':
                switch ($message->arguments[1]) {
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

                    case 'files':
                        $files = count(get_included_files());

                        $server->ModuleManager->message('There\'s ' . $files . ' files loaded in memory.');
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
    public function _getServerInfo()
    {
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
        } elseif (PHP_OS == 'Linux') {
            $version = explode('.', PHP_VERSION);
            $phrase = 'PHP Version : ' . $version[0] . '.' . $version[1];


            // File that has it
            $file = '/proc/cpuinfo';
            // Not there?
            if (!is_file($file) || !is_readable($file)) {
                return 'Unknown';
            }

            // Get contents
            $contents = trim(file_get_contents($file));

            // Lines
            $lines = explode("\n", $contents);

            // Holder for current CPU info
            $cpu = [];

            // Go through lines in file
            $numLines = count($lines);

            for ($i = 0; $i < $numLines; $i++) {
                $line = explode(':', $lines[$i], 2);

                if (!array_key_exists(1, $line)) {
                    continue;
                }

                $key = trim($line[0]);
                $value = trim($line[1]);

                // What we want are MHZ, Vendor, and Model.
                switch ($key) {
                    // CPU model
                    case 'model name':
                    case 'cpu':
                    case 'Processor':
                        $cpu['Model'] = $value;
                        break;
                    // Speed in MHz
                    case 'cpu MHz':
                        $cpu['MHz'] = $value;
                        break;
                    case 'Cpu0ClkTck': // Old sun boxes
                        $cpu['MHz'] = hexdec($value) / 1000000;
                        break;
                    // Brand/vendor
                    case 'vendor_id':
                        $cpu['Vendor'] = $value;
                        break;
                    // CPU Cores
                    case 'cpu cores':
                        $cpu['Cores'] = $value;
                        break;
                }
            }

            $phrase .= " Processor : " . $cpu['Model'];

            return $phrase;
        } else {
            return 'This function work only on a Windows system. :(';
        }
    }
}
