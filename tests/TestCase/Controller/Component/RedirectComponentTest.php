<?php
declare(strict_types=1);

namespace UtilityKit\Test\TestCase\Controller\Component;

use Cake\Controller\ComponentRegistry;
use Cake\Controller\Controller;
use Cake\Event\Event;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use UtilityKit\Controller\Component\RedirectComponent;

class RedirectComponentTest extends TestCase
{
    protected function makeComponent(array $config = []): RedirectComponent
    {
        $controller = new Controller(new ServerRequest());
        $registry = new ComponentRegistry($controller);

        return new RedirectComponent($registry, $config);
    }

    public function testDefaultConfig(): void
    {
        $component = $this->makeComponent();

        $this->assertSame('redirect', $component->getConfig('key'));
        $this->assertTrue($component->getConfig('enable'));
    }

    public function testIsRedirectEnabledDefault(): void
    {
        $component = $this->makeComponent();

        $this->assertTrue($component->isRedirectEnabled());
    }

    public function testIsRedirectEnabledDisabled(): void
    {
        $component = $this->makeComponent(['enable' => false]);

        $this->assertFalse($component->isRedirectEnabled());
    }

    public function testEnableTurnsRedirectOn(): void
    {
        $component = $this->makeComponent(['enable' => false]);

        $component->enable();

        $this->assertTrue($component->isRedirectEnabled());
    }

    public function testEnableFalseTurnsRedirectOff(): void
    {
        $component = $this->makeComponent();

        $component->enable(false);

        $this->assertFalse($component->isRedirectEnabled());
    }

    public function testBeforeRenderEnabledSetsViewVar(): void
    {
        $request = new ServerRequest(['url' => '/articles', 'query' => ['redirect' => '/target']]);
        $controller = new Controller($request);
        $registry = new ComponentRegistry($controller);
        $component = new RedirectComponent($registry, ['enable' => true]);

        $event = new Event('Controller.beforeRender', $controller);
        $component->beforeRender($event);

        $this->assertSame('/target', $controller->viewBuilder()->getVar('redirect'));
    }

    public function testBeforeRenderDisabledDoesNotSetViewVar(): void
    {
        $request = new ServerRequest(['url' => '/articles', 'query' => ['redirect' => '/target']]);
        $controller = new Controller($request);
        $registry = new ComponentRegistry($controller);
        $component = new RedirectComponent($registry, ['enable' => false]);

        $event = new Event('Controller.beforeRender', $controller);
        $component->beforeRender($event);

        $this->assertNull($controller->viewBuilder()->getVar('redirect'));
    }

    public function testBeforeRedirectRewritesLocationFromQuery(): void
    {
        $request = new ServerRequest(['url' => '/articles', 'query' => ['redirect' => '/from-query']]);
        $controller = new Controller($request);
        $registry = new ComponentRegistry($controller);
        $component = new RedirectComponent($registry, ['enable' => true]);

        $url = '/somewhere-else';
        $response = new Response();
        $event = new Event('Controller.beforeRedirect', $controller, [$url, $response]);
        $component->beforeRedirect($event, $url, $response);

        $this->assertInstanceOf(Response::class, $event->getResult());
        $this->assertSame('http://localhost/from-query', $event->getResult()->getHeaderLine('Location'));
    }

    public function testBeforeRedirectWithoutQueryKeepsOriginalUrl(): void
    {
        $request = new ServerRequest(['url' => '/articles']);
        $controller = new Controller($request);
        $registry = new ComponentRegistry($controller);
        $component = new RedirectComponent($registry, ['enable' => true]);

        $url = '/somewhere-else';
        $response = new Response();
        $event = new Event('Controller.beforeRedirect', $controller, [$url, $response]);
        $component->beforeRedirect($event, $url, $response);

        $this->assertInstanceOf(Response::class, $event->getResult());
        $this->assertSame('http://localhost/somewhere-else', $event->getResult()->getHeaderLine('Location'));
    }

    public function testBeforeRedirectDisabledDoesNotTouchResponse(): void
    {
        $request = new ServerRequest(['url' => '/articles', 'query' => ['redirect' => '/from-query']]);
        $controller = new Controller($request);
        $registry = new ComponentRegistry($controller);
        $component = new RedirectComponent($registry, ['enable' => false]);

        $url = '/somewhere-else';
        $response = new Response();
        $event = new Event('Controller.beforeRedirect', $controller, [$url, $response]);
        $component->beforeRedirect($event, $url, $response);

        $this->assertNull($event->getResult());
    }

    public function testBeforeRenderPrefersQueryOverData(): void
    {
        $request = new ServerRequest([
            'url' => '/articles',
            'query' => ['redirect' => '/from-query'],
            'post' => ['redirect' => '/from-data'],
        ]);
        $controller = new Controller($request);
        $registry = new ComponentRegistry($controller);
        $component = new RedirectComponent($registry, ['enable' => true]);

        $event = new Event('Controller.beforeRender', $controller);
        $component->beforeRender($event);

        $this->assertSame('/from-query', $controller->viewBuilder()->getVar('redirect'));
    }

    public function testBeforeRenderFallsBackToData(): void
    {
        $request = new ServerRequest([
            'url' => '/articles',
            'post' => ['redirect' => '/from-data'],
        ]);
        $controller = new Controller($request);
        $registry = new ComponentRegistry($controller);
        $component = new RedirectComponent($registry, ['enable' => true]);

        $event = new Event('Controller.beforeRender', $controller);
        $component->beforeRender($event);

        $this->assertSame('/from-data', $controller->viewBuilder()->getVar('redirect'));
    }

    public function testBeforeRenderUsesCustomKey(): void
    {
        $request = new ServerRequest([
            'url' => '/articles',
            'query' => ['customkey' => '/custom-target'],
        ]);
        $controller = new Controller($request);
        $registry = new ComponentRegistry($controller);
        $component = new RedirectComponent($registry, ['enable' => true, 'key' => 'customkey']);

        $event = new Event('Controller.beforeRender', $controller);
        $component->beforeRender($event);

        $this->assertSame('/custom-target', $controller->viewBuilder()->getVar('customkey'));
    }
}
