<?php
/**
 * Garp_Form_Element_Number
 * @author Harmen Janssen | grrr.nl
 * @version 1
 * @package Garp
 * @subpackage Form
 */
class Garp_Form_Element_Number extends Garp_Form_Element_Text {

    #[\Override]
    public function init() {
        $this->addFilter('Digits');
        $this->addValidator('Digits');

        $validatorOpts = [
            'min' => $this->getAttrib('min'),
            'max' => $this->getAttrib('max'),
        ];
        $validator = null;
        if (2 === count($validatorOpts)) {
            $validator = 'Between';
        } elseif (isset($validatorOpts['min'])) {
            $validator = 'GreaterThan';
        } elseif (isset($validatorOpts['max'])) {
            $validator = 'LessThan';
        }

        if ($validator) {
            $this->addValidator($validator, false, $validatorOpts);
        }
    }

}
