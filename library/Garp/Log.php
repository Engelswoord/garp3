<?php
/**
 * Garp_Log
 * class description
 *
 * @package Garp
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Log extends Zend_Log {
    /**
     * Shortcut to fetching a configured logger instance
     *
     * @param  array|Zend_Config $config Array or instance of Zend_Config
     * @return Zend_Log
     */
    static public function factory($config = []) {
        if (is_string($config)) {
            // Assume $config is a filename
            $filename = $config;
            $config = [
                'timestampFormat' => 'Y-m-d',
                [
                    'writerName' => 'Stream',
                    'writerParams' => [
                        'stream' => self::_getLoggingDirectory() . DIRECTORY_SEPARATOR . $filename
                    ]
                ]
            ];
        }
        return parent::factory($config);
    }

    static protected function _getLoggingDirectory() {
        $target = APPLICATION_PATH . '/data/logs/';
        if (Zend_Registry::isRegistered('config')
            && !empty(Zend_Registry::get('config')->logging->directory)
        ) {
            $target = Zend_Registry::get('config')->logging->directory;
        }
        if (!is_dir($target)) {
            @mkdir($target);
        }
        return $target;
    }
}
