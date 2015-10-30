<?php
namespace Mars\Utility;

use Mars\Configure\Configure;
use Mars\Network\Http;
use Mars\Utility\Inflector;

class Power
{
    protected static $_groupPower = [
        '70' => 'Banished',
        '72' => 'Gkaoani',
        '74' => 'Gline',
        '78' => 'Supporter',
        '80' => 'Gcontrol',
        '82' => 'Sea',
        '84' => 'Blastpro',
        '86' => 'Blastban',
        '90' => 'Bad',
        '94' => 'Blastkick',
        '96' => 'Winter',
        '100' => 'Link',
        '106' => 'Gscol',
        '108' => 'Love',
        '112' => 'Announce',
        '114' => 'Rankpool',
        '116' => 'Animal',
        '120' => 'Events',
        '126' => 'Banpool',
        '130' => 'Gback',
        '134' => 'Snakeban',
        '150' => 'Bot',
        '152' => 'Mazeban',
        '156' => 'Santa',
        '162' => 'Codeban',
        '176' => 'Reverse',
        '180' => 'Gsound',
        '188' => 'Doodlerace',
        '194' => 'Snakerace',
        '206' => 'Lang',
        '220' => 'Vote',
        '224' => 'Hearts',
        '238' => 'Switch',
        '246' => 'Darts',
        '252' => 'Redirect',
        '256' => 'Zwhack',
        '278' => 'Springflix',
        '296' => 'Summerflix',
        '310' => 'Manage',
        '318' => 'Backup'
    ];

    protected static $_rankValue = [
        0 => 'N/A',
        2 => 'Guest',
        3 => 'Temp Member',
        5 => 'Member',
        7 => 'Temp Moderator',
        8 => 'Moderator',
        10 => 'Temp Owner',
        11 => 'Owner',
        14 => 'Main'
    ];

    protected static $_gameType = [
        0 => 'Individual',
        1 => 'Race'
    ];

    /**
     * Get the power id by his name.
     *
     * @param string $name The name of the power.
     *
     * @return int|false
     */
    public static function getPowerIdByName($name = null)
    {
        if (is_null($name)) {
            return false;
        }

        return array_search(Inflector::camelize($name), static::$_groupPower);
    }

    /**
     * Return the information about a power configuration for the room.
     *
     * @param int $id The id of the power.
     * @param array|string $roomInfo The information about the power.
     *
     * @return false|string
     */
    public static function getPowerInfo($id = null, $roomInfo = null)
    {
        if (is_null($id) || !is_numeric($id) || is_null($roomInfo)) {
            return false;
        }

        //If the power is defined, then call the method by the power name.
        if (isset(static::$_groupPower[$id])) {
            $result = static::{'_get' . static::$_groupPower[$id] . 'Info'}($roomInfo);
            return $result;
        }
    }

    /**
     * Calls a given static method with the arguments passed to this method.
     *
     * @param string $method The method to check.
     * @param array  $arguments The arguments to pass to the function.
     *
     * @return bool
     */
    public static function __callStatic($method, array $arguments)
    {
        //Check if the class has the method.
        if (!method_exists('Power', $method)) {
            return false;
        }

        return true;
    }

