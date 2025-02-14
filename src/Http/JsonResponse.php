<?php

declare(strict_types=1);

namespace BootstrapTools\Http;

use Cake\Http\Response;
use Cake\Log\Log;

/**
 * Clase que implementa el patrón Builder para construir respuestas JSON.
 */
class JsonResponse
{
    protected Response $response;

    protected bool $success = true;
    protected int $status = 200;
    protected array $data = [];
    protected string $message = '';
    protected array $meta = [];
    protected mixed $errors = null;
    protected ?string $html = null;

    public function __construct(Response $response)
    {
        $this->response = $response;
    }

    public static function create(Response $response): self
    {
        return new self($response);
    }

    public function success(bool $success = true): self
    {
        $this->success = $success;

        return $this;
    }

    public function status(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function message(string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function data(array $data): self
    {
        $this->data = $data;

        return $this;
    }

    public function addData(string $key, mixed $value): self
    {
        $this->data[$key] = $value;

        return $this;
    }

    public function meta(array $meta): self
    {
        $this->meta = $meta;

        return $this;
    }

    public function addMeta(string $key, mixed $value): self
    {
        $this->meta[$key] = $value;

        return $this;
    }

    public function errors(mixed $errors): self
    {
        $this->errors = $errors;

        return $this;
    }

    public function html(string $html): self
    {
        $this->html = $html;

        return $this;
    }

    public function build(): Response
    {
        if (!$this->success) {
            Log::error("Error: {$this->message}. Details: " . json_encode($this->errors ?? ''));
        }

        $responseBody = [
            'success'  => $this->success ?? true,
            'message' => $this->message ?? __('Operation completed successfully'),
        ];

        if (!empty($this->data)) {
            $responseBody['data'] = $this->data;
        }

        if (!empty($this->meta)) {
            $responseBody['meta'] = $this->meta;
        }

        if ($this->status === 'error') {
            $responseBody['errors'] = is_array($this->errors) ? $this->errors : ['general' => $this->errors];
        }

        if ($this->html !== null) {
            $responseBody['html'] = $this->html;
        }

        return $this->response
            ->withStatus($this->status)
            ->withType('application/json')
            ->withStringBody(json_encode(
                $responseBody,
                JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
            ));
    }
}
