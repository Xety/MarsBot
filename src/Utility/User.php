<?php
namespace Mars\Utility;

class User {

/**
 * Parse the id to get only the user id.
 *
 * @param string $id The id to parse.
 *
 * @return int
 */
	public static function parseId($id) {
		if (substr_count($id, '_') >= 1) {
			$id = substr($id, 0, strpos($id, '_'));
		}

		return (int)$id;
	}

/**
 * Checks if the given user has permission to perform an action.
 *
 * @param int $user The user id to check.
 * @param array $admins Admins of the bot.
 *
 * @return bool
 */
	public static function hasPermission($user, array $admins = []) {
		return in_array($user, $admins);
	}

/**
 * Get the rank name by the rank id.
 *
 * @param string $rank The rank to get the name.
 *
 * @return string
 */
	public static function fToRank($rank) {
		$rank = static::parseId($rank);

		if ($rank == -1) {
			return 'guest';
		}

		if ((16 & $rank)) {
			return 'banned';
		}

		if ((1 & $rank) && (2 & $rank)) {
			return 'member';
		}

		if ((4 & $rank)) {
			return 'owner';
		}

		if ((32 & $rank) && (1 & $rank) && !(2 & $rank)) {
			return 'main';
		}

		if (!(1 & $rank) && !(2 & $rank)) {
			return 'guest';
		}
		if ((16 & $rank)) {
			return 'banned';
		}

		if ((2 & $rank) && !(1 & $rank)) {
			return 'mod';
		}

		return 'guest';
	}
}
