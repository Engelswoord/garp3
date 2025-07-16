<?php

use Garp\Functional as f;

/**
 * Garp_Spawn_Behavior_Type_Set
 * class description
 *
 * @package Garp_Spawn_Behavior_Type
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Spawn_Behavior_Type_Set extends Garp_Spawn_Behavior_Type_Abstract {
    static public function isNeededBy(Garp_Spawn_Model_Abstract $model) {
        return !!count(self::_getSetColumnsFromModel($model));
    }

    #[\Override]
    public function needsPhpModelObserver() {
        return true;
    }

    #[\Override]
    public function getParams() {
        $sets = self::_getSetColumnsFromModel($this->getModel());
        return ['columns' => array_map(f\prop('name'), $sets)];
    }

    static protected function _getSetColumnsFromModel(Garp_Spawn_Model_Abstract $model) {
        return $model->fields->getFields('type', 'set');
    }
}

