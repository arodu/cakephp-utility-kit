<?php
declare(strict_types=1);

namespace UtilityKit\Test\TestCase\Controller\Component;

use Cake\Controller\ComponentRegistry;
use Cake\Controller\Controller;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use RuntimeException;
use UtilityKit\Controller\Component\JsonComponent;

class JsonComponentTest extends TestCase
{
    protected function makeComponent(array $config = []): JsonComponent
    {
        $controller = new Controller(new ServerRequest());
        $registry = new ComponentRegistry($controller);

        return new JsonComponent($registry, $config);
    }

    protected function bodyArray(Controller $controller, Response $response): array
    {
        $body = json_decode((string)$response->getBody(), true);

        $this->assertIsArray($body);

        return $body;
    }

    public function testSuccessResponse(): void
    {
        $component = $this->makeComponent();
        $response = $component->success(['id' => 1]);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('application/json', $response->getType());

        $body = $this->bodyArray($component->getController(), $response);
        $this->assertSame('success', $body['status']);
        $this->assertSame(['id' => 1], $body['data']);
    }

    public function testSuccessWithEmptyDataIsEmptyArray(): void
    {
        $component = $this->makeComponent();
        $response = $component->success();

        $body = $this->bodyArray($component->getController(), $response);
        $this->assertSame('success', $body['status']);
        $this->assertSame([], $body['data']);
    }

    public function testFailResponse(): void
    {
        $component = $this->makeComponent();
        $response = $component->fail(['error' => 'bad'], 'Invalid input');

        $this->assertSame(400, $response->getStatusCode());

        $body = $this->bodyArray($component->getController(), $response);
        $this->assertSame('fail', $body['status']);
        $this->assertArrayHasKey('data', $body);
    }

    public function testBuildResponseIsJsend(): void
    {
        $component = $this->makeComponent();
        $response = $component->buildResponse(true, ['a' => 1], null, 201);

        $this->assertSame(201, $response->getStatusCode());

        $body = $this->bodyArray($component->getController(), $response);
        $this->assertSame('success', $body['status']);
        $this->assertSame(['a' => 1], $body['data']);
    }

    public function testErrorFromString(): void
    {
        $component = $this->makeComponent();
        $response = $component->error('Something broke');

        $this->assertSame(500, $response->getStatusCode());

        $body = $this->bodyArray($component->getController(), $response);
        $this->assertSame('error', $body['status']);
        $this->assertSame('Something broke', $body['message']);
    }

    public function testErrorFromThrowable(): void
    {
        $component = $this->makeComponent(['actions' => null]);
        $response = $component->error(new RuntimeException('Boom', 500));

        $this->assertSame(500, $response->getStatusCode());

        $body = $this->bodyArray($component->getController(), $response);
        $this->assertSame('error', $body['status']);
        $this->assertSame('Boom', $body['message']);
    }

    public function testSetDataAndGetJsonData(): void
    {
        $component = $this->makeComponent();

        $this->assertSame($component, $component->setData(['a' => 1]));
        $this->assertSame($component, $component->setData(['b' => 2]));

        $this->assertSame(['a' => 1, 'b' => 2], $component->getJsonData());
    }

    public function testSetDataOverwrite(): void
    {
        $component = $this->makeComponent();
        $component->setData(['a' => 1]);
        $component->setData(['x' => 9], true);

        $this->assertSame(['x' => 9], $component->getJsonData());
    }

    public function testSetMessageSetMeta(): void
    {
        $component = $this->makeComponent();

        $this->assertSame($component, $component->setMessage('hi'));
        $this->assertSame($component, $component->setMeta(['page' => 2]));

        $this->assertSame('hi', $component->getJsonData()['message']);
        $this->assertSame(['page' => 2], $component->getJsonData()['meta']);
    }

    public function testFluentGetters(): void
    {
        $component = $this->makeComponent();

        $this->assertSame($component, $component->setSuccess(true));
        $this->assertSame($component, $component->withView(false));
        $this->assertSame([], $component->getJsonData());
    }
}
