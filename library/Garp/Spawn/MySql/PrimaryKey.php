<?php
/**
 * @author David Spreekmeester | grrr.nl
 * @package Garp
 * @subpackage Model
 */
class Garp_Spawn_MySql_PrimaryKey extends Garp_Spawn_MySql_Key {
    public $columns = [];


    public static function isPrimaryKeyStatement($line) {
        return stripos((string) $line, 'PRIMARY KEY') !== false;
    }


    /**
     * @param Array $primaryKeys An array of field names that will serve as primary key.
     */
    public static function renderSqlDefinition(array $primaryKeyNames) {
        return "  PRIMARY KEY (`".implode('`,`', $primaryKeyNames)."`)";
    }


    public static function modify($tableName, Garp_Spawn_MySql_PrimaryKey $newPrimaryKey) {
        $tableName  = strtolower((string) $tableName);
        $adapter    = Zend_Db_Table::getDefaultAdapter();

        $sql =   "ALTER TABLE `{$tableName}` ";
        if (self::_liveTableHasPrimaryKey($tableName)) {
            $sql .= "DROP PRIMARY KEY, ";
        }
        $sql .= "ADD PRIMARY KEY(`" . implode('`,`', $newPrimaryKey->columns) . "`)";

        return $adapter->query($sql);
    }


    protected static function _liveTableHasPrimaryKey($tableName) {
        $tableName  = strtolower((string) $tableName);
        $adapter    = Zend_Db_Table::getDefaultAdapter();
        return (bool)$adapter->query("SHOW INDEXES FROM `{$tableName}` WHERE Key_name = 'PRIMARY'")->fetch();
    }


    protected function _parse($line) {
        $matches = [];
        preg_match('/PRIMARY KEY\s+\((?P<columns>[`\w,]+?)\)/i', trim((string) $line), $matches);
        if (array_key_exists('columns', $matches)) {
            $columns = preg_split('/`+,?\s?/', $matches['columns'], -1, PREG_SPLIT_NO_EMPTY);
            return ['columns' => $columns];
        } else throw new Exception("Could not find any column names in the primary key statement.");
    }
}
