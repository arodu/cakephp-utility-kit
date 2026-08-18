<?php
declare(strict_types=1);

namespace UtilityKit\Test\TestCase\Trait;

use Cake\TestSuite\TestCase;
use UtilityKit\Trait\RegisterScopeDataTrait;

class RegisterScopeDataTraitTest extends TestCase
{
    use RegisterScopeDataTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->defaultScope = 'default';
        $this->currentScope = 'default';
        $this->scopeItems = [];
    }

    public function testSetAndGetScopeDataDefaultScope(): void
    {
        $result = $this->setScopeData(['foo' => 'bar']);
        $this->assertSame($this, $result);
        $this->assertSame([['foo' => 'bar']], $this->getScopeData());
    }

    public function testSetAndGetScopeDataExplicitScope(): void
    {
        $this->setScopeData(['a' => 1], 'custom');

        $this->assertSame([['a' => 1]], $this->getScopeData('custom'));
        $this->assertSame([], $this->getScopeData());
    }

    public function testSetScopeDataAppendsToExistingScope(): void
    {
        $this->setScopeData('first', 'custom');
        $this->setScopeData('second', 'custom');

        $this->assertSame(['first', 'second'], $this->getScopeData('custom'));
    }

    public function testGetScopeDataWithNullUsesCurrentScope(): void
    {
        $this->withScope('custom');
        $this->setScopeData('value');

        $this->assertSame(['value'], $this->getScopeData());
    }

    public function testGetScopeDataEmptyReturnsArray(): void
    {
        $this->assertSame([], $this->getScopeData('nonexistent'));
    }

    public function testDeleteScopeData(): void
    {
        $this->setScopeData('x', 'one');
        $this->setScopeData('y', 'two');

        $result = $this->deleteScopeData('one');

        $this->assertSame($this, $result);
        $this->assertSame([], $this->getScopeData('one'));
        $this->assertSame(['y'], $this->getScopeData('two'));
    }

    public function testDeleteScopeDataDefaultWhenNull(): void
    {
        $this->setScopeData('x');

        $this->deleteScopeData();

        $this->assertSame([], $this->getScopeData());
    }

    public function testDefaultScopeResetsCurrentScope(): void
    {
        $this->withScope('custom');

        $result = $this->defaultScope();

        $this->assertSame($this, $result);
        $this->assertSame('default', $this->getScopeName());
    }

    public function testWithScopeChangesCurrentScope(): void
    {
        $result = $this->withScope('custom');

        $this->assertSame($this, $result);
        $this->assertSame('custom', $this->getScopeName());
        $this->assertSame('default', $this->defaultScope);
    }

    public function testWithScopeOverwriteDeletesExistingData(): void
    {
        $this->setScopeData('old', 'custom');
        $this->withScope('custom', true);

        $this->assertSame([], $this->getScopeData('custom'));
    }

    public function testWithScopeWithoutOverwriteKeepsExistingData(): void
    {
        $this->setScopeData('old', 'custom');
        $this->withScope('custom');

        $this->assertSame(['old'], $this->getScopeData('custom'));
    }
}
