<?php
/**
 * Garp_File_Extension
 * Value-object class representing a file extension.
 *
 * @author       Harmen Janssen | grrr.nl
 * @version      0.1.0
 * @package      Garp_File
 */
class Garp_File_Extension implements \Stringable {

    protected $_extension;
    // @todo Add every single mimetype known to man.
    protected $_mimeStore = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'text/javascript' => 'js',
        'text/plain' => 'txt',
        'text/x-php' => 'php',
    ];

    /**
     * Class constructor
     * @return Void
     */
    public function __construct($mime) {
        $this->_extension = $this->_mimeStore[$mime] ?? null;
    }

    public function getValue() {
        return $this->_extension;
    }

    public function __toString(): string {
        return (string) $this->getValue();
    }
}
