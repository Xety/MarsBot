<?php
namespace Mars\Utility;

use Mars\Configure\Configure;
use Mars\Network\Http\Client;

class Xavi
{
    /**
     * Get a xavi of an user by his id.
     *
     * @param int $id The user id.
     *
     * @return false|string
     */
    public static function get($id)
    {
        if (!is_numeric($id)) {
            return false;
        }

        $http = new Client();
        $response = $http->get('http://xat.com/json/xavi/get.php', ['u' => $id]);

        return $response->body;
    }

    /**
     * Save a xavi for the bot.
     *
     * @param string $xavi The xavi to save.
     * @param array $infos The informations needed to save the xavi.
     *
     * @return bool
     */
    public static function post($xavi, $infos = [])
    {
        $http = new Client();
        $response = $http->post(
            'http://xat.com/json/xavi/put.php',
            [
                's' => 60,
                'au' => 'undefined',
                't' => $infos['y']['t'],
                'j' => 'undefined',
                'u' => Configure::read('Bot.id'),
                'k' => $infos['y']['I'],
                'v' => 'undefined',
                'i' => $infos['y']['i'],
                'xavi' => $xavi
            ],
            [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded'
                ]
            ]
        );

        if ($response->isOk() && $response->body == "OK") {
            return true;
        }

        return false;
    }
}
