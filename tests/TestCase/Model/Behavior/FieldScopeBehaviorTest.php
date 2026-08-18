<?php
declare(strict_types=1);

namespace UtilityKit\Test\TestCase\Model\Behavior;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Locator\TableLocator;
use Cake\ORM\Table;
use Cake\TestSuite\TestCase;
use RuntimeException;
use UtilityKit\Model\Behavior\FieldScopeBehavior;

class FieldScopeBehaviorTest extends TestCase
{
    protected array $fixtures = [
        'plugin.UtilityKit.Posts',
    ];

    protected TableLocator $locator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->locator = new TableLocator();
        $this->locator->clear();
    }

    protected function tearDown(): void
    {
        unset($this->locator);
        parent::tearDown();
    }

    protected function makePostsTable(): Table
    {
        return $this->locator->get('Posts');
    }

    public function testInitializeWithoutFieldNameThrows(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('FieldScopeBehavior: "fieldName" must be configured');

        $table = $this->makePostsTable();
        $table->addBehavior('UtilityKit.FieldScope', ['fieldValue' => 1]);
    }

    public function testInitializeWithFieldNameDoesNotThrow(): void
    {
        $table = $this->makePostsTable();
        $table->addBehavior('UtilityKit.FieldScope', [
            'fieldName' => 'organization_id',
            'fieldValue' => 1,
        ]);

        $this->assertInstanceOf(
            FieldScopeBehavior::class,
            $table->behaviors()->get('FieldScope'),
        );
    }

    public function testBeforeFindFiltersByScope(): void
    {
        $table = $this->makePostsTable();
        $table->addBehavior('UtilityKit.FieldScope', [
            'fieldName' => 'organization_id',
            'fieldValue' => 1,
        ]);

        $ids = $table->find()->all()->extract('id')->toArray();

        $this->assertCount(2, $ids);
        foreach ($ids as $id) {
            $this->assertContains($id, [1, 3]);
        }
    }

    public function testBeforeFindWithSkipFieldScopeDoesNotFilter(): void
    {
        $table = $this->makePostsTable();
        $table->addBehavior('UtilityKit.FieldScope', [
            'fieldName' => 'organization_id',
            'fieldValue' => 1,
        ]);

        $count = $table->find('all', skipFieldScope: true)->count();

        $this->assertSame(3, $count);
    }

    public function testBeforeFindNonPrimaryDoesNotFilter(): void
    {
        $table = $this->makePostsTable();
        $table->addBehavior('UtilityKit.FieldScope', [
            'fieldName' => 'organization_id',
            'fieldValue' => 1,
        ]);

        $behavior = $table->behaviors()->get('FieldScope');
        $query = $table->find();
        $options = new ArrayObject([]);

        $behavior->beforeFind(new Event('Model.beforeFind', $table), $query, $options, false);

        $this->assertNull($query->clause('where'));
    }

    public function testBeforeFindPrimaryDoesFilter(): void
    {
        $table = $this->makePostsTable();
        $table->addBehavior('UtilityKit.FieldScope', [
            'fieldName' => 'organization_id',
            'fieldValue' => 1,
        ]);

        $behavior = $table->behaviors()->get('FieldScope');
        $query = $table->find();
        $options = new ArrayObject([]);

        $behavior->beforeFind(new Event('Model.beforeFind', $table), $query, $options, true);

        $this->assertNotNull($query->clause('where'));
    }

    public function testBeforeSaveSetsFieldOnNewEntityWithoutIt(): void
    {
        $table = $this->makePostsTable();
        $table->addBehavior('UtilityKit.FieldScope', [
            'fieldName' => 'organization_id',
            'fieldValue' => 1,
        ]);

        $entity = $table->newEntity(['title' => 'Post D']);
        $saved = $table->save($entity);

        $this->assertSame(1, $saved->get('organization_id'));
    }

    public function testBeforeSaveKeepsExplicitFieldOnNewEntity(): void
    {
        $table = $this->makePostsTable();
        $table->addBehavior('UtilityKit.FieldScope', [
            'fieldName' => 'organization_id',
            'fieldValue' => 1,
        ]);

        $entity = $table->newEntity(['title' => 'Post E', 'organization_id' => 5]);
        $saved = $table->save($entity);

        $this->assertSame(5, $saved->get('organization_id'));
    }

    public function testBeforeSaveDoesNotTouchExistingEntity(): void
    {
        $table = $this->makePostsTable();
        $table->addBehavior('UtilityKit.FieldScope', [
            'fieldName' => 'organization_id',
            'fieldValue' => 999,
        ]);

        $entity = $table->get(1, skipFieldScope: true);
        $entity->set('title', 'Updated');
        $saved = $table->save($entity);

        $this->assertSame(1, $saved->get('organization_id'));
    }
}
