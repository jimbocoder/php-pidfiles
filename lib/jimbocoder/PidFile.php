<?php
namespace jimbocoder;

class PidFile
{
    public static function managed($options = [])
    {
        $options = array_merge_recursive([
            'overwriteExisting' => false,
            'overcomeAdversity' => false,
            'unlinkOthers'      => false,
            'signals'           => [ SIGINT, SIGTERM ]
        ], $options);

        static::_setupSignals($options['signals']);
        static::_write($options['overwriteExisting']);
        register_shutdown_function(function() use ($options) {
            static::_unlink($options['overcomeAdversity'], $options['unlinkOthers']);
        });
    }

    protected static function _signalHandler($signal)
    {
        switch($signal) {
            default:
                break;
        }
    }

    protected static function _setupSignals($signals)
    {
        $handler = sprintf("%s::%s", __CLASS__, '_signalHandler');
        array_map(
            function($signal) use ($handler) { pcntl_signal($signal, $handler); },
            $signals
        );
    }

    protected static function _getPath()
    {
        if ( !isset($_ENV['PIDFILE']) ) {
            $pidFileDir = isset($_ENV['PIDDIR']) ? $_ENV['PIDDIR'] : '/var/run';
            $_ENV['PIDFILE'] = sprintf("%s/%s.pid", realpath($pidFileDir), basename($_SERVER['PHP_SELF']));
        }
        return $_ENV['PIDFILE'];
    }

    protected static function _write($overwriteExisting = false)
    {
        $pidFile = static::_getPath();
        if ( file_exists($pidFile) && !$overwriteExisting ) {
            static::_exit("Unwilling to overwrite existing pidfile");
        }

        if ( !is_writeable($pidFile) && !is_writeable(dirname($pidFile)) ) {
            static::_exit("Can't write to unwriteable pidfile");
        } else {
            file_put_contents($pidFile, getmypid());
        }
    }

    protected static function _unlink($overcomeAdversity = false, $unlinkOthers = false)
    {
        $pidFile = static::_getPath();
        if ( !file_exists($pidFile) && !$acceptFailure ) {
            static::_exit("Can't unlink non-existent pidfile");
        } else if ( !is_readable($pidFile) && !$acceptFailure ) {
            static::_exit("Can't unlink unreadable pidfile");
        } else if ( !is_writeable($pidFile) ) {
            static::_exit("Can't unlink unwriteable pidfile");
        }

        $pidFilePid = file_get_contents($pidFile);
        if ( $pidFilePid != getmypid() && !$evenOthers ) {
            static::_exit("Unwilling to unlink pidfile containing a different process ID.", [
                'pidFromFile' => $pidFilePid,
            ]);
        }
        unlink($pidFile);
    }

    protected static function _exit($msg, $context=[])
    {
        $context['pidFile'] = static::_getPath();
        $context['myPid'] = getmypid();
        throw new \Exception(sprintf("%s: %s", $msg, json_encode($context)));
    }
}
