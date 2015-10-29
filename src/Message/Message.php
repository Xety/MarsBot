<?php
namespace Mars\Message;

class Message
{
    /**
     * Raw line we have parsed
     *
     * @var string
     */
    public $raw;

    /**
     * Raw message in parts
     *
     * @var array
     */
    public $parts = [];

    /**
     * Command
     *
     * @var string
     */
    public $command;

    /**
     * Message
     *
     * @var string
     */
    public $message;

    /**
     * Code of the command
     *
     * @var string
     */
    public $commandCode;

    /**
     * Message in parts
     *
     * @var array
     */
    public $arguments = [];

    /**
     * Id of the user who has sent the message
     *
     * @var array
     */
    public $userId;

    /**
     * Initiate new parser class
     *
     * @param array $data The data to parse
     */
    public function __construct($data)
    {
        //Check if we have a Message.
        if (isset($data['message'])) {
            $this->raw = $data['message'];
            $this->parts = explode(chr(32), trim($data['message']), 2);
            $this->commandCode = substr($this->parts[0], 0, 1);
            $this->command = substr($this->parts[0], 1);

            //There are more than one word in the message
            if (count($this->parts) > 1) {
                $this->message = $this->parts[1];
                $this->arguments = explode(chr(32), $this->parts[1]);
            }
        }

        //Check if we have an userId.
        if (isset($data['userId'])) {
            $this->userId = $data['userId'];
        }
    }
}
