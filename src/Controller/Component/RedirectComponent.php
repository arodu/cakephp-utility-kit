<?php

declare(strict_types=1);

namespace UtilityKit\Controller\Component;

use Cake\Controller\Component;
use Cake\Core\Configure;
use Cake\Event\EventInterface;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\Routing\Router;
use Cake\Utility\Hash;

/**
 * Redirect component
 */
class RedirectComponent extends Component
{
    /**
     * Default configuration.
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'key' => 'redirect',
        'enable' => true,
    ];

    public function initialize(array $config): void
    {
        $config = Hash::merge(Configure::read('BootstrapTools.redirect', []), $config);
        $this->setConfig($config);
    }

    /**
     * @param EventInterface $event
     * @return void
     */
    public function beforeRender(EventInterface $event): void
    {
        if (!$this->isRedirectEnabled()) {
            return;
        }

        $controller = $this->getController();
        $redirect = $this->getRedirectUrl($controller->getRequest());
        $controller->set($this->getConfig('key'), $redirect);
    }

    /**
     * @param EventInterface $event
     * @param mixed $url
     * @param Response $response
     * @return void
     */
    public function beforeRedirect(EventInterface $event, $url, Response $response): void
    {
        if (!$this->isRedirectEnabled()) {
            return;
        }

        $controller = $this->getController();
        $redirect = $this->getRedirectUrl($controller->getRequest());
        $url = Router::url($redirect ?? $url, true);
        $response = $response->withLocation($url);
        $event->setResult($response);
    }

    /**
     * @param ServerRequest $request
     * @param array $config
     * @return string|null
     */
    protected function getRedirectUrl(ServerRequest $request): ?string
    {
        return $request->getQuery($this->getConfig('key'))
            ?? $request->getData($this->getConfig('key'))
            ?? null;
    }

    /**
     * @return boolean
     */
    public function isRedirectEnabled(): bool
    {
        return $this->getConfig('enable', false);
    }
}
