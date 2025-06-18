<?php

namespace App\Formatter;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ApiResponseFormatter
{
    private mixed $data = null;
    private array $messages = [];
    private array $errors = [];
    private int $statusCode = Response::HTTP_OK;
    private mixed $additionalData = null;

    public function withData(mixed $data): self
    {
        $this->data = $data;
        return $this;
    }

    public function addMessage(string $message): self
    {
        $this->messages[] = $message;
        return $this;
    }

    public function withMessages(array $messages): self
    {
        $this->messages = $messages;
        return $this;
    }

    public function addError(string $error): self
    {
        $this->errors[] = $error;
        if ($this->statusCode === JsonResponse::HTTP_OK) {
            $this->statusCode = JsonResponse::HTTP_BAD_REQUEST;
        }
        return $this;
    }

    public function withErrors(array $errors): self
    {
        $this->errors = $errors;
        if (!empty($errors) && $this->statusCode === JsonResponse::HTTP_OK) {
            $this->statusCode = JsonResponse::HTTP_BAD_REQUEST;
        }
        return $this;
    }

    public function withStatusCode(int $statusCode): self
    {
        $this->statusCode = $statusCode;
        return $this;
    }

    public function withAdditionalData(mixed $additionalData): self
    {
        $this->additionalData = $additionalData;
        return $this;
    }

    public function createResponse(): JsonResponse
    {
        $responseData = [
            'data' => $this->data,
            'messages' => $this->messages,
            'errors' => $this->errors,
            'additionalData' => $this->additionalData,
        ];

        if (empty($responseData['errors']) && empty($responseData['messages'])) {
            $responseData['messages'][] = 'ok';
        }
        if (empty($responseData['data']) && !is_array($responseData['data'])) {
            unset($responseData['data']);
        }
        if (empty($responseData['messages'])) {
            unset($responseData['messages']);
        }
        if (empty($responseData['errors'])) {
            unset($responseData['errors']);
        }
        if (is_null($responseData['additionalData'])) {
            unset($responseData['additionalData']);
        }

        return new JsonResponse($responseData, $this->statusCode);
    }
}
