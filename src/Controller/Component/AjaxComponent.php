<?php

declare(strict_types=1);

namespace UtilityKit\Controller\Component;

use Cake\Controller\Component;
use Cake\Event\EventInterface;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Response;
use Cake\Routing\Router;
use Cake\Utility\Hash;

/**
 * AjaxComponent component
 *
 * This component provides functionality for handling AJAX requests and responses in CakePHP applications.
 * It supports both HTML and JSON strategies for AJAX responses, allowing for flexible handling of AJAX requests.
 */
class AjaxComponent extends Component
{
    public const STRATEGY_HTML = 'html';
    public const STRATEGY_JSON = 'json';

    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAIL = 'fail';
    public const STATUS_ERROR = 'error';

    protected array $_defaultConfig = [
        'strategy' => self::STRATEGY_HTML,
        'excludedActions' => [], // actions that do not require AJAX requests, even if strategy is HTML
        'ajaxClassName' => 'Ajax', // name of the AJAX view class to use

        'jsonOptions' => [
            'renderHtml' => true, // if true, the rendered HTML view will be included in the response
            'serializeAll' => false, // if true, all viewVars will be serialized in the JSON response
            'field' => [
                'html' => 'html',
                'messages' => 'messages',
                'redirectUrl' => 'redirectUrl',
            ],
        ],

        'ajaxRequired' => true, // if true, all actions except those in excludedActions must be AJAX requests
    ];

    /**
     * @inheritDoc
     */
    public function beforeFilter(EventInterface $event): void
    {
        $controller = $this->getController();
        $action = $controller->getRequest()->getParam('action');
        $excludedActions = $this->getConfig('excludedActions', []);

        if (in_array($action, $excludedActions)) {
            return;
        }

        if ($this->getConfig('ajaxRequired')) {
            $controller->getRequest()->allowMethod('ajax');
        }

        $controller->viewBuilder()->setClassName($this->getConfig('ajaxClassName'));
    }

    /**
     * @inheritDoc
     */
    public function afterFilter(EventInterface $event): void
    {
        $action = $this->getController()->getRequest()->getParam('action');
        $excludedActions = $this->getConfig('excludedActions', []);

        if (in_array($action, $excludedActions)) {
            return;
        }

        if ($this->getConfig('strategy') === self::STRATEGY_JSON) {
            if ($this->getConfig('jsonOptions.renderHtml', true)) {
                $this->handleJsonWithHtml($event);
            } else {
                $this->handleJson($event);
            }
        }
    }

    /**
     * @param EventInterface $event
     * @return void
     */
    public function handleJsonWithHtml(EventInterface $event): void
    {
        $controller = $this->getController();
        $htmlField = $this->getConfig('jsonOptions.field.html', 'html');

        $data[$htmlField] = (string) $controller->render()->getBody();
        $data = Hash::merge($data, $this->getJsonData());

        $response = $this->buildJsonResponse([
            'status' => self::STATUS_SUCCESS,
            'data' => $data,
        ], 200);

        $event->setResult($response);
    }

    protected array $jsonData = [];

    /**
     * Set additional JSON data to be included in the response.
     * NOTE: this method only works if the strategy is set to JSON.
     *
     * @param array $data The data to be included in the JSON response.
     * @param bool $overwrite If true, the existing JSON data will be overwritten. If false, the new data will be merged with the existing data.
     * @return self
     */
    public function setJsonData(array $data, bool $overwrite = false): self
    {
        if ($overwrite) {
            $this->jsonData = $data;
        } else {
            $this->jsonData = Hash::merge($this->jsonData, $data);
        }

        return $this;
    }

    /**
     * Get the JSON data that will be included in the response.
     * NOTE: this method only works if the strategy is set to JSON.
     *
     * @return array The JSON data to be included in the response.
     */
    public function getJsonData(): array
    {
        return $this->jsonData ?? [];
    }
    /**
     * Build a JSON response with the given data and status code.
     *
     * @param array $data The data to be included in the JSON response.
     * @param int $status The HTTP status code for the response.
     * @return Response The JSON response.
     */
    protected function buildJsonResponse(array $data, int $status = 200): Response
    {
        $response = $this->getController()->getResponse();

        return $response
            ->withType('application/json')
            ->withStringBody(json_encode($data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP))
            ->withStatus($status);
    }

    // ---------------------------------------

    protected function getFormattedFlashMessages(\Cake\Http\Session $session): array
    {
        // ... (sin cambios respecto a la versión anterior)
        $rawFlashMessages = (array)$session->read('Flash');
        $session->delete('Flash');
        $formattedMessages = [];
        if (!empty($rawFlashMessages)) {
            foreach ($rawFlashMessages as $flashKey => $flashMessageArray) {
                if (isset($flashMessageArray['message'], $flashMessageArray['element'])) {
                    $elementType = $flashMessageArray['element'];
                    if (str_starts_with($elementType, 'Flash/')) {
                        $elementType = substr($elementType, strlen('Flash/'));
                    }
                    $formattedMessages[] = [
                        'text' => $flashMessageArray['message'],
                        'type' => $elementType,
                        'key' => $flashKey,
                    ];
                }
            }
        }
        return $formattedMessages;
    }

    public function handleRequest(EventInterface $event): void
    {
        // ... (sin cambios para la lógica de estrategia 'Html')
        $controller = $this->getController();
        $request = $controller->getRequest();
        $action = $request->getParam('action');
        $strategy = $this->getConfig('strategy');
        $excludedActions = (array)$this->getConfig('excludedActions');

        if ($strategy === self::STRATEGY_HTML) {
            if (!$request->is('ajax') && !in_array($action, $excludedActions, true)) {
                throw new BadRequestException(__('This action requires an AJAX request.'));
            }
            if ($request->is('ajax')) {
                $controller->viewBuilder()->setClassName($this->getConfig('ajaxClassName'));
            }
        }
    }

