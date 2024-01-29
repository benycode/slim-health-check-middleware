<?php

declare(strict_types=1);

namespace BenyCode\Slim\Middleware\Traits;

trait WithLogger
{
    private function error(string $identifier, string $message, array $context = [])
    {
        if(null !== $this->logger) {
            $this
                ->logger
                ->error(
                    \sprintf(
                        '%s | %s | %s',
                        \gethostname(),
                        $identifier,
                        $message
                    ),
                    $context,
                )
            ;
        }
    }

    private function info(string $identifier, string $message, array $context = [])
    {
        if(null !== $this->logger) {
            $this
                ->logger
                ->info(
                    \sprintf(
                        '%s | %s | %s',
                        \gethostname(),
                        $identifier,
                        $message,
                    ),
                    $context,
                )
            ;
        }
    }
}
