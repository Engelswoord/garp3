<?php
/**
 * Garp_Cli
 * Command line interface
 *
 * @package Garp_Cli
 * @author Harmen Janssen <harmen@grrr.nl>
 * @author David Spreekmeester <david@grrr.nl>
 */
class Garp_Cli {
    /**@#+
     * String coloring constants
     *
     * @var string
     */
    const RED = '0;31';
    const GREEN = '2;32';
    const BLUE = '2;34';
    const YELLOW = '0;33';
    const PURPLE = '0;35';
    const WHITE = '1;37';
    const CYAN = '0;36';
    /**#@-*/

    /**
     * Wether output should be emitted
     *
     * @var bool
     */
    static protected $_quiet = false;

    public static function isTty($stream) {
        return posix_isatty($stream);
    }

    /**
     * @param  resource $stream For instance, STDIN
     * @return string
     */
    public static function readStream($stream): string {
        $stat = fstat($stream);
        $mode = $stat['mode'] & 0170000;
        $isFifo = $mode == 0010000; // S_IFIFO;
        return $isFifo
            ? stream_get_contents($stream)
            : '';
    }

    /**
     * Print line.
     *
     * @param string $s The string.
     * @param string $color Show string in color?
     * @param bool $appendNewline Wether to add a newline character
     * @param bool $echo Wether to echo
     * @return void
     */
    public static function lineOut($s, $color = null, $appendNewline = true, $echo = true) {
        if ($color) {
            self::addStringColoring($s, $color);
        }
        $out = "{$s}" . ($appendNewline ? "\n" : '');
        if ($echo && !static::$_quiet) {
            print $out;
        } else {
            return $out;
        }
    }

    /**
     * Print line in red.
     *
     * @param string $s
     * @return void
     */
    public static function errorOut($s) {
        // Always output errors
        $oldQuiet = self::getQuiet();
        self::setQuiet(false);

        self::lineOut($s, self::RED);
        self::setQuiet($oldQuiet);
    }

    /**
     * Print line in a certain color
     * Inspired by Garp_Spawn_Util::addStringColoring()
     *
     * @param string $s
     * @param string $color
     * @return void
     */
    public static function addStringColoring(&$s, $color) {
        $prevEnc = mb_internal_encoding();
        mb_internal_encoding("UTF-8");
        $s = "\033[{$color}m{$s}\033[0m";
        mb_internal_encoding($prevEnc);
    }

    /**
     * Receive input from the commandline.
     *
     * @param string $prompt Something to say to the user indicating your waiting for a response
     * @param bool $trim Wether to trim the response
     * @return string The user's response
     */
    public static function prompt($prompt = '', $trim = true) {
        $prompt && self::lineOut($prompt);
        self::lineOut('> ', null, false);
        $response = fgets(STDIN);

        if ($trim) {
            $response = trim($response);
        }
        return $response;
    }

    /**
     * Force user to confirm a question with yes or no.
     *
     * @param string $msg Question or message to display. A prompt (>) will be added.
     * @return bool Returns true if answer was 'y' or 'Y', no enter needed.
     */
    public static function confirm($msg) {
        print $msg . ' > ';
        system('stty -icanon');
        $handle = fopen('php://stdin', 'r');
        $char = fgetc($handle);
        system('stty icanon');
        print "\n";

        $allowedResponses = array('y', 'Y', 'n', 'N');
        if (!in_array($char, $allowedResponses)) {
            // nag 'em some more
            Garp_Cli::errorOut('Please respond with a clear y or n');
            return static::confirm($msg);
        }
        return $char === 'y' || $char === 'Y';
    }

    /**
     * Set quiet mode
     *
     * @param bool $quiet
     * @return void
     */
    public static function setQuiet($quiet) {
        static::$_quiet = $quiet;
    }

    /**
     * Get quiet mode
     *
     * @return bool
     */
    public static function getQuiet() {
        return static::$_quiet;
    }

