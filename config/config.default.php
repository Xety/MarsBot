<?php
return [
/**
 * Debug Level:
 *
 * Production Mode:
 * false: No error messages, errors, or warnings shown.
 *
 * Development Mode:
 * true: Errors and warnings shown.
 */
    'debug' => true,

/**
 * Configure basic information about the application.
 *
 * - namespace - The namespace to find app classes under.
 */
    'App' => [
        'namespace' => 'Mars',
    ],

/**
 * Configure basic information about the bot.
 */
    'Bot' => [
        'id' => 42,
        'username' => '',
        'password' => '',
        'name' => 'Bot Mars',
        'avatar' => '624',
        'home' => 'https://github.com/Xety/MarsBot',

        //Admins of the bot.
        'admin' => [
            '1000069'
        ]
    ],

/**
 * Configure basic information about the room.
 *
 * - name - The name of the chat.
 */
    'Room' => [
        'name' => 'noze',
    ],

/**
 * Configure the Xavi.
 *
 * - enabled - If true, the bot will connect to the room, change the xavi then disconnect.
 * - id - The id of the user where to get the xavi.
 */
    'Xavi' => [
        'enabled' => false,
        'id' => 804
    ],

/**
 * Configure Module manager.
 *
 * - priority - All modules that need to be loaded before others.
 */
    'Modules' => [
        'priority' => []
    ],

/**
 * Configure Packet manager.
 *
 * - priority - All packets that need to be loaded before others.
 */
    'Packets' => [
        'priority' => []
    ],

/**
 * Configure basic information about the the commands.
 *
 * - prefix - Prefix used with command.
 */
    'Commands' => [
        'prefix' => '!'
    ],

/**
 * Configure information about Pastebin.
 */
    'Pastebin' => [
        'apiDevKey' => 'zz',
        'apiPastePrivate' => '1',
        'apiPasteExpireDate' => '1M'
    ]
];
