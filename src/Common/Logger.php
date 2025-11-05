<?php

namespace Kova\Kams\Common;

use Throwable;

/**
 * Simple logger utility for Kova application.
 * Logs to a file in the logs directory under KOVA_ROOT.
 */
final class Logger
{
    private string $logFile;
    private string $logDir;

    public function __construct(string $logFile = 'app.log', ?string $logDir = null)
    {
        if ($logDir === null) {
            $logDir = defined('KOVA_ROOT') ? KOVA_ROOT . '/logs' : '/tmp';
        }
        $this->logDir = $logDir;
        $this->logFile = $logFile;
    }

    /**
     * Log an error message.
     */
    public function error(string $message, array $context = []): void
    {
        $this->write('ERROR', $message, $context);
    }

    /**
     * Log a warning message.
     */
    public function warning(string $message, array $context = []): void
    {
        $this->write('WARNING', $message, $context);
    }

    /**
     * Log an info message.
     */
    public function info(string $message, array $context = []): void
    {
        $this->write('INFO', $message, $context);
    }

    /**
     * Write a log entry.
     */
    private function write(string $level, string $message, array $context = []): void
    {
        try {
            if (!is_dir($this->logDir)) {
                @mkdir($this->logDir, 0755, true);
            }

            $logPath = $this->logDir . '/' . $this->logFile;
            $timestamp = date('Y-m-d H:i:s');
            $contextStr = empty($context) ? '' : ' ' . json_encode($context);
            $logLine = "[{$timestamp}] [{$level}] {$message}{$contextStr}" . PHP_EOL;

            @file_put_contents($logPath, $logLine, FILE_APPEND | LOCK_EX);
        } catch (Throwable $_) {
            // Fallback to error_log if file writing fails
            error_log("{$level}: {$message}");
        }
    }
}


