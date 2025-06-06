<?php

declare(strict_types=1);

namespace UtilityKit\Controller\Component;

use Cake\Controller\Component;
use Cake\Event\EventInterface;
use Cake\Http\Response;
use Cake\Utility\Hash;
use UtilityKit\Http\JsonResponse;

/**
 * AjaxResponseComponent component
 */
class AjaxResponseComponent extends Component
{
    const STRATEGY_HTML = 'html';
    const STRATEGY_JSON = 'json';

    /**
     * Default configuration.
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'strategy' => self::STRATEGY_HTML,
        'ajaxClassName' => 'Ajax',
        'modalSuccessKey' => 'X-Ajax-Success',
    ];

    /**
     * @var array
     */
    protected array $options = [];

    protected bool $preRendered = false;

    /**
     * @param array $config
     * @return void
     */
    public function initialize(array $config): void
    {
        $controller = $this->getController();
        if ($controller->getRequest()->is('ajax')) {
            $controller->viewBuilder()->setClassName($this->getConfig('ajaxClassName'));
        }
    }

    public function beforeRender(EventInterface $event): void
    {
        if ($this->preRendered) {
            return;
        }

        $this->preRendered = true;
        $event->setResult($this->success());
    }

    /**
     * @param array $options
     * @return self
     */
    public function setOptions(array $options): self
    {
        $this->options = Hash::merge($this->options, $options);

        return $this;
    }

    /**
     * @param boolean $success
     * @param array $options
     * @return Response
     */
    public function send(bool $success, array $options = []): Response
    {
        $options = Hash::merge($this->options, $options);
        $options['success'] = $success;
        $this->options = [];

        return match ($this->getConfig('strategy')) {
            self::STRATEGY_HTML => $this->handleHtmlResponse($options),
            self::STRATEGY_JSON => $this->handleJsonResponse($options),
            default => throw new \RuntimeException('Invalid strategy'),
        };
    }

    /**
     * @param string|null $message
     * @param array $options
     * @return Response
     */
    public function success(string $message = null, array $options = []): Response
    {
        $options['message'] = $message ?? __('Operation successful.');
        $options['status'] ??= 200;

        return $this->send(true, $options);
    }

    /**
     * @param string|null $message
     * @param array $options
     * @return Response
     */
    public function error(string $message = null, array $options = []): Response
    {
        $options['message'] = $message ?? __('An error occurred.');
        $options['status'] ??= 400;

        return $this->send(false, $options);
    }

    /**
     * @param array $options
     * @return Response
     */
    protected function handleHtmlResponse(array $options = []): Response
    {
        $controller = $this->getController();
        $response = $controller->getResponse();

        $status = $options['status'] ?? $response->getStatusCode();
        $success = $options['success'] ?? true;

        return $response
            ->withStatus($status)
            ->withBody($controller->render()->getBody())
            ->withHeader($this->getConfig('modalSuccessKey'), $success ? '1' : '0');
    }

    /**
     * @param array $options
     * @return Response
     */
    protected function handleJsonResponse(array $options = []): Response
    {
        $controller = $this->getController();
        $response = $controller->getResponse();

        $options = Hash::merge([
            'status' => $response->getStatusCode(),
            'success' => true,
            'message' => __('Operation successful.'),
            'data' => null,
            'meta' => null,
            'html' => (string) $controller->render()->getBody(),
        ], $this->options, $options);

        return JsonResponse::create($response)
            ->success($options['success'])
            ->status($options['status'])
            ->message($options['message'])
            ->data($options['data'])
            ->meta($options['meta'])
            ->html($options['html'])
            ->build();
    }
}
