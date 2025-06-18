<?php

namespace App\Tests;

use App\Formatter\ApiResponseFormatter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ApiResponseFormatterTest extends TestCase
{
    public function testCreateResponseWithDataAndSuccessStatus(): void
    {
        $formatter = new ApiResponseFormatter();
        $testData = ['id' => 1, 'name' => 'Test Item'];


        $response = $formatter
            ->withData($testData)
            ->withStatusCode(Response::HTTP_OK)
            ->createResponse();


        $this->assertInstanceOf(JsonResponse::class, $response);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('data', $responseData);
        $this->assertEquals($testData, $responseData['data']);

        $this->assertArrayHasKey('messages', $responseData);
        $this->assertEquals(['ok'], $responseData['messages']);
    }
}
