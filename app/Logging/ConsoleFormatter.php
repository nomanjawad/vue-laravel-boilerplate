<?php

namespace App\Logging;

use Monolog\Formatter\FormatterInterface;
use Monolog\Level;
use Monolog\LogRecord;

/**
 * A tree-structured, color-coded log formatter for terminal output,
 * inspired by @meadown/logger.
 *
 * Renders each record as:
 *
 *   [INFO]
 *   ├── user logged in { "id": 5 }
 *   └── 05-30 04:00:00 PM - (LoginController.php:42)
 */
class ConsoleFormatter implements FormatterInterface
{
    private const RESET = "\033[0m";
    private const DIM = "\033[2m";
    private const BOLD = "\033[1m";

    /** ANSI foreground color per log level. */
    private const COLORS = [
        'DEBUG'     => "\033[90m", // gray
        'INFO'      => "\033[36m", // cyan
        'NOTICE'    => "\033[34m", // blue
        'WARNING'   => "\033[33m", // yellow
        'ERROR'     => "\033[31m", // red
        'CRITICAL'  => "\033[31m", // red
        'ALERT'     => "\033[31m", // red
        'EMERGENCY' => "\033[31m", // red
    ];

    public function __construct(
        private bool $useColor = true,
    ) {
    }

    public function format(LogRecord $record): string
    {
        $level = $record->level->getName();
        $color = self::COLORS[$level] ?? self::COLORS['INFO'];

        // Header: [INFO]
        $header = $this->paint($color . self::BOLD, '[' . $level . ']');

        // Branch: message + inline context
        $message = trim($record->message);
        $context = $this->formatData($record->context);
        $branch = '├── ' . $this->paint($color, $message);
        if ($context !== '') {
            $branch .= ' ' . $this->paint(self::DIM, $context);
        }

        // Leaf: timestamp + source location
        $timestamp = $record->datetime->format('m-d h:i:s A');
        $source = $this->source($record);
        $leaf = $this->paint(self::DIM, '└── ' . $timestamp . ($source !== '' ? ' - (' . $source . ')' : ''));

        $lines = $header . "\n" . $branch . "\n" . $leaf . "\n";

        // Append exception trace, if present.
        if (isset($record->context['exception']) && $record->context['exception'] instanceof \Throwable) {
            $lines .= $this->paint(self::DIM, (string) $record->context['exception']) . "\n";
        }

        return $lines;
    }

    public function formatBatch(array $records): string
    {
        return implode('', array_map([$this, 'format'], $records));
    }

    /** Render the context array as compact, single-line JSON (sans exception object). */
    private function formatData(array $context): string
    {
        unset($context['exception']);

        if ($context === []) {
            return '';
        }

        $json = json_encode(
            $context,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR
        );

        return $json === false ? '' : $json;
    }

    /** Build a "file:line" source hint from the introspection processor's extra data. */
    private function source(LogRecord $record): string
    {
        $file = $record->extra['file'] ?? null;
        $line = $record->extra['line'] ?? null;

        if ($file === null) {
            return '';
        }

        return basename((string) $file) . ($line !== null ? ':' . $line : '');
    }

    private function paint(string $code, string $text): string
    {
        return $this->useColor ? $code . $text . self::RESET : $text;
    }
}
