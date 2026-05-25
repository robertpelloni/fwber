<?php

namespace App\Logging;

use Monolog\Formatter\JsonFormatter;
use Monolog\Logger;

class JsonFormatterTap
{
    /**
     * Customize the given logger instance.
     */
    public function __invoke(Logger $logger): void
    {
        // Only apply JSON formatting when explicitly requested via env
        $format = config('logging.format', 'text');
        if (strtolower((string) $format) !== 'json') {
            return;
        }

        foreach ($logger->getHandlers() as $handler) {
            // Append newline for log shipping friendliness; preserve context
            $handler->setFormatter(new JsonFormatter(JsonFormatter::BATCH_MODE_JSON, true));
        }
    }
}
