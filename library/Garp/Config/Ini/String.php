<?php
/**
 * Garp_Config_Ini_String
 * Object used to trick Garp_Config_Ini::__construct into taking another path.
 *
 * @package Garp_Config_Ini
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Config_Ini_String implements \Stringable {
    /**
     * @var string
     */
    protected $_value = '';

    /**
     * Class constructor
     *
     * @param string $value
     * @return void
     */
    public function __construct($value) {
        $this->setValue($value);
    }

    /**
     * @return string
     */
    public function getValue() {
        return $this->_value;
    }

    /**
     * @param string $value
     * @return void
     */
    public function setValue($value) {
        $this->_value = (string)$value;
    }

    /**
     * @return string
     */
    public function __toString(): string {
        return $this->_value;
    }
}
