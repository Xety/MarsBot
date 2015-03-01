<?php
namespace Mars\Packet;

use ArrayAccess;
use Countable;
use DirectoryIterator;
use Mars\Core\App;
use Mars\Utility\Inflector;

class PacketManager implements ArrayAccess, Countable {

/**
 * Constant that can be returned by packets to indicate to halt noticing further packets.
 *
 * @var int
 */
	const STOP = -1;

/**
 * Packets that have priority over other packets.
 *
 * @var array
 */
	protected $_priorityList = [];

/**
 * Loaded packets.
 *
 * @var array
 */
	protected $_loadedPackets = [];

/**
 * A list of optional arguments that should be passed every call.
 *
 * @var array
 */
	protected $_prefixArguments = [];

/**
 * Constructor, loads all Packets in the Packet directory.
 *
 * @param array $priorities The packets to load in priority.
 *
 * @return void
 */
	public function __construct(array $priorities = []) {
		$this->_priorityList = $priorities;
		$files = new DirectoryIterator(PACKET_DIR);

		foreach ($files as $file) {

			$filename = $file->getFilename();

			if ($file->isDot() || $file->isDir() || $filename[0] == '.') {
				// Ignore hidden files and directories.
				continue;
			} elseif ($file->isFile() && substr($filename, -4) != '.php') {
				continue;
			} else {
				try {

					$this->load(substr($filename, 0, -4));
				} catch (Exception $e) {
					//Error while loading packet.
					debug('Error while loading packet : ' . $e->getMessage());
				}
			}
		}
	}

/**
 * Call destructors to unload all packets directly.
 */
	public function __destruct() {
		$this->_loadedPackets = [];
	}

/**
 * List of arguments that should be passed on to Packets.
 *
 * @param array $arguments List of arguments.
 *
 * @return bool
 */
	public function addPrefixArgument(array $arguments = []) {
		$this->_prefixArguments = array_merge($this->_prefixArguments, $arguments);

		return true;
	}

/**
 * Calls a given method on all packets with the arguments passed to this method.
 * The loop will halt when the method returns the STOP constant, indicating all
 * work is done.
 *
 * @param string $method The method to check.
 * @param array  $arguments The arguments to pass to the function.
 *
 * @return bool
 */
	public function __call($method, array $arguments) {
		//Add out predefined prefix arguments to the total list.
		$arguments = array_merge($this->_prefixArguments, $arguments);
		foreach ($this->_loadedPackets as $packet) {

			//Check if the packet has the method.
			if (!method_exists($packet['object'], $method)) {
				continue;
			}

			//Check if we should stop calling packets.
			if (call_user_func_array([$packet['object'], $method], $arguments) === self::STOP) {
				break;
			}
		}

		return true;
	}

/**
 * Loads a packet into the Framework and prioritize it according to our priority list.
 *
 * @param string $packet Filename in the PACKET_DIR we want to load.
 *
 * @throws \RuntimeException When the class doesn't implement the PacketInterface.
 *
 * @return string
 */
	public function load($packet) {
		$packet = Inflector::camelize($packet);

		if (isset($this->_loadedPackets[$packet])) {
			//Return the message AlreadyLoaded.
			return 'AL';
		} elseif (!file_exists(PACKET_DIR . DS . $packet . '.php')) {
			Debug('Class file for ' . $packet . ' could not be found.');

			//Return NotFound.
			return 'NF';
		}

		//Check if this class already exists.
		$path = PACKET_DIR . DS . $packet . '.php';
		$className = App::className($packet, 'Packet/Packet');

		if (class_exists($className, false)) {
			//Check if the user has the runkit extension.
			if (function_exists('runkit_import')) {
				runkit_import($path, RUNKIT_IMPORT_OVERRIDE | RUNKIT_IMPORT_CLASSES);
			} else {

				//Here, we load the file's contents first, then use preg_replace() to replace the original class-name with a random one.
				//After that, we create a copy and include it.
				$newClass = $packet . '_' . md5(mt_rand() . time());
				$contents = preg_replace(
					"/(class[\s]+?)" . $packet . "([\s]+?implements[\s]+?PacketInterface[\s]+?{)/",
					"\\1" . $newClass . "\\2",
					file_get_contents($path)
				);

				$name = tempnam(TMP_PACKET_DIR, 'packet_');
				file_put_contents($name, $contents);
				require_once $name;
				$className = App::className($newClass, 'Packet/Packet');
				unlink($name);
			}
		} else {
			require_once $path;
		}

		$objectPacket = new $className();
		$new = [
			'object' => $objectPacket,
			'loaded' => time(),
			'name' => $className,
			'modified' => (isset($contents) ? true : false)
		];

		//Check if this packet implements our default interface.
		if (!$objectPacket instanceof PacketInterface) {
			throw new \RuntimeException(sprintf('PacketManager::offsetSet() expects "%s" to be an instance of PacketInterface.', $className));
		}

		//Prioritize.
		if (in_array($packet, $this->_priorityList)) {
			//So, here we reverse our list of loaded packets, so that prioritized packets will be the last ones,
			//then, we add the current prioritized packets to the array and reverse it again.
			$temp = array_reverse($this->_loadedPackets, true);
			$temp[$packet] = $new;
			$this->_loadedPackets = array_reverse($temp, true);
		} else {
			$this->_loadedPackets[$packet] = $new;
		}

		//Return the message Loaded.
		return 'L';
	}

/**
 * Unload a packet from the Framework.
 *
 * @param string $packet Packet to unload.
 *
 * @return string
 */
	public function unload($packet) {
		$packet = Inflector::camelize($packet);

		if (!isset($this->_loadedPackets[$packet])) {

			//Return the message AlreadyUnloaded.
			return 'AU';
		}

		//Remove this packet, also calling the __destruct method of it.
		unset($this->_loadedPackets[$packet]);

		//Return the message Unloaded.
		return 'U';
	}

/**
 * Reloads a packet by first calling unload and then load.
 *
 * @param string $packet The packet to reload.
 *
 * @return string
 */
	public function reload($packet) {
		$packet = Inflector::camelize($packet);

		$unload = $this->unload($packet);

		if ($unload != "U") {
			return $unload;
		}

		return $this->load($packet);
	}

/**
 * Returns the time when a packet was loaded or false if we don't have it.
 *
 * @param string $packet The packet to check the time.
 *
 * @return false|int
 */
	public function timeLoaded($packet) {
		$packet = Inflector::camelize($packet);

		if (!isset($this->_loadedPackets[$packet])) {
			return false;
		}

		return $this->_loadedPackets[$packet]['loaded'];
	}

/**
 * Returns if a packet has been modified or -1 if we do not have it
 *
 * @param string $packet The packet to check.
 *
 * @return bool
 */
	public function isModified($packet) {
		$packet = Inflector::camelize($packet);

		if (!isset($this->_loadedPackets[$packet])) {
			return -1;
		}

		return $this->_loadedPackets[$packet]['modified'];
	}

/**
 * Returns an array with names of all loaded packets, sorted on their priority.
 *
 * @return array
 */
	public function getLoadedPackets() {
		return array_keys($this->_loadedPackets);
	}

/**
 * Returns the numbers of packets loaded.
 *
 * @return int
 */
	public function count() {
		return count($this->_loadedPackets);
	}

/**
 * Returns instance of a loaded packet if we have it, or false if we don't have it.
 *
 * @param string $packet The packet to get.
 *
 * @return bool|object
 */
	public function offsetGet($packet) {
		$packet = Inflector::camelize($packet);

		if (!isset($this->_loadedPackets[$packet])) {
			return false;
		}

		return $this->_loadedPackets[$packet]['object'];
	}

/**
 * Check if we have loaded a certain packet.
 *
 * @param string $packet The packet to check.
 *
 * @return bool
 */
	public function offsetExists($packet) {
		$packet = Inflector::camelize($packet);

		return isset($this->_loadedPackets[$packet]);
	}

/**
 * Creates a new Packet in our list.
 *
 * @param string $offset The offset of the moddule.
 * @param object $packet The packet to create.
 *
 * @throws \RuntimeException
 *
 * @return bool
 */
	public function offsetSet($offset, $packet) {
		if (!$packet instanceof PacketInterface) {
			throw new \RuntimeException(sprintf('PacketManager::offsetSet() expects "%s" to be an instance of PacketInterface.', $packet));
		}

		$newPacket = [
			'object' => $packet,
			'loaded' => time(),
			'name' => get_class($packet),
			'modified' => false
		];

		if (in_array($offset, $this->_priorityList)) {
			$temp = array_reverse($this->_loadedPackets, true);
			$temp[$offset] = $newPacket;
			$this->_loadedPackets = array_reverse($temp, true);
		} else {
			$this->_loadedPackets[$offset] = $newPacket;
		}

		return true;
	}

/**
 * Unload a Packet, this is basically the same as unload().
 *
 * @param string $packet The packet to unlod.
 *
 * @return bool
 */
	public function offsetUnset($packet) {
		if (!isset($this->_loadedPackets[$packet])) {
			return true;
		}

		unset($this->_loadedPackets[$packet]);

		return true;
	}
}
