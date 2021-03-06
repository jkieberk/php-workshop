<?php

namespace PhpSchool\PhpWorkshopTest\Util;

use PhpSchool\PhpWorkshop\Utils\ArrayObject;
use PHPUnit_Framework_TestCase;

/**
 * Class ArrayObjectTest
 * @package PhpSchool\PhpWorkshopTest\Util
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class ArrayObjectTest extends PHPUnit_Framework_TestCase
{
    public function testMap()
    {
        $arrayObject = new ArrayObject([1, 2, 3]);
        $new = $arrayObject->map(function ($elem) {
            return $elem * 2;
        });

        $this->assertNotSame($arrayObject, $new);
        $this->assertEquals([2, 4, 6], $new->getArrayCopy());
    }

    public function testImplode()
    {
        $arrayObject = new ArrayObject([1, 2, 3]);
        $this->assertSame('1 2 3', $arrayObject->implode(' '));
    }

    public function testPrepend()
    {
        $arrayObject = new ArrayObject([1, 2, 3]);
        $new = $arrayObject->prepend(0);

        $this->assertNotSame($new, $arrayObject);
        $this->assertSame([0, 1, 2, 3], $new->getArrayCopy());
    }

    public function testAppend()
    {
        $arrayObject = new ArrayObject([1, 2, 3]);
        $new = $arrayObject->append(4);

        $this->assertNotSame($new, $arrayObject);
        $this->assertSame([1, 2, 3, 4], $new->getArrayCopy());
    }

    public function testGetIterator()
    {
        $arrayObject = new ArrayObject([1, 2, 3]);
        $this->assertSame([1, 2, 3], iterator_to_array($arrayObject));
    }

    public function testGetArrayCopy()
    {
        $arrayObject = new ArrayObject([1, 2, 3]);
        $this->assertSame([1, 2, 3], $arrayObject->getArrayCopy());
    }

    public function testFlatMap()
    {
        $arrayObject = new ArrayObject([
            ['name' => 'Aydin', 'pets' => ['rat', 'raccoon', 'binturong']],
            ['name' => 'Caroline', 'pets' => ['rabbit', 'squirrel', 'dog']],
        ]);
        $new = $arrayObject->flatMap(function (array $item) {
            return $item['pets'];
        });

        $this->assertSame(['rat', 'raccoon', 'binturong', 'rabbit', 'squirrel', 'dog'], $new->getArrayCopy());
    }

    public function testCollapse()
    {
        $arrayObject = new ArrayObject([[1, 2], [3, 4, 5], [6, 7, 8]]);
        $new = $arrayObject->collapse();

        $this->assertSame([1, 2, 3, 4, 5, 6, 7, 8], $new->getArrayCopy());

        //with non array elements (should be skipped)
        $arrayObject = new ArrayObject([[1, 2], [3, 4, 5], [6, 7, 8], 9, 10]);
        $new = $arrayObject->collapse();

        $this->assertSame([1, 2, 3, 4, 5, 6, 7, 8], $new->getArrayCopy());
    }

    public function testReduce()
    {
        $arrayObject = new ArrayObject([6, 3, 1]);
        $total = $arrayObject->reduce(function ($carry, $item) {
            return $carry + $item;
        }, 0);

        $this->assertEquals(10, $total);
    }

    public function testKeys()
    {
        $arrayObject = new ArrayObject(['one' => 1, 'two' => 2, 'three' => 3]);
        $new = $arrayObject->keys();

        $this->assertSame(['one', 'two', 'three'], $new->getArrayCopy());
    }

    public function testGetReturnsDefaultIfNotSet()
    {
        $arrayObject = new ArrayObject(['one' => 1, 'two' => 2, 'three' => 3]);
        $this->assertEquals(4, $arrayObject->get('four', 4));
    }

    public function testGet()
    {
        $arrayObject = new ArrayObject(['one' => 1, 'two' => 2, 'three' => 3]);
        $this->assertEquals(3, $arrayObject->get('three'));
    }

    public function testSet()
    {
        $arrayObject = new ArrayObject([1, 2, 3]);
        $new = $arrayObject->set(3, 4);

        $this->assertNotSame($new, $arrayObject);
        $this->assertSame([1, 2, 3, 4], $new->getArrayCopy());

        $arrayObject = new ArrayObject(['one' => 1, 'two' => 2, 'three' => 3]);
        $new = $arrayObject->set('three', 4);

        $this->assertNotSame($new, $arrayObject);
        $this->assertSame(['one' => 1, 'two' => 2, 'three' => 4], $new->getArrayCopy());
    }

    public function testIsEmpty()
    {
        $arrayObject = new ArrayObject([1, 2, 3]);
        self::assertFalse($arrayObject->isEmpty());

        $arrayObject = new ArrayObject;
        self::assertTrue($arrayObject->isEmpty());
    }
}
