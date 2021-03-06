<?php

namespace PhpSchool\PhpWorkshopTest\Listener;

use PhpSchool\PhpWorkshop\Event\Event;
use PhpSchool\PhpWorkshop\Exercise\ExerciseInterface;
use PhpSchool\PhpWorkshop\Input\Input;
use PhpSchool\PhpWorkshop\Listener\SelfCheckListener;
use PhpSchool\PhpWorkshop\Result\Success;
use PhpSchool\PhpWorkshop\ResultAggregator;
use PhpSchool\PhpWorkshopTest\Asset\SelfCheckExerciseInterface;
use PHPUnit_Framework_TestCase;

/**
 * Class SelfCheckListenerTest
 * @package PhpSchool\PhpWorkshopTest\Listener
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class SelfCheckListenerTest extends PHPUnit_Framework_TestCase
{
    public function testSelfCheck()
    {
        $exercise = $this->createMock(SelfCheckExerciseInterface::class);
        $input    = new Input('app', ['program' => 'some-file.php']);
        $event    = new Event('event', compact('exercise', 'input'));

        $success = new Success('Success');
        $exercise
            ->expects($this->once())
            ->method('check')
            ->with($input)
            ->will($this->returnValue($success));

        $results = new ResultAggregator;
        $listener = new SelfCheckListener($results);
        $listener->__invoke($event);

        $this->assertTrue($results->isSuccessful());
        $this->assertCount(1, $results);
    }

    public function testExerciseWithOutSelfCheck()
    {
        $exercise = $this->createMock(ExerciseInterface::class);
        $input    = new Input('app', ['program' => 'some-file.php']);
        $event    = new Event('event', compact('exercise', 'input'));

        $results = new ResultAggregator;
        $listener = new SelfCheckListener($results);
        $listener->__invoke($event);

        $this->assertTrue($results->isSuccessful());
        $this->assertCount(0, $results);
    }
}
