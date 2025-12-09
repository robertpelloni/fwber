<?php

namespace App\Logging;

use Monolog\Formatter\JsonFormatter;
use Monolog\Logger;

class JsonFormatterTap
{
    /**
     * Customize the given logger instance.
     *
     * @param  \Monolog\Logger  $logger
     * @return void
     */
    public function __invoke(Logger $logger): void
    {
        // Only apply JSON formatting when explicitly requested via env
        $format = env('LOG_FORMAT', 'text');
        if (strtolower((string) $format) !== 'json') {
            return;
        }

        foreach ($logger->getHandlers() as $handler) {
            // Append newline for log shipping friendliness; preserve context
            $handler->setFormatter(new JsonFormatter(JsonFormatter::BATCH_MODE_JSON, true));
        }
    }
}
