<?php

namespace MongoDB\Tests\Collection\CrudSpec;

/**
 * CRUD spec functional tests for distinct().
 *
 * @see https://github.com/mongodb/specifications/tree/master/source/crud/tests
 */
class DistinctFunctionalTest extends FunctionalTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->createFixtures(3);
    }

    public function testDistinctWithoutFilter()
    {
        $this->assertSame([11, 22, 33], $this->collection->distinct('x'));
    }

    public function testDistinctWithFilter()
    {
        $filter = ['_id' => ['$gt' => 1]];

        $this->assertSame([22, 33], $this->collection->distinct('x', $filter));
    }
}
