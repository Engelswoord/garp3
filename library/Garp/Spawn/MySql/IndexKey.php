<?php
/**
 * @author David Spreekmeester | grrr.nl
 * @package Garp
 * @subpackage Model
 */
class Garp_Spawn_MySql_IndexKey extends Garp_Spawn_MySql_Key {
    public $name;
    public $column;


    /**
     * @param String $line Line of SQL from the model's CREATE TABLE statement.
     * @param Array $foreignKeys The foreignKeys in this table, of type Garp_Spawn_MySql_ForeignKey.
     * Returns true if this is a 'KEY' (index) statement. This does not include primary or foreign keys.
     */
    public static function isIndexKeyStatement($line, Array $foreignKeys) {
        if (str_starts_with(trim($line), 'KEY')) {
            $key = self::_parse($line);

            foreach ($foreignKeys as $fk) {
                if ($key['column'] === $fk->localColumn) {
                    return false;
                }
            }

            return true;
        }

        return false;
    }


    public static function renderSqlDefinition($columnName) {
        return "  KEY `{$columnName}` (`{$columnName}`)";
    }


    public static function add($tableName, Garp_Spawn_MySql_IndexKey $key) {
        $tableName  = strtolower((string) $tableName);
        $adapter    = Zend_Db_Table::getDefaultAdapter();
        $success    = false;

        try {
            $success = $adapter->query("ALTER TABLE `{$tableName}` ADD KEY `{$key->name}` (`{$key->column}`);");
        } catch(Exception $e) {
            if (!str_contains($e->getMessage(), 'Duplicate')) {
                throw $e;
            } else $success = true;
        }
        
        return $success;
    }


    public static function delete($tableName, Garp_Spawn_MySql_IndexKey $key) {
        $tableName  = strtolower((string) $tableName);
        $adapter    = Zend_Db_Table::getDefaultAdapter();
        return $adapter->query("ALTER TABLE `{$tableName}` DROP KEY `{$key->name}`;");
    }
    
    
    protected static function _parse($line) {
        $matches = [];
        preg_match('/\s*KEY\s+`(?P<name>\w+)`\s*\(`(?P<column>\w+)`\),?/i', trim((string) $line), $matches);
        return $matches;
    }
}