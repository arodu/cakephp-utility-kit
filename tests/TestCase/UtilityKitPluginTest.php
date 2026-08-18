<?php
declare(strict_types=1);

namespace UtilityKit\Test\TestCase;

use Cake\Console\CommandCollection;
use Cake\Core\ContainerInterface;
use Cake\Core\PluginApplicationInterface;
use Cake\Event\EventDispatcherTrait;
use Cake\Http\MiddlewareQueue;
use Cake\Routing\RouteBuilder;
use Cake\Routing\RouteCollection;
use Cake\TestSuite\TestCase;
use UtilityKit\UtilityKitPlugin;

class UtilityKitPluginTest extends TestCase
{
    protected function plugin(): UtilityKitPlugin
    {
        return new UtilityKitPlugin();
    }

    protected function app(): object
    {
        return new class implements PluginApplicationInterface {
            use EventDispatcherTrait;

            public function addPlugin($name, array $config = [])
            {
                return $this;
            }

            public function pluginBootstrap(): void
            {
            }

            public function pluginRoutes(RouteBuilder $routes): RouteBuilder
            {
                return $routes;
            }

            public function pluginMiddleware(MiddlewareQueue $middleware): MiddlewareQueue
            {
                return $middleware;
            }

            public function pluginConsole(CommandCollection $commands): CommandCollection
            {
                return $commands;
            }
        };
    }

    public function testBootstrapDoesNotThrow(): void
    {
        $app = $this->app();
        $plugin = $this->plugin();

        $plugin->bootstrap($app);

        $this->assertTrue(true);
    }

    public function testRoutesRegistersPluginPath(): void
    {
        $plugin = $this->plugin();
        $routes = new RouteBuilder(new RouteCollection(), '/');

        // Should not throw while registering the `/utility-kit` plugin scope.
        $plugin->routes($routes);

        $this->assertTrue(true);
    }

    public function testMiddlewareReturnsSameQueue(): void
    {
        $plugin = $this->plugin();
        $queue = new MiddlewareQueue();

        $result = $plugin->middleware($queue);

        $this->assertSame($queue, $result);
    }

    public function testConsoleReturnsCommandCollection(): void
    {
        $plugin = $this->plugin();
        $commands = new CommandCollection();

        $result = $plugin->console($commands);

        $this->assertInstanceOf(CommandCollection::class, $result);
    }

    public function testServicesDoesNotThrow(): void
    {
        $plugin = $this->plugin();
        $container = $this->createMock(ContainerInterface::class);

        $plugin->services($container);

        $this->assertTrue(true);
    }
}
