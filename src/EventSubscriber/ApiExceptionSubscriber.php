<?php

namespace App\EventSubscriber;


use Cassandra\Exception\UnauthorizedException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

final class ApiExceptionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::EXCEPTION => ['onException', 10]];
    }

    public function onException(ExceptionEvent $event): void
    {
        $request = $event->getRequest();
        if (!str_starts_with($request->getPathInfo(), '/api')) {
            return;
        }

        $e = $event->getThrowable();
        $prev = $e->getPrevious();

        if ($e instanceof ValidationFailedException) {
            $event->setResponse(new JsonResponse([
                'message' => 'The given data was invalid.',
                'errors'  => $this->toLaravelErrors($e->getViolations()),
            ], 422));
            return;
        }

        if ($e instanceof HttpExceptionInterface && ($prev = $e->getPrevious()) instanceof ValidationFailedException) {
            $status = $e->getStatusCode() === 0 ? 422 : $e->getStatusCode();
            $event->setResponse(new JsonResponse([
                'message' => 'The given data was invalid.',
                'errors'  => $this->toLaravelErrors($prev->getViolations()),
            ], $status >= 400 ? $status : 422));
            return;
        }

        if ($e instanceof BadRequestHttpException && str_contains((string)$e->getMessage(), 'Invalid JSON')) {
            $event->setResponse(new JsonResponse([
                'message' => 'Invalid JSON body.',
                'errors'  => [],
            ], 400));
            return;
        }

        if (
            $e instanceof UnauthorizedHttpException // часто кидает JWT
            || $e instanceof AuthenticationException
            || ($e instanceof HttpExceptionInterface && 401 === $e->getStatusCode())
            || ($prev instanceof AuthenticationException)
        ) {
            $reason = (string) ($e instanceof UnauthorizedHttpException ? $e->getMessage() : $e->getMessage());
            $message = match (true) {
                str_contains($reason, 'Expired') || str_contains($reason, 'expired') => 'Token expired.',
                str_contains($reason, 'Invalid') || str_contains($reason, 'invalid') => 'Token invalid.',
                str_contains($reason, 'Not found') || str_contains($reason, 'not found') => 'Token not provided.',
                default => 'Unauthenticated.',
            };

            $event->setResponse(new JsonResponse([
                'message' => $message,
                'errors'  => [],
            ], 401));
            return;
        }

        if (
            $e instanceof AccessDeniedException
            || ($e instanceof HttpExceptionInterface && 403 === $e->getStatusCode())
        ) {
            $event->setResponse(new JsonResponse([
                'message' => 'This action is unauthorized.',
                'errors'  => [],
            ], 403));
            return;
        }

        if ($e instanceof HttpExceptionInterface) {
            $status = $e->getStatusCode();
            $event->setResponse(new JsonResponse([
                'message' => $e->getMessage() ?: ($status === 404 ? 'Not Found.' : 'HTTP Error'),
                'errors'  => [],
            ], $status));
            return;
        }

        $event->setResponse(new JsonResponse([
            'message' => 'Server Error.',
            'errors'  => [],
        ], 500));
    }

    private function toLaravelErrors(ConstraintViolationListInterface $violations): array
    {
        $errors = [];
        /** @var ConstraintViolationInterface $v */
        foreach ($violations as $v) {
            $path = $this->normalizePath($v->getPropertyPath());
            $errors[$path][] = $v->getMessage();
        }
        return $errors;
    }

    private function normalizePath(string $path): string
    {
        $p = preg_replace('#^data\.#', '', $path);
        $p = preg_replace('#^children\[([^\]]+)\](?:\.data)?$#', '$1', $p);
        $p = preg_replace('/^\[([^\]]+)\]$/', '$1', $p);
        return $p ?: 'non_field_errors';
    }
}
