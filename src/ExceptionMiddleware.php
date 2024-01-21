<?php

namespace BenyCode\Slim\Middleware;

use Throwable;
use DomainException;
use Psr\Log\LoggerInterface;
use InvalidArgumentException;
use Slim\Exception\HttpException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseFactoryInterface;

final class ExceptionMiddleware implements MiddlewareInterface
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private ?LoggerInterface $logger = null,
        private bool $displayErrorDetails = false,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface
    {
        try {
            return $handler
                ->handle($request)
            ;
        } catch (Throwable $exception) {
            return $this
                ->render($exception, $request)
            ;
        }
    }

    private function render(
        Throwable $exception,
        ServerRequestInterface $request,
    ) : ResponseInterface {
        $httpStatusCode = $this
            ->getHttpStatusCode($exception)
        ;

        $response = $this
            ->responseFactory
            ->createResponse($httpStatusCode)
        ;

        // Log error
        if (isset($this->logger)) {
            $this->logger->error(
                sprintf(
                    '%s;Code %s;File: %s;Line: %s',
                    $exception->getMessage(),
                    $exception->getCode(),
                    $exception->getFile(),
                    $exception->getLine()
                ),
                $exception->getTrace()
            );
        }

        // Content negotiation
        if (str_contains($request->getHeaderLine('Accept'), 'application/json')) {
            $response = $response
                ->withAddedHeader('Content-Type', 'application/json')
            ;

            // JSON
            return $this
                ->renderJson($exception, $response)
            ;
        }

        // HTML
        return $this
            ->renderHtml($response, $exception)
        ;
    }

    public function renderJson(Throwable $exception, ResponseInterface $response) : ResponseInterface
    {
        $data = [
            'error' => [
                'message' => $exception->getMessage(),
            ],
        ];

        $response = $response
            ->withHeader('Content-Type', 'application/json; charset=UTF-8')
        ;

        $response->getBody()->write(
            (string)json_encode(
                $data,
                JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR
            )
        );

        return $response;
    }

    public function renderHtml(ResponseInterface $response, Throwable $exception) : ResponseInterface
    {
        $response = $response
            ->withAddedHeader('Content-Type', 'text/html')
        ;

        $message = sprintf(
            "\n<br>Error %s (%s)\n<br>Message: %s\n<br>",
            $this->html((string)$response->getStatusCode()),
            $this->html($response->getReasonPhrase()),
            $this->html($exception->getMessage()),
        );

        if ($this->displayErrorDetails) {
            $message .= sprintf(
                'File: %s, Line: %s',
                $this->html($exception->getFile()),
                $this->html((string)$exception->getLine())
            );
        }

        $response
            ->getBody()
            ->write($message)
        ;

        return $response;
    }

    private function getHttpStatusCode(Throwable $exception) : int
    {
        $statusCode = StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR;

        if ($exception instanceof HttpException) {
            $statusCode = $exception->getCode();
        }

        if ($exception instanceof DomainException || $exception instanceof InvalidArgumentException) {
            $statusCode = StatusCodeInterface::STATUS_BAD_REQUEST;
        }

        return $statusCode;
    }

    private function html(string $text) : string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
