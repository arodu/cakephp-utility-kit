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

class JsonComponent extends Component
{
    use AjaxHandlerTrait;

    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAIL = 'fail';
    public const STATUS_ERROR = 'error';

    /**
     * @var array
     */
    protected array $_defaultConfig = [
        'actions' => [],
        'ajaxRequired' => true,
        'flashKey' => 'flash',
        'renderView' => false,
        'htmlField' => 'html',
        'messagesField' => 'messages',
    ];

    /**
     * @var bool
     */
    protected bool $_isSuccess = true;

    /**
     * @var bool
     */
    protected bool $_responseStopped = false;

    /**
     * @var array
     */
    protected array $jsonData = [];

    /**
     * @var bool|null
     */
    protected ?bool $_renderViewOverride = null;

    /**
     * @param \Cake\Event\EventInterface $event
     * @return void
     */
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

    /**
     * @param \Cake\Event\EventInterface $event
     * @param array|string|null $url
     * @param \Cake\Http\Response $response
     * @return void
     */
    public function beforeRedirect(EventInterface $event, string|array|null $url, Response $response): void
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

        $response = $this->buildResponse($this->_isSuccess, $payloadData);

        $event->setResult($response);
    }

    /**
     * @param array $data
     * @param bool $overwrite
     * @return self
     */
    public function setData(array $data, bool $overwrite = false): self
    {
        $this->jsonData = $overwrite ? $data : Hash::merge($this->jsonData, $data);

        return $this;
    }

    /**
     * @param int $code
     * @return self
     * @throws \Cake\Http\Exception\BadRequestException
     */
    public function setCode(int $code): self
    {
        if ($code < 100 || $code >= 600) {
            throw new BadRequestException('Invalid HTTP status code provided.');
        }

        $this->getController()->getResponse()->withStatus($code);

        return $this;
    }

    /**
     * @param string $message
     * @return self
     */
    public function setMessage(string $message): self
    {
        $this->jsonData['message'] = $message;

        return $this;
    }

    /**
     * @param array $meta
     * @return self
     */
    public function setMeta(array $meta): self
    {
        $this->jsonData['meta'] = $meta;

        return $this;
    }

    /**
     * @param bool $isSuccess `true` para JSend::STATUS_SUCCESS, `false` para JSend::STATUS_FAIL.
     * @return self
     */
    public function setSuccess(bool $isSuccess): self
    {
        $this->_isSuccess = $isSuccess;

        return $this;
    }

    /**
     * @param bool $enable Define si se debe renderizar la vista.
     * @return self
     */
    public function withView(bool $enable = true): self
    {
        $this->_renderViewOverride = $enable;

        return $this;
    }

    /**
     * @return array
     */
    public function getJsonData(): array
    {
        return $this->jsonData ?? [];
    }

    /**
     * @param array $data
     * @param string|null $message
     * @return \Cake\Http\Response
     */
    public function success(array $data = [], ?string $message = null): Response
    {
        return $this->buildResponse(true, $data, $message);
    }

    /**
     * @param array $data
     * @param string|null $message
     * @param int $httpStatusCode
     * @return \Cake\Http\Response
     */
    public function fail(array $data = [], ?string $message = null, int $httpStatusCode = 400): Response
    {
        return $this->buildResponse(false, $data, $message, $httpStatusCode);
    }

    /**
     * @param bool $isSuccess
     * @param array $data
     * @param string|null $message
     * @param int|null $httpStatusCode
     * @return \Cake\Http\Response
     */
    public function buildResponse(
        bool $isSuccess,
        array $data = [],
        ?string $message = null,
        ?int $httpStatusCode = null,
    ): Response {
        $status = $isSuccess ? self::STATUS_SUCCESS : self::STATUS_FAIL;

        $finalHttpStatusCode = $httpStatusCode ?? ($isSuccess ? 200 : 400);

        $payload = $this->_buildPayload($data, $message);

        return $this->_createJsendResponse($status, $payload, $finalHttpStatusCode);
    }

    /**
     * @param \Throwable|string $source
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
                $httpStatusCode = $errorCode >= 400 && $errorCode < 600 ? $errorCode : 500;
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

        return $this->_createJsendResponse(self::STATUS_ERROR, $payload, $httpStatusCode);
    }

    /**
     * @param array|string|null $url
     * @param string|null $message
     * @return \Cake\Http\Response
     */
    public function redirect(string|array|null $url, ?string $message = null): Response
    {
        $payload = $this->_buildPayload(['redirect' => Router::url($url, true)], $message);

        return $this->_createJsendResponse(
            status: self::STATUS_SUCCESS,
            payload: $payload,
            httpStatusCode: 200,
            renderView: false,
        );
    }

    /**
     * @param array $data
     * @param string|null $message
     * @return array
     */
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

    /**
     * @param string $status
     * @param array $payload
     * @param int $httpStatusCode
     * @param bool|null $renderView
     * @return \Cake\Http\Response
     */
    protected function _createJsendResponse(
        string $status,
        array $payload,
        int $httpStatusCode,
        ?bool $renderView = null,
    ): Response {
        $this->_responseStopped = true;
        $jsend = ['status' => $status];

        if ($status === self::STATUS_ERROR) {
            $jsend = array_merge($jsend, $payload);
        } else {
            $jsend['data'] = $status === self::STATUS_SUCCESS && empty($payload) ? null : $payload;
        }

        $jsend['data'] = Hash::merge($jsend['data'] ?? [], $this->getJsonData());

        $shouldRenderView = $this->_renderViewOverride ?? $renderView ?? $this->getConfig('renderView');
        if ($shouldRenderView) {
            $jsend['data'][$this->getConfig('htmlField')] = (string)$this->getController()->render()->getBody();
        }
        $this->_renderViewOverride = null;

        return $this->_buildJsonResponse($jsend, $httpStatusCode);
    }

    /**
     * @param array $data
     * @param int $status
     * @return \Cake\Http\Response
     */
    protected function _buildJsonResponse(array $data, int $status = 200): Response
    {
        return $this->getController()->getResponse()
            ->withType('application/json')
            ->withStringBody((string)json_encode(
                $data,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG,
                JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP,
            ))
            ->withStatus($status);
    }

    /**
     * @return array
     */
    protected function _getFormattedFlashMessages(): array
    {
        $session = $this->getController()->getRequest()->getSession();
        $flashMessages = (array)$session->read('Flash.' . $this->getConfig('flashKey'));
        $session->delete('Flash');

        return $flashMessages;
    }
}
