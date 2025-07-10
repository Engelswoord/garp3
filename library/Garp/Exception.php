<?php
/**
 * Garp_Exception
 * class description
 *
 * @package Garp_Exception
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Exception extends Exception {

    public static function isDuplicateEntryException(Exception $e): bool {
        return str_contains($e->getMessage(), 'Duplicate entry');
    }

}
