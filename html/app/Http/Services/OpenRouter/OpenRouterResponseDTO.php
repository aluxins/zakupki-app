<?php

namespace App\Http\Services\OpenRouter;

class OpenRouterResponseDTO
{
    public function __construct(
        public readonly bool $success,
        public readonly ?array $data = null,
        public readonly ?string $error = null,
        public readonly int $statusCode = 200
    ) {}

    /**
     * Create success response DTO
     */
    public static function success(array $data, int $statusCode = 200): self
    {
        return new self(
            success: true,
            data: $data,
            statusCode: $statusCode
        );
    }

    /**
     * Create error response DTO
     */
    public static function error(string $error, int $statusCode = 500): self
    {
        return new self(
            success: false,
            error: $error,
            statusCode: $statusCode
        );
    }

    /**
     * Create DTO from an array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            success: $data['success'] ?? false,
            data: $data['data'] ?? null,
            error: $data['error'] ?? null,
            statusCode: $data['status_code'] ?? 200
        );
    }

    /**
     * Convert DTO to array
     */
    public function toArray(): array
    {
        $result = [
            'success' => $this->success,
            'status_code' => $this->statusCode
        ];

        if ($this->data !== null) {
            $result['data'] = $this->data;
        }

        if ($this->error !== null) {
            $result['error'] = $this->error;
        }

        return $result;
    }

    /**
     * Get the AI response content if available
     */
    public function getContent(): ?string
    {
        if (!$this->success || !$this->data) {
            return null;
        }

        return $this->data['choices'][0]['message']['content'] ?? null;
    }

    /**
     * Get the model used for the response
     */
    public function getModel(): ?string
    {
        if (!$this->success || !$this->data) {
            return null;
        }

        return $this->data['model'] ?? null;
    }

    /**
     * Get usage statistics if available
     */
    public function getUsage(): ?array
    {
        if (!$this->success || !$this->data) {
            return null;
        }

        return $this->data['usage'] ?? null;
    }

    /**
     * Check if the response has content
     */
    public function hasContent(): bool
    {
        return $this->getContent() !== null;
    }

    /**
     * Get an error message if the response is not successful
     */
    public function getErrorMessage(): ?string
    {
        return $this->success ? null : $this->error;
    }
}
