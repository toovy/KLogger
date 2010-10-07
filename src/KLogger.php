<?php

/**
 * Finally, a light, permissions-checking logging class.
 *
 * Author : Kenny Katzgrau <katzgrau@gmail.com>
 * Date : July 26, 2008
 * Comments : Originally written for use with wpSearch
 * Website : http://codefury.net
 * Version : 1.0
 *
 * Usage:
 * $log = new KLogger ( "log.txt" , KLogger::INFO );
 * $log->_logInfo("Returned a million search results"); //Prints to the log file
 * $log->_logFatal("Oh dear."); //Prints to the log file
 * $log->_logDebug("x = 5"); //Prints nothing due to priority setting
 */

/**
 * Class documentation
 */
class KLogger
{
    const DEBUG = 1; // Most Verbose
    const INFO  = 2; // ...
    const WARN  = 3; // ...
    const ERROR = 4; // ...
    const FATAL = 5; // Least Verbose
    const OFF   = 6; // Nothing at all.

    const LOG_OPEN    = 1;
    const OPEN_FAILED = 2;
    const LOG_CLOSED  = 3;

    private $_logStatus    = self::LOG_CLOSED;
    private $_messageQueue = array();
    private $_logFile      = null;
    private $_priority     = self::INFO;
    private $_fileHandle   = null;

    private static $_defaultPriority    = self::DEBUG;
    private static $_dateFormat         = "Y-m-d G:i:s";
    private static $_defaultPermissions = 0777;
    private static $instances           = array();

    /**
     * Partially implements the Singleton pattern. Each $logDirectory gets one
     * instance.
     *
     * @param string  $logDirectory File path to the logging directory
     * @param integer $priority     One of the pre-defined priority constants
     * @return KLogger
     */
    public static function instance($logDirectory = false, $priority = false)
    {
        if ($priority === false) {
            $priority = self::$_defaultPriority;
        }
        
        if ($logDirectory === false) {
            if (count(self::$instances) > 0) {
                return self::$instances[0];
            } else {
                $logDirectory = dirname(__FILE__);
            }
        }

        if (in_array($logDirectory, self::$instances)) {
            return self::$instances[$logDirectory];
        }

        self::$instances[$logDirectory] = new self($logDirectory, $priority);

        return self::$instances[$logDirectory];
    }

    /**
     * Class constructor
     *
     * @param string  $logDirectory File path to the logging directory
     * @param integer $priority     One of the pre-defined priority constants
     * @return void
     */
    public function __construct($logDirectory, $priority)
    {
        $logDirectory = rtrim($logDirectory, '/');

        if ($priority == self::OFF) {
            return;
        }

        $this->_logFile = $logDirectory
            . DIRECTORY_SEPARATOR
            . 'log_'
            . date('Y-m-d')
            . '.txt';

        $this->_priority = $priority;
        if (!file_exists($logDirectory)) {
            mkdir($logDirectory, self::$_defaultPermissions, true);
        }

        if (file_exists($this->_logFile)) {
            if (!is_writable($this->_logFile)) {
                $this->_logStatus = self::OPEN_FAILED;
                $this->_messageQueue[] = "The file exists, but could not be opened for writing. Check that appropriate permissions have been set.";
                return;
            }
        }

        if (($this->_fileHandle = fopen($this->_logFile, "a"))) {
            $this->_logStatus = self::LOG_OPEN;
            $this->_messageQueue[] = "The log file was opened successfully.";
        } else {
            $this->_logStatus = self::OPEN_FAILED;
            $this->_messageQueue[] = "The file could not be opened. Check permissions.";
        }
    }

    /**
     * Class destructor
     */
    public function __destruct()
    {
        if ($this->_fileHandle) {
            fclose($this->_fileHandle);
        }
    }

    /**
     * Writes a $line to the log with a priority level of INFO
     *
     * @param string $line Information to log
     * @return void
     */
    public function logInfo($line)
    {
        $this->log($line, self::INFO);
    }

    /**
     * Writes a $line to the log with a priority level of DEBUG
     *
     * @param string $line Information to log
     * @return void
     */
    public function logDebug($line)
    {
        $this->log($line, self::DEBUG);
    }

    /**
     * Writes a $line to the log with a priority level of WARN
     *
     * @param string $line Information to log
     * @return void
     */
    public function logWarn($line)
    {
        $this->log($line, self::WARN);
    }

    /**
     * Writes a $line to the log with a priority level of ERROR
     *
     * @param string $line Information to log
     * @return void
     */
    public function logError($line)
    {
        $this->log($line, self::ERROR);
    }

    /**
     * Writes a $line to the log with a priority level of FATAL
     *
     * @param string $line Information to log
     * @return void
     */
    public function logFatal($line)
    {
        $this->log($line, self::FATAL);
    }

    /**
     * Writes a $line to the log with the given priority
     *
     * @param string  $line     Text to add to the log
     * @param integer $priority Priority level of log message (use constants)
     */
    public function log($line, $priority)
    {
        if ($this->_priority <= $priority) {
            $status = $this->_getTimeLine($priority);
            $this->writeFreeFormLine("$status $line \n");
        }
    }

    /**
     * Writes a line to the log without prepending a status or timestamp
     *
     * @param string $line Line to write to the log
     * @return void
     */
    public function writeFreeFormLine($line)
    {
        if ($this->_logStatus == self::LOG_OPEN
            && $this->_priority != self::OFF) {
            if (fwrite($this->_fileHandle, $line) === false) {
                $this->_messageQueue[] = "The file could not be written to. Check that appropriate permissions have been set.";
            }
        }
    }

    private function _getTimeLine($level)
    {
        $time = date(self::$_dateFormat);

        switch ($level) {
            case self::INFO:
                return "$time - INFO -->";
            case self::WARN:
                return "$time - WARN -->";
            case self::DEBUG:
                return "$time - DEBUG -->";
            case self::ERROR:
                return "$time - ERROR -->";
            case self::FATAL:
                return "$time - FATAL -->";
            default:
                return "$time - LOG -->";
        }
    }
}
