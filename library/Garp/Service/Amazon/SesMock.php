<?php
class Garp_Service_Amazon_SesMock extends Garp_Service_Amazon_Ses {
    protected static $_requests = [];

    public function _makeRequest($args = []) {
        static::$_requests[] = $args;
    }

    public function getRequest($n) {
        return static::$_requests[$n] ?? null;
    }

    public function getRequests() {
        return static::$_requests;
    }

    public static function clearRequests() {
        static::$_requests = [];
    }

}
