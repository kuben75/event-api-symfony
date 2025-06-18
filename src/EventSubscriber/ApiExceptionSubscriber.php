<?php

namespace App\EventSubscriber;

use App\Formatter\ApiResponseFormatter;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class ApiExceptionSubscriber implements EventSubscriberInterface
{
    private ApiResponseFormatter $apiFormatter;
    private bool $isDevEnvironment;

    public function __construct(ApiResponseFormatter $apiFormatter, string $appEnv)
    {
        $this->apiFormatter = $apiFormatter;
        $this->isDevEnvironment = ($appEnv === 'dev');
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $request = $event->getRequest();

        if (strpos($request->getPathInfo(), '/api') !== 0) {
            return;
        }

        $exception = $event->getThrowable();
        $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;
        $message = 'An internal server error occurred.';

        if ($exception instanceof HttpExceptionInterface) {
            $statusCode = $exception->getStatusCode();
            $message = $exception->getMessage();
        }

        if ($exception instanceof AuthenticationException) {
            $statusCode = Response::HTTP_UNAUTHORIZED;
            $message = $exception->getMessage();
        }
        if ($exception instanceof AccessDeniedException) {
            $statusCode = Response::HTTP_FORBIDDEN;
            $message = $exception->getMessage();
        }

        $this->apiFormatter->addError($message)->withStatusCode($statusCode);

        if ($statusCode >= 500 && $this->isDevEnvironment) {
            $this->apiFormatter->withAdditionalData([
                'exception' => get_class($exception),
                'trace' => $exception->getTraceAsString(),
            ]);
        }

        $response = $this->apiFormatter->createResponse();
        $event->setResponse($response);
    }
}
