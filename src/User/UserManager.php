<?php
namespace Mars\User;

use ArrayAccess;
use Countable;
use Mars\Utility\Inflector;
use Mars\Utility\User;

class UserManager implements ArrayAccess, Countable {

/**
 * Users connected.
 *
 * @var array
 */
	protected $_users = [];

/**
 * Call destructor to unload all users directly.
 */
	public function __destruct() {
		$this->_users = [];
	}

/**
 * Loads a module into the Framework and prioritize it according to our priority list.
 *
 * @param array $user The user to load
 *
 * @throws \RuntimeException When the class doesn't implement the ModuleInterface.
 *
 * @return string
 */
	public function load(array $user = []) {
		if (!is_array($user)) {
			throw new \RuntimeException("UserManager::load() expects \"$user\" to be an array.");
		}

		if (isset($user['u']['u']) && isset($this->_users[User::parseId($user['u']['u'])])) {
			//Return the message AlreadyLoaded.
			return 'AL';
		}

		$newUser = $this->_createUser($user);

		$this->_users[$newUser['id']] = $newUser;
		debug($this->_users);
		//Return the message Loaded.
		return 'L';
	}

/**
 * Unload an user from the list.
 *
 * @param string $user The user to unload.
 *
 * @return string
 *
 * Can also be a $user['u']['u'].
 */
	public function unload($user) {
		if (is_array($user)) {
			$user = User::parseId($user['l']['u']);
		}

		if (!isset($this->_users[$user])) {

			//Return the message AlreadyUnloaded.
			return 'AU';
		}

		//Remove this user, also calling the __destruct method of it.
		unset($this->_users[$user]);

		//Return the message Unloaded.
		return 'U';
	}

/**
 * Reloads an user by first calling unload and then load.
 *
 * @param int $user The user to reload.
 *
 * @return string
 */
	public function reload($user) {
		$unload = $this->unload($user);

		if ($unload != "U") {
			return $unload;
		}

		return $this->load($user);
	}

/**
 * Returns the time when an user was loaded or false if we don't have it.
 *
 * @param int $user The user to check the time.
 *
 * @return false|int
 */
	public function timeLoaded($user) {
		if (!isset($this->_users[$user])) {
			return false;
		}

		return $this->_users[$user]['loaded'];
	}

/**
 * Returns if an user has been modified or -1 if we do not have him.
 *
 * @param int $user The user to check.
 *
 * @return bool
 */
	public function isModified($user) {
		if (!isset($this->_users[$user])) {
			return -1;
		}

		return $this->_users[$user]['modified'];
	}

/**
 * Returns an array with id of all loaded users.
 *
 * @return array
 */
	public function getLoadedUsers() {
		return $this->_users;
	}

/**
 * Create the new user with his information.
 *
 * @param array $user The user to create.
 *
 * @return array The user created.
 */
	protected function _createUser(array $user = []) {
		$user['u']['u'] = User::parseId($user['u']['u']);

		$status = strstr($user['u']['n'], '##');
		if ($status != false) {
			$status = substr($status, 2);
		}

		$newUser = [
			'id' => $user['u']['u'],
			'name' => $user['u']['n'],
			'registeredName' => isset($user['u']['N']) ? $user['u']['N'] : '',
			'avatar' => $user['u']['a'],
			'homepage' => $user['u']['h'],
			'status' => $status != false ? $status : false,
			'rank' => isset($user['u']['f']) ? User::fToRank($user['u']['f']) : 'guest',
			'loaded' => time(),
			'modified' => false
		];

		return $newUser;
	}

/**
 * Returns the numbers of users loaded.
 *
 * @return int
 */
	public function count() {
		return count($this->_users);
	}

/**
 * Returns the array of the loaded user if we have him, or false if we don't have him.
 *
 * @param int $user The user to get.
 *
 * @return bool|array
 */
	public function offsetGet($user) {
		if (!isset($this->_users[$user])) {
			return false;
		}

		return $this->_users[$user];
	}

/**
 * Check if we have loaded a certain user.
 *
 * @param int $user The user to check.
 *
 * @return bool
 */
	public function offsetExists($user) {
		return isset($this->_users[$user]);
	}

/**
 * Creates a new User in our list.
 *
 * @param string $offset The offset of the user.
 * @param array $user The user to create.
 *
 * @throws \RuntimeException
 *
 * @return bool
 */
	public function offsetSet($offset, $user) {
		if (!is_array($user)) {
			throw new \RuntimeException("UserManager::offsetSet() expects \"$user\" to be an array.");
		}

		$newUser = $this->_createUser($user);

		$this->_users[$offset] = $newUser;

		return true;
	}

/**
 * Unload an User, this is basically the same as unload().
 *
 * @param int $user The module to unlod.
 *
 * @return bool
 */
	public function offsetUnset($user) {
		if (!isset($this->_users[$user])) {
			return true;
		}

		unset($this->_users[$user]);

		return true;
	}
}