    /**
     * Get information about the Gcontrol power.
     *
     * @param array $roomInfo An array of the Gcontrol configuration.
     *
     * @return false|string
     */
    protected static function _getGcontrolInfo($roomInfo)
    {
        if (!is_array($roomInfo)) {
            return false;
        }

        $phrase = 'Gcontrol info : ';

        $rank = isset($roomInfo['mg']) ? static::$_rankValue[(int)$roomInfo['mg']] : static::$_rankValue[7];
        $phrase .= '
Make Guest : ' . $rank;

        $type = isset($roomInfo['mb']) ? static::$_rankValue[(int)$roomInfo['mb']] : static::$_rankValue[8];
        $phrase .= '
Make Member : ' . $type;

        $type = isset($roomInfo['mm']) ? static::$_rankValue[(int)$roomInfo['mm']] : static::$_rankValue[11];
        $phrase .= '
Make Moderator : ' . $type;

        $type = isset($roomInfo['kk']) ? static::$_rankValue[(int)$roomInfo['kk']] : static::$_rankValue[7];
        $phrase .= '
Kick : ' . $type;

        $type = isset($roomInfo['bn']) ? static::$_rankValue[(int)$roomInfo['bn']] : static::$_rankValue[7];
        $phrase .= '
Ban : ' . $type;

        $type = isset($roomInfo['ubn']) ? static::$_rankValue[(int)$roomInfo['ubn']] : static::$_rankValue[7];
        $phrase .= '
Unban : ' . $type;

        $type = isset($roomInfo['mbt']) ? $roomInfo['mbt'] : '6';
        $phrase .= '
Mod Max Ban Time : ' . $type;

        $type = isset($roomInfo['obt']) ? ((int)$roomInfo['obt'] === 0) ? 'Forever' : $roomInfo['obt'] : 'Forever';
        $phrase .= '
Owner Max Ban Time : ' . $type;

        $type = isset($roomInfo['ss']) ? static::$_rankValue[(int)$roomInfo['ss']] : static::$_rankValue[10];
        $phrase .= '
Set Scroller : ' . $type;

        $type = isset($roomInfo['lkd']) ? static::$_rankValue[(int)$roomInfo['lkd']] : static::$_rankValue[0];
        $phrase .= '
Must be Locked : ' . $type;

        $type = isset($roomInfo['rgd']) ? static::$_rankValue[(int)$roomInfo['rgd']] : static::$_rankValue[0];
        $phrase .= '
Must be Registered : ' . $type;

        $type = isset($roomInfo['bst']) ? $roomInfo['bst'] : '0';
        $phrase .= '
Preferred Blast : ' . $type;

        $type = isset($roomInfo['prm']) ? static::$_rankValue[(int)$roomInfo['prm']] : static::$_rankValue[5];
        $phrase .= '
Can Promote : ' . $type;

        $type = isset($roomInfo['bge']) ? static::$_rankValue[(int)$roomInfo['bge']] : static::$_rankValue[7];
        $phrase .= '
Barge In : ' . $type;

        $type = isset($roomInfo['mxt']) ? $roomInfo['mxt'] : '10';
        $phrase .= '
Max Toons : ' . $type;

        $type = isset($roomInfo['ads']) ? $roomInfo['ads'] : '0';
        $phrase .= '
Ads Position : ' . $type;

        $type = isset($roomInfo['sme']) ? static::$_rankValue[(int)$roomInfo['sme']] : static::$_rankValue[7];
        $phrase .= '
Silent Member : ' . $type;

        $type = isset($roomInfo['dnc']) ? static::$_rankValue[(int)$roomInfo['dnc']] : static::$_rankValue[14];
        $phrase .= '
Can be Dunced : ' . $type;

        $type = isset($roomInfo['bdg']) ? static::$_rankValue[(int)$roomInfo['bdg']] : static::$_rankValue[10];
        $phrase .= '
Can Award Badge : ' . $type;

        $type = isset($roomInfo['ns']) ? static::$_rankValue[(int)$roomInfo['ns']] : static::$_rankValue[7];
        $phrase .= '
Can Naughty Step : ' . $type;

        $type = isset($roomInfo['yl']) ? static::$_rankValue[(int)$roomInfo['yl']] : static::$_rankValue[7];
        $phrase .= '
Can Yellow Card : ' . $type;

        $type = isset($roomInfo['p']) ? static::$_rankValue[(int)$roomInfo['p']] : static::$_rankValue[10];
        $phrase .= '
Protect Mode : ' . $type;

        $type = isset($roomInfo['pd']) ? $roomInfo['pd'] : '1';
        $phrase .= '
Protect Default : ' . $type;

        $type = isset($roomInfo['pt']) ? $roomInfo['pt'] : '1';
        $phrase .= '
Protect Time (hours) : ' . $type;

        $type = isset($roomInfo['ka']) ? static::$_rankValue[(int)$roomInfo['ka']] : static::$_rankValue[10];
        $phrase .= '
Kick All : ' . $type;

        $type = isset($roomInfo['mft']) ? $roomInfo['mft'] : '4';
        $phrase .= '
Member Flood Trust : ' . $type;

        $type = isset($roomInfo['ft']) ? $roomInfo['ft'] : '50';
        $phrase .= '
Flood Threshold : ' . $type;

        $http = new Http();
        $response = $http->post('http://pastebin.com/api/api_post.php', [
            'api_option' => 'paste',
            'api_dev_key' => Configure::read('Pastebin.apiDevKey'),
            'api_user_key' => '',
            'api_paste_private' => Configure::read('Pastebin.apiPastePrivate'),
            'api_paste_expire_date' => Configure::read('Pastebin.apiPasteExpireDate'),
            'api_paste_code' => $phrase
        ]);

        if (substr($response->getBody(), 0, 15) === 'Bad API request') {
            return 'Erreur to post the paste on Pastebin. Error : ' . $response->body;
        }

        $phrase = 'Gcontrol info : ' . $response->body;

        return $phrase;
    }

