<?php

namespace App\Http\Services\OpenRouter;

class OpenRouterRequestDTO
{
    public function __construct(
        public readonly string $model,
        public readonly array $messages,
        public readonly ?float $temperature = null,
        public readonly ?int $maxTokens = null,
        public readonly ?float $topP = null,
        public readonly ?int $n = null,
        public readonly ?bool $stream = null,
        public readonly ?array $stop = null,
        public readonly ?float $presencePenalty = null,
        public readonly ?float $frequencyPenalty = null,
        public readonly ?array $logitBias = null,
        public readonly ?string $user = null
    ) {}

    /**
     * Create DTO from an array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            model: $data['model'] ?? '',
            messages: $data['messages'] ?? [],
            temperature: $data['temperature'] ?? null,
            maxTokens: $data['max_tokens'] ?? null,
            topP: $data['top_p'] ?? null,
            n: $data['n'] ?? null,
            stream: $data['stream'] ?? null,
            stop: $data['stop'] ?? null,
            presencePenalty: $data['presence_penalty'] ?? null,
            frequencyPenalty: $data['frequency_penalty'] ?? null,
            logitBias: $data['logit_bias'] ?? null,
            user: $data['user'] ?? null
        );
    }

    /**
     * Convert DTO to an array for API request
     */
    public function toArray(): array
    {
        $data = [
            'model' => $this->model,
            'messages' => $this->messages
        ];

        if ($this->temperature !== null) {
            $data['temperature'] = $this->temperature;
        }

        if ($this->maxTokens !== null) {
            $data['max_tokens'] = $this->maxTokens;
        }

        if ($this->topP !== null) {
            $data['top_p'] = $this->topP;
        }

        if ($this->n !== null) {
            $data['n'] = $this->n;
        }

        if ($this->stream !== null) {
            $data['stream'] = $this->stream;
        }

        if ($this->stop !== null) {
            $data['stop'] = $this->stop;
        }

        if ($this->presencePenalty !== null) {
            $data['presence_penalty'] = $this->presencePenalty;
        }

        if ($this->frequencyPenalty !== null) {
            $data['frequency_penalty'] = $this->frequencyPenalty;
        }

        if ($this->logitBias !== null) {
            $data['logit_bias'] = $this->logitBias;
        }

        if ($this->user !== null) {
            $data['user'] = $this->user;
        }

        return $data;
    }

    /**
     * Create a simple chat completion request
     */
    public static function createChatRequest(string $content, string $model, array $additionalOptions = []): self
    {
        $messages = [
            [
                'role' => 'user',
                'content' => $content
            ]
        ];

        return new self(
            model: $model,
            messages: $messages,
            temperature: $additionalOptions['temperature'] ?? null,
            maxTokens: $additionalOptions['max_tokens'] ?? null,
            topP: $additionalOptions['top_p'] ?? null,
            n: $additionalOptions['n'] ?? null,
            stream: $additionalOptions['stream'] ?? null,
            stop: $additionalOptions['stop'] ?? null,
            presencePenalty: $additionalOptions['presence_penalty'] ?? null,
            frequencyPenalty: $additionalOptions['frequency_penalty'] ?? null,
            logitBias: $additionalOptions['logit_bias'] ?? null,
            user: $additionalOptions['user'] ?? null
        );
    }
}
