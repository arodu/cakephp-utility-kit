<?php
declare(strict_types=1);

namespace UtilityKit\Model\Behavior;

use ArrayObject;
use Cake\Database\Query\SelectQuery;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Behavior;
use RuntimeException;

/**
 * FieldScope behavior
 */
class FieldScopeBehavior extends Behavior
{
    /**
     * Default configuration.
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'fieldName' => null,
        'fieldValue' => null,
    ];

    /**
     * @param array $config
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        if (empty($this->getConfig('fieldName'))) {
            throw new RuntimeException('FieldScopeBehavior: "fieldName" must be configured');
        }

        if (empty($this->getConfig('fieldValue'))) {
            throw new RuntimeException('FieldScopeBehavior: "fieldValue" must be configured');
        }
    }

    /**
     * @param \Cake\Event\EventInterface $event
     * @param \Cake\Database\Query\SelectQuery $query
     * @param \ArrayObject $options
     * @param bool $primary
     * @return void
     */
    public function beforeFind(EventInterface $event, SelectQuery $query, ArrayObject $options, bool $primary): void
    {
        if (!$primary || ($options['skipFieldScope'] ?? null) === true) {
            return;
        }

        $query->where([$this->table()->aliasField($this->getConfig('fieldName')) => $this->getConfig('fieldValue')]);
    }

    /**
     * @param \Cake\Event\EventInterface $event
     * @param \Cake\Datasource\EntityInterface $entity
     * @param \ArrayObject $options
     * @return void
     */
    public function beforeSave(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
        if ($entity->isNew() && !$entity->has($this->getConfig('fieldName'))) {
            $entity->set($this->getConfig('fieldName'), $this->getConfig('fieldValue'));
        }
    }
}