    // @codingStandardsIgnoreStart
    /**
     * PARSE ARGUMENTS
     *
     * This command line option parser supports any combination of three types
     * of options (switches, flags and arguments) and returns a simple array.
     *
     * [pfisher ~]$ php test.php --foo --bar=baz
     *   ["foo"]   => true
     *   ["bar"]   => "baz"
     *
     * [pfisher ~]$ php test.php -abc
     *   ["a"]     => true
     *   ["b"]     => true
     *   ["c"]     => true
     *
     * [pfisher ~]$ php test.php arg1 arg2 arg3
     *   [0]       => "arg1"
     *   [1]       => "arg2"
     *   [2]       => "arg3"
     *
     * [pfisher ~]$ php test.php plain-arg --foo --bar=baz --funny="spam=eggs" --also-funny=spam=eggs \
     * > 'plain arg 2' -abc -k=value "plain arg 3" --s="original" --s='overwrite' --s
     *   [0]       => "plain-arg"
     *   ["foo"]   => true
     *   ["bar"]   => "baz"
     *   ["funny"] => "spam=eggs"
     *   ["also-funny"]=> "spam=eggs"
     *   [1]       => "plain arg 2"
     *   ["a"]     => true
     *   ["b"]     => true
     *   ["c"]     => true
     *   ["k"]     => "value"
     *   [2]       => "plain arg 3"
     *   ["s"]     => "overwrite"
     *
     * @author              Patrick Fisher <patrick@pwfisher.com>
     * @since               August 21, 2009
     * @see                 http://www.php.net/manual/en/features.commandline.php
     *                      #81042 function arguments($argv) by technorati at gmail dot com, 12-Feb-2008
     *                      #78651 function getArgs($args) by B Crawford, 22-Oct-2007
     * @usage               $args = CommandLine::parseArgs($_SERVER['argv']);
     */
    public static function parseArgs($argv) {
        array_shift($argv);
        $out = array();
        foreach ($argv as $arg) {
            // --foo --bar=baz
            if (substr($arg, 0, 2) == '--') {
                $eqPos = strpos($arg,'=');

                // --foo
                if ($eqPos === false){
                    $key        = substr($arg,2);
                    $value      = isset($out[$key]) ? $out[$key] : true;
                    $out[$key]  = $value;
                }
                // --bar=baz
                else {
                    $key        = substr($arg,2,$eqPos-2);
                    $value      = substr($arg,$eqPos+1);
                    $out[$key]  = $value;
                }
            }
            // -k=value -abc
            elseif (substr($arg, 0, 1) == '-') {

                // -k=value
                if (substr($arg, 2, 1) == '=') {
                    $key        = substr($arg,1,1);
                    $value      = substr($arg,3);
                    $out[$key]  = $value;
                }
                // -abc
                else {
                    $chars = str_split(substr($arg, 1));
                    foreach ($chars as $char) {
                        $key        = $char;
                        $value      = isset($out[$key]) ? $out[$key] : true;
                        $out[$key]  = $value;
                    }
                }
            }
            // plain-arg
            else {
                $value  = $arg;
                $out[]  = $value;
            }
        }
        return $out;
    }
    // @codingStandardsIgnoreEnd

    /**
     * For some functionality you absolutely need an HTTP context.
     * This method mimics a standard Zend request.
     *
     * @param string $uri
     * @return string The response body
     */
    public static function makeHttpCall($uri) {
        $request = new Zend_Controller_Request_Http();
        $request->setRequestUri($uri);
        $application = Zend_Registry::get('application');
        $front = $application->getBootstrap()->getResource('FrontController');
        $default = $front->getDefaultModule();
        if (null === $front->getControllerDirectory($default)) {
            throw new Zend_Application_Bootstrap_Exception(
                'No default controller directory registered with front controller'
            );
        }
        $front->setParam('bootstrap', $application->getBootstrap());
        // Make sure we aren't blocked from the ContentController as per the rules in the ACL
        $front->unregisterPlugin('Garp_Controller_Plugin_Auth');
        // Make sure no output is rendered
        $front->returnResponse(true);

        $response = $front->dispatch($request);
        return $response;
    }

    /**
     * On shell, you must return 0 on success, 1 on failure.
     * This method deals with that. Feed it any expression and
     * it will exit the right way
     *
     * @param mixed $bool
     * @return void
     */
    public static function halt($bool) {
        // Convert to boolean
        $bool = !!$bool;
        // Toggle it: PHP uses 0 for FALSE, shell uses 0 for TRUE
        $bool = !$bool;
        // @codingStandardsIgnoreStart
        exit((int)$bool);
        // @codingStandardsIgnoreEnd
    }

}
