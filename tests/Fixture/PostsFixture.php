<?php
declare(strict_types=1);

namespace UtilityKit\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * PostsFixture
 */
class PostsFixture extends TestFixture
{
    /**
     * Table name
     *
     * @var string
     */
    public string $table = 'posts';

    /**
     * Records
     *
     * @var array<int, array<string, mixed>>
     */
    public array $records = [
        [
            'title' => 'Post A',
            'organization_id' => 1,
        ],
        [
            'title' => 'Post B',
            'organization_id' => 2,
        ],
        [
            'title' => 'Post C',
            'organization_id' => 1,
        ],
    ];
}