    /**
     * Get information about the Bad power.
     *
     * @param string $roomInfo A bad word(s) list.
     *
     * @return false|string
     */
    protected static function _getBadInfo($roomInfo)
    {
        if (!is_string($roomInfo)) {
            return false;
        }

        return 'The bad words list is : ' . implode(', ', explode(',', $roomInfo));
    }

    /**
     * Get information about the DoodleRace power.
     *
     * @param string $roomInfo An array of the DoodleRace configuration.
     *
     * @return string
     */
    protected static function _getDoodleRaceInfo($roomInfo)
    {
        if (!is_array($roomInfo)) {
            return false;
        }

        $displayWhileDrawing = [
            0 => 'Doodles',
            1 => 'Doodles and name',
            2 => 'Nothing'
        ];

        $phrase = 'DoodleRace info : ';

        $rank = isset($roomInfo['rnk']) ? static::$_rankValue[(int)$roomInfo['rnk']] : static::$_rankValue[3];
        $phrase .= 'Controller Rank : ' . $rank;

        $time = isset($roomInfo['dt']) ? $roomInfo['dt'] : '60';
        $phrase .= ', Drawing Time : ' . $time;

        $level = isset($roomInfo['lv']) ? $roomInfo['lv'] : '1';
        $phrase .= ', Level : ' . $level;

        $time = isset($roomInfo['rt']) ? $roomInfo['rt'] : '10';
        $phrase .= ', Results Time : ' . $time;

        $time = isset($roomInfo['st']) ? $roomInfo['st'] : '60';
        $phrase .= ', Rating Time : ' . $time;

        $type = isset($roomInfo['o']) ? $displayWhileDrawing[(int)$roomInfo['o']] : $displayWhileDrawing[0];
        $phrase .= ', Display While Drawing : ' . $type;

        return $phrase;
    }

    /**
     * Get information about the Darts power.
     *
     * @param string $roomInfo An array of the Darts configuration.
     *
     * @return string
     */
    protected static function _getDartsInfo($roomInfo)
    {
        if (!is_array($roomInfo)) {
            return false;
        }

        $phrase = 'Darts info : ';

        $rank = isset($roomInfo['rnk']) ? static::$_rankValue[(int)$roomInfo['rnk']] : static::$_rankValue[3];
        $phrase .= 'Controller Rank : ' . $rank;

        $time = isset($roomInfo['dt']) ? $roomInfo['dt'] : '60';
        $phrase .= ', Play Time : ' . $time;

        $time = isset($roomInfo['rt']) ? $roomInfo['rt'] : '10';
        $phrase .= ', Results Time : ' . $time;

        $type = isset($roomInfo['rc']) ? static::$_gameType[(int)$roomInfo['rc']] : static::$_gameType[0];
        $phrase .= ', Game Type : ' . $type;

        $score = isset($roomInfo['tg']) ? $roomInfo['tg'] : '5000';
        $phrase .= ', Target Score : ' . $score;

        $prize = isset($roomInfo['pz']) ? $roomInfo['pz'] . '.' : '0. ';
        $phrase .= ', Prize : ' . $prize;

        return $phrase;
    }

    /**
     * Get information about the Rankpool power.
     *
     * @param string $roomInfo An array of the Rankpool configuration.
     *
     * @return string
     */
    protected static function _getRankpoolInfo($roomInfo)
    {
        $phrase = '';

        if (isset($roomInfo['m'])) {
            $phrase = 'The main pool name is : ' . $roomInfo['m'] . '. ';
        }

        if (isset($roomInfo['t'])) {
            $phrase .= 'The rank pool name is : ' . $roomInfo['t'] . '; ';

            $rank = isset($roomInfo['rnk']) ? static::$_rankValue[(int)$roomInfo['rnk']] . '. ' : static::$_rankValue[3] . '. ';

            $phrase .= 'Min rank is : ' . $rank;
        }

        if (isset($roomInfo['b'])) {
            $phrase .= 'The banpool name is : ' . $roomInfo['b'] . '; ';

            $rank = isset($roomInfo['brk']) ? static::$_rankValue[(int)$roomInfo['brk']] . '. ' : static::$_rankValue[7] . '. ';

            $phrase .= 'Min rank is : ' . $rank;
        }

        return $phrase;
    }
}