    public function handleJson(EventInterface $event): void
    {
        $controller = $this->getController();

        $flashMessages = $this->getFormattedFlashMessages($controller->getRequest()->getSession());

        $currentViewVars = $controller->viewBuilder()->getVars();
        $serializeKeys = $currentViewVars['_serialize'] ?? [];
        if (is_string($serializeKeys)) {
            $serializeKeys = [$serializeKeys];
        }


        $controllerPayload = []; // Datos que el controlador quiere explícitamente en el JSend 'data'
        if (is_array($serializeKeys)) {
            foreach ($serializeKeys as $key) {
                if (array_key_exists($key, $currentViewVars)) {
                    $controllerPayload[$key] = $currentViewVars[$key];
                }
            }
        }

        debug($controllerPayload);
        exit();

        $jsendStatus = self::STATUS_SUCCESS; // Por defecto, el estado es 'success'
        $jsendDataForPayload = $controllerPayload; // Inicia el payload con los datos del controlador

        // Determinar status 'fail' si el controlador lo indica (ej. 'success' => false)
        if (isset($jsendDataForPayload['success']) && $jsendDataForPayload['success'] === false) {
            $jsendStatus = self::STATUS_FAIL;
            // 'success' no es parte del payload 'data' en JSend, ya está en 'status'
            unset($jsendDataForPayload['success']);
        } elseif (isset($jsendDataForPayload['success'])) {
            // También quitar 'success': true del payload, ya que se representa en 'status'
            unset($jsendDataForPayload['success']);
        }

        // Renderizar vista a HTML si está configurado
        $jsonRenderViewToHtml = (bool)$this->getConfig('jsonRenderViewToHtml');
        if ($jsonRenderViewToHtml) {
            $jsonHtmlField = (string)$this->getConfig('jsonHtmlField');
            $view = $controller->createView();
            try {
                if (!$view->getTemplatePath()) {
                    $view->setTemplatePath($controller->getName());
                }
                if (!$view->getTemplate()) {
                    $view->setTemplate($controller->getRequest()->getParam('action'));
                }
                $jsendDataForPayload[$jsonHtmlField] = $view->render();
            } catch (\Throwable $e) {
                // Error al renderizar la vista: esto es un JSend 'error'
                $finalResponse = [
                    'status' => self::STATUS_ERROR,
                    'message' => 'View rendering failed: ' . $e->getMessage(),
                    // Podrías añadir 'code' o 'data' con detalles del error si es necesario
                    // 'code' => 'VIEW_ERROR',
                    // 'data' => ['template' => $view->getTemplate(), 'path' => $view->getTemplatePath()]
                ];
                $controller->set($finalResponse);
                $controller->set('_serialize', array_keys($finalResponse));

                $response = $controller->getResponse()
                    ->withType('application/json')
                    ->withStringBody((string)json_encode($finalResponse))
                    ->withStatus(500); // HTTP 500 para indicar error interno del servidor

                $event->setResult($response);
                $event->stopPropagation(); // Detener la propagación del evento

                return;
            }
        }

        // Añadir mensajes Flash al payload 'data'
        $jsonMessagesField = (string)$this->getConfig('jsonMessagesField');
        if (!empty($flashMessages)) {
            $jsendDataForPayload[$jsonMessagesField] = $flashMessages;
        }

        // Construir la respuesta JSend final
        $finalResponse = ['status' => $jsendStatus];
        if ($jsendStatus === 'success') {
            $finalResponse['data'] = empty($jsendDataForPayload) ? null : $jsendDataForPayload;
        } else { // 'fail'
            // Para 'fail', 'data' debe estar presente, incluso si es un array/objeto vacío.
            $finalResponse['data'] = $jsendDataForPayload;
        }

        $controller->set($finalResponse);
        $controller->set('_serialize', array_keys($finalResponse)); // Debería ser ['status', 'data']

        $response = $controller->getResponse()
            ->withType('application/json')
            ->withStringBody((string)json_encode($finalResponse))
            ->withStatus(200); // HTTP 200 para que el cliente JS procese el JSend
        $event->setResult($response);
    }


    public function handleRedirect(EventInterface $event, $url, \Cake\Http\Response $response)
    {
        if ($this->getConfig('strategy') !== self::STRATEGY_JSON) {
            return null;
        }

        $controller = $this->getController();
        $normalizedUrl = Router::url($url, true);
        $flashMessages = $this->getFormattedFlashMessages($controller->getRequest()->getSession());

        $jsendDataPayload = []; // Datos para el JSend 'data'

        $jsonRedirectUrlField = (string)$this->getConfig('jsonRedirectUrlField');
        $jsendDataPayload[$jsonRedirectUrlField] = $normalizedUrl;

        $jsonMessagesField = (string)$this->getConfig('jsonMessagesField');
        if (!empty($flashMessages)) {
            $jsendDataPayload[$jsonMessagesField] = $flashMessages;
        }
        // Opcional: podrías añadir una clave para indicar explícitamente la acción de redirección
        // $jsendDataPayload['_action'] = 'redirect';

        $finalResponse = [
            'status' => 'success', // Una redirección es un tipo de "éxito"
            'data' => $jsendDataPayload,
        ];

        $event->stopPropagation();
        $event->setResult($response);

        return $controller->getResponse()
            ->withType('application/json')
            ->withStringBody((string)json_encode($finalResponse))
            ->withStatus(200); // HTTP 200 para que el cliente JS procese el JSend
    }
}
