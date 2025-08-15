<?php

namespace Tests\Feature;

use App\Http\Services\OpenRouter\OpenRouterInterface;
use App\Http\Services\OpenRouter\OpenRouterRequestDTO;
use App\Http\Services\OpenRouter\OpenRouterResponseDTO;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpenRouterServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_openrouter_service_can_be_resolved_from_container()
    {
        $service = app(OpenRouterInterface::class);
        
        $this->assertInstanceOf(OpenRouterInterface::class, $service);
    }

    public function test_openrouter_request_dto_creation()
    {
        $dto = OpenRouterRequestDTO::createChatRequest(
            'Test message',
            'test-model',
            ['temperature' => 0.7]
        );

        $this->assertEquals('test-model', $dto->model);
        $this->assertEquals('Test message', $dto->messages[0]['content']);
        $this->assertEquals('user', $dto->messages[0]['role']);
        $this->assertEquals(0.7, $dto->temperature);
    }

    public function test_openrouter_request_dto_to_array()
    {
        $dto = OpenRouterRequestDTO::createChatRequest(
            'Test message',
            'test-model',
            ['temperature' => 0.7, 'max_tokens' => 100]
        );

        $array = $dto->toArray();

        $this->assertArrayHasKey('model', $array);
        $this->assertArrayHasKey('messages', $array);
        $this->assertArrayHasKey('temperature', $array);
        $this->assertArrayHasKey('max_tokens', $array);
        $this->assertEquals('test-model', $array['model']);
        $this->assertEquals(0.7, $array['temperature']);
        $this->assertEquals(100, $array['max_tokens']);
    }

    public function test_openrouter_response_dto_success()
    {
        $dto = OpenRouterResponseDTO::success(['test' => 'data'], 200);

        $this->assertTrue($dto->success);
        $this->assertEquals(['test' => 'data'], $dto->data);
        $this->assertEquals(200, $dto->statusCode);
        $this->assertNull($dto->error);
    }

    public function test_openrouter_response_dto_error()
    {
        $dto = OpenRouterResponseDTO::error('Test error', 500);

        $this->assertFalse($dto->success);
        $this->assertEquals('Test error', $dto->error);
        $this->assertEquals(500, $dto->statusCode);
        $this->assertNull($dto->data);
    }

    public function test_openrouter_response_dto_from_array()
    {
        $data = [
            'success' => true,
            'data' => ['test' => 'data'],
            'status_code' => 200
        ];

        $dto = OpenRouterResponseDTO::fromArray($data);

        $this->assertTrue($dto->success);
        $this->assertEquals(['test' => 'data'], $dto->data);
        $this->assertEquals(200, $dto->statusCode);
    }

    public function test_openrouter_response_dto_content_extraction()
    {
        $data = [
            'success' => true,
            'data' => [
                'choices' => [
                    [
                        'message' => [
                            'content' => 'Test response content'
                        ]
                    ]
                ]
            ],
            'status_code' => 200
        ];

        $dto = OpenRouterResponseDTO::fromArray($data);

        $this->assertEquals('Test response content', $dto->getContent());
        $this->assertTrue($dto->hasContent());
    }

    public function test_openrouter_response_dto_model_extraction()
    {
        $data = [
            'success' => true,
            'data' => [
                'model' => 'test-model-123'
            ],
            'status_code' => 200
        ];

        $dto = OpenRouterResponseDTO::fromArray($data);

        $this->assertEquals('test-model-123', $dto->getModel());
    }

    public function test_openrouter_response_dto_usage_extraction()
    {
        $data = [
            'success' => true,
            'data' => [
                'usage' => [
                    'prompt_tokens' => 10,
                    'completion_tokens' => 20,
                    'total_tokens' => 30
                ]
            ],
            'status_code' => 200
        ];

        $dto = OpenRouterResponseDTO::fromArray($data);

        $this->assertEquals([
            'prompt_tokens' => 10,
            'completion_tokens' => 20,
            'total_tokens' => 30
        ], $dto->getUsage());
    }

    public function test_openrouter_response_dto_error_message()
    {
        $dto = OpenRouterResponseDTO::error('Test error message', 500);

        $this->assertEquals('Test error message', $dto->getErrorMessage());
    }

    public function test_openrouter_response_dto_to_array()
    {
        $dto = OpenRouterResponseDTO::success(['test' => 'data'], 200);
        $array = $dto->toArray();

        $this->assertArrayHasKey('success', $array);
        $this->assertArrayHasKey('data', $array);
        $this->assertArrayHasKey('status_code', $array);
        $this->assertTrue($array['success']);
        $this->assertEquals(['test' => 'data'], $array['data']);
        $this->assertEquals(200, $array['status_code']);
    }
}
