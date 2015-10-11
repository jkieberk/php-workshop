<?php

namespace PhpWorkshop\PhpWorkshopTest\Command;

use PHPUnit_Framework_TestCase;
use PhpWorkshop\PhpWorkshop\Command\VerifyCommand;
use PhpWorkshop\PhpWorkshop\Exercise\ExerciseInterface;
use PhpWorkshop\PhpWorkshop\ExerciseRepository;
use PhpWorkshop\PhpWorkshop\ExerciseRunner;
use PhpWorkshop\PhpWorkshop\Output;
use PhpWorkshop\PhpWorkshop\UserState;
use PhpWorkshop\PhpWorkshop\UserStateSerializer;

/**
 * Class VerifyCommandTest
 * @package PhpWorkshop\PhpWorkshop\Command
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class VerifyCommandTest extends PHPUnit_Framework_TestCase
{

    public function testVerifyPrintsErrorIfProgramDoesNotExist()
    {
        $repo = new ExerciseRepository([]);
        $state = new UserState;
        $output = $this->getMockBuilder(Output::class)
            ->disableOriginalConstructor()
            ->getMock();

        $runner = new ExerciseRunner;

        $programFile = sprintf('%s/%s/program.php', sys_get_temp_dir(), $this->getName());
        $output
            ->expects($this->once())
            ->method('printError')
            ->with(sprintf('Could not verify. File: "%s" does not exist', $programFile));

        $serializer = $this->getMockBuilder(UserStateSerializer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $command = new VerifyCommand($repo, $runner, $state, $serializer, $output);
        $this->assertSame(1, $command->__invoke('appname', $programFile));
    }

    public function testVerifyPrintsErrorIfNoExerciseAssigned()
    {
        $file = tempnam(sys_get_temp_dir(), 'pws');
        touch($file);

        $repo = new ExerciseRepository([]);
        $state = new UserState;
        $output = $this->getMockBuilder(Output::class)
            ->disableOriginalConstructor()
            ->getMock();

        $runner = new ExerciseRunner;

        $output
            ->expects($this->once())
            ->method('printError')
            ->with('No active exercises. Select one from the menu');

        $serializer = $this->getMockBuilder(UserStateSerializer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $command = new VerifyCommand($repo, $runner, $state, $serializer, $output);
        $this->assertSame(1, $command->__invoke('appname', $file));

        unlink($file);
    }

    public function testVerifySuccessOutput()
    {
        $file = tempnam(sys_get_temp_dir(), 'pws');
        touch($file);

        $e = $this->getMock(ExerciseInterface::class);
        $e->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('exercise1'));
        $repo = new ExerciseRepository([$e]);
        $state = new UserState;
        $state->setCurrentExercise('exercise1');
        $output = $this->getMockBuilder(Output::class)
            ->disableOriginalConstructor()
            ->getMock();

        $serializer = $this->getMockBuilder(UserStateSerializer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $runner = new ExerciseRunner;
        $command = new VerifyCommand($repo, $runner, $state, $serializer, $output);
        $command->__invoke('appname', $file);
        unlink($file);
    }

    public function testVerifyFailureOutput()
    {

    }
}
