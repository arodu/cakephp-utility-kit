<?php

declare(strict_types=1);

namespace UtilityKit\Controller\Component;

use Cake\Controller\Component;
use Cake\Core\Configure;
use Cake\Event\EventInterface;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Response;
use Cake\Routing\Router;
use Cake\Utility\Hash;
use Throwable;
use UtilityKit\Controller\Component\AjaxHandlerTrait;

class JsonComponent extends Component
{
    use AjaxHandlerTrait;

    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAIL = 'fail';
    public const STATUS_ERROR = 'error';

    protected array $_defaultConfig = [
        'actions' => [],
        'ajaxRequired' => true,
        'flashKey' => 'flash',
        'renderView' => false,
        'htmlField' => 'html',
        'messagesField' => 'messages',
    ];

    protected bool $_isSuccess = true;

    protected bool $_responseStopped = false;

    protected array $jsonData = [];

    protected ?bool $_renderViewOverride = null;

    public function beforeFilter(EventInterface $event): void
    {
        if (!$this->_isActionHandled()) {
            return;
        }
        $controller = $this->getController();
        if ($this->getConfig('ajaxRequired') && !$controller->getRequest()->is('ajax')) {
            throw new BadRequestException('This action must be accessed via an AJAX request.');
        }

        $controller->viewBuilder()->setClassName('Ajax');
        $controller->disableAutoRender();
    }

    public function beforeRedirect(EventInterface $event, $url, Response $response): void
    {
        if (!$this->_isActionHandled() || $this->_responseStopped) {
            return;
        }
        $response = $this->redirect(Router::url($url, true));
        $event->stopPropagation();
        $event->setResult($response);
    }

    /**
     * @inheritDoc
     */
    public function afterFilter(EventInterface $event): void
    {
        if (!$this->_isActionHandled() || $this->_responseStopped) {
            return;
        }

        $controller = $this->getController();
        $viewVars = $controller->viewBuilder()->getVars();
        $payloadData = [];

        $serializeKeys = $controller->viewBuilder()->getOption('serialize')
            ?? (array)($viewVars['_serialize'] ?? []);

        foreach ($serializeKeys as $key) {
            if (array_key_exists($key, $viewVars)) {
                $payloadData[$key] = $viewVars[$key];
            }
        }

        $response = $this->_isSuccess
            ? $this->success($payloadData)
            : $this->fail($payloadData);

        $event->setResult($response);
    }

    public function setData(array $data, bool $overwrite = false): self
    {
        $this->jsonData = $overwrite ? $data : Hash::merge($this->jsonData, $data);

        return $this;
    }

    /**
     * @param bool $isSuccess `true` para JSend::STATUS_SUCCESS, `false` para JSend::STATUS_FAIL.
     * @return $this
     */
    public function setSuccess(bool $isSuccess): self
    {
        $this->_isSuccess = $isSuccess;

        return $this;
    }

    /**
     * @param bool $enable Define si se debe renderizar la vista.
     * @return $this
     */
    public function withView(bool $enable = true): self
    {
        $this->_renderViewOverride = $enable;

        return $this;
    }

    public function getJsonData(): array
    {
        return $this->jsonData ?? [];
    }

    public function success(array $data = [], ?string $message = null): Response
    {
        $payload = $this->_buildPayload($data, $message);

        return $this->_buildResponse(self::STATUS_SUCCESS, $payload, 200);
    }

    public function fail(array $data = [], ?string $message = null, int $httpStatusCode = 400): Response
    {
        $payload = $this->_buildPayload($data, $message);

        return $this->_buildResponse(self::STATUS_FAIL, $payload, $httpStatusCode);
    }

    /**
     * @param string|\Throwable $source
     * @param array|null $data
     * @param int|null $httpStatusCode
     * @return \Cake\Http\Response
     */
    public function error(string|Throwable $source, ?array $data = null, ?int $httpStatusCode = null): Response
    {
        $message = '';
        $errorCode = 0;
        $debugInfo = [];

        if ($source instanceof Throwable) {
            $message = $source->getMessage();
            $errorCode = $source->getCode();

            if ($httpStatusCode === null) {
                $httpStatusCode = ($errorCode >= 400 && $errorCode < 600) ? $errorCode : 500;
            }

            if (Configure::read('debug')) {
                $debugInfo['debug'] = [
                    'class' => get_class($source),
                    'code' => $errorCode,
                    'file' => $source->getFile(),
                    'line' => $source->getLine(),
                    'trace' => $source->getTraceAsString(),
                ];
            }
        } else {
            $message = $source;
            $httpStatusCode ??= 500;
        }

        $payload = ['message' => $message];

        $finalData = array_merge($data ?? [], $debugInfo);
        if (!empty($finalData)) {
            $payload['data'] = $finalData;
        }

        return $this->_buildResponse(self::STATUS_ERROR, $payload, $httpStatusCode);
    }

    public function redirect($url, ?string $message = null): Response
    {
        $payload = $this->_buildPayload(['redirect' => Router::url($url, true)], $message);

        return $this->_buildResponse(
            status: self::STATUS_SUCCESS,
            payload: $payload,
            httpStatusCode: 200,
            renderView: false
        );
    }

    // --- MÉTODOS PROTEGIDOS (HELPERS) ---
    // (Sin cambios en _buildPayload, _buildResponse, _buildJsonResponse, _getFormattedFlashMessages)

    protected function _buildPayload(array $data, ?string $message): array
    {
        $messages = $this->_getFormattedFlashMessages();
        if ($message !== null) {
            $messages[] = [
                'message' => $message,
                'key' => 'flash',
                'element' => 'flash/info',
                'params' => [],
            ];
        }

        if (!empty($messages)) {
            $data[$this->getConfig('messagesField')] = $messages;
        }

        return $data;
    }

    protected function _buildResponse(string $status, array $payload, int $httpStatusCode, ?bool $renderView = null): Response
    {
        $this->_responseStopped = true;
        $jsend = ['status' => $status];

        if ($status === self::STATUS_ERROR) {
            $jsend = array_merge($jsend, $payload);
        } else {
            $jsend['data'] = ($status === self::STATUS_SUCCESS && empty($payload)) ? null : $payload;
        }

        $jsend['data'] = Hash::merge($jsend['data'] ?? [], $this->getJsonData());

        $shouldRenderView = $this->_renderViewOverride ?? $renderView ?? $this->getConfig('renderView');

        if ($shouldRenderView) {
            $jsend['data'][$this->getConfig('htmlField')] = (string) $this->getController()->render()->getBody();
        }

        $this->_renderViewOverride = null;

        return $this->_buildJsonResponse($jsend, $httpStatusCode);
    }

    protected function _buildJsonResponse(array $data, int $status = 200): Response
    {
        return $this->getController()->getResponse()
            ->withType('application/json')
            ->withStringBody((string)json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP))
            ->withStatus($status);
    }

    protected function _getFormattedFlashMessages(): array
    {
        $session = $this->getController()->getRequest()->getSession();
        $flashMessages = (array)$session->read('Flash.' . $this->getConfig('flashKey'));
        $session->delete('Flash');

        return $flashMessages;
    }
}
