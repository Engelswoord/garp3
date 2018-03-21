<?php
/**
 * Garp_Cli_Command_PostcodeNl
 *
 * @package Garp_Cli_Command
 * @author  David Spreekmeester <david@grrr.nl>
 */
class Garp_Cli_Command_PostcodeNl extends Garp_Cli_Command {
    const ERROR_NO_FILE_PROVIDED = "No path provided to the 6PP CSV file from postcode.nl";
    const SOURCE_LABEL = 'Postcode.nl';

    protected $_args;

    /**
     * @var int $_storedZips Number of inserted / updated zips this session
     */
    protected $_storedZips = 0;

    /**
     * @var Garp_Cli_Ui_ProgressBar $_progress Feedback device to display progress
     */
    protected $_progress;

    /**
     * @var int $_totalZips Total amount of zip codes to be imported. Set when known.
     */
    protected $_totalZips = 0;

    /**
     * Central start method
     *
     * @param array $args
     * @return Void
     */
    public function main(array $args = array()) {
        if (!$args) {
            $this->_displayHelp();
            return;
        }

        $mem = new Garp_Util_Memory();
        $mem->useHighMemory();

        $this->_args = $args;
        $command = $args[0];
        $this->{'_' . $command}();
    }

    protected function _import() {
        if (!$this->_obligateFileParam()) {
            return;
        }

        $path = $this->_args[1];
        $content = file_get_contents($path);

        $size = round(strlen($content) / 1024 / 1024);
        Garp_Cli::lineOut("Thanks for feeding me {$size} MB of data.");

        $zipSet = new Garp_Service_PostcodeNl_Zipcode_Set($content);

        $this->_totalZips = count($zipSet);
        $linesCountLabel = $this->_formatBigNumber($this->_totalZips);
        Garp_Cli::lineOut("Parsing {$linesCountLabel} lines.");

        $question = "Do you want this data to overwrite\n"
            . "existing entries for matching zip codes?\n"
            . "Warning: this can remove richer data."
        ;
        $overwrite = Garp_Cli::confirm($question);
        $this->_storeZipSet($zipSet, $overwrite);

        $stored = $this->_formatBigNumber($this->_storedZips);
        Garp_Cli::lineOut("\nStored {$stored} zipcodes.");
    }

    /**
     * @param Garp_Service_PostcodeNl_Zipcode_Set $zipSet
     * @param bool $overwrite Whether to overwrite existing location entries matching these zipcodes
     * @return void
     */
    protected function _storeZipSet(Garp_Service_PostcodeNl_Zipcode_Set &$zipSet, $overwrite) {
        $this->_progress = Garp_Cli_Ui_ProgressBar::getInstance();
        $this->_progress->init($this->_totalZips);
        $this->_progress->display("initializing import");

        array_walk($zipSet, array($this, '_storeZip'), $overwrite);
    }

    protected function _formatBigNumber($number) {
        return number_format($number, 0, ',', '.');
    }

    protected function _storeZip(Garp_Service_PostcodeNl_Zipcode &$zip, $key, $overwrite) {
        $this->_progress->advance();
        $this->_progress->display('Importing zip codes', '%s left');

        $model = new Model_Location();
        $select = $model->select()->where('zip = ? AND number IS NULL', $zip->zipcode);
        $existingRow = $model->fetchRow($select);

        if (!$existingRow) {
            return $this->_insertZip($zip, $model);
        }

        if ($overwrite && $existingRow->source === self::SOURCE_LABEL) {
            $model->update(
                $this->_zipToRow($zip),
                'id = ' . $existingRow->id
            );
            $this->_storedZips++;
        }

    }

    protected function _insertZip(Garp_Service_PostcodeNl_Zipcode &$zip, Garp_Model_Db &$model) {
        if ($model->insert($this->_zipToRow($zip))) {
            $this->_storedZips++;
        }
    }

    protected function _zipToRow(Garp_Service_PostcodeNl_Zipcode $zip) {
        return array(
            'zip' => $zip->zipcode,
            'latitude' => $zip->latitude,
            'longitude' => $zip->longitude,
            'source' => self::SOURCE_LABEL
        );
    }

    protected function _obligateFileParam() {
        if (!array_key_exists(1, $this->_args)) {
            $this->_printError(self::ERROR_NO_FILE_PROVIDED);
            $this->_displayHelp();
            return false;
        }
        return true;
    }

    protected function _printError($message) {
        Garp_Cli::errorOut($message);
        Garp_Cli::lineOut("");
    }

    protected function _displayHelp() {
        Garp_Cli::lineOut("Commands");
        Garp_Cli::lineOut("g postcodenl import ~/pcdata.csv", Garp_Cli::BLUE);
        Garp_Cli::lineOut(
            "Where the last argument is the path to the 6PP CSV file from postcode.nl."
        );

    }
}
