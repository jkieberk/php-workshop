<?php

namespace PhpSchool\PhpWorkshop\ExerciseRunner;

use Colors\Color;
use InvalidArgumentException;
use PhpSchool\CliMenu\Terminal\TerminalInterface;
use PhpSchool\PhpWorkshop\Event\EventDispatcher;
use PhpSchool\PhpWorkshop\Exception\SolutionExecutionException;
use PhpSchool\PhpWorkshop\Exercise\ExerciseType;
use PhpSchool\PhpWorkshop\Input\Input;
use PhpSchool\PhpWorkshop\Output\StdOutput;
use PhpSchool\PhpWorkshop\Result\Failure;
use PhpSchool\PhpWorkshop\Result\StdOutFailure;
use PhpSchool\PhpWorkshop\Result\Success;
use PhpSchool\PhpWorkshop\ResultAggregator;
use PhpSchool\PhpWorkshop\Solution\SingleFileSolution;
use PhpSchool\PhpWorkshopTest\Asset\CliExerciseInterface;
use PHPUnit_Framework_TestCase;

/**
 * Class CliRunnerTest
 * @package PhpSchool\PhpWorkshop\ExerciseRunner
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class CliRunnerTest extends PHPUnit_Framework_TestCase
{
    /** @var  CliRunner */
    private $runner;

    /**
     * @var CliExerciseInterface
     */
    private $exercise;

    public function setUp()
    {
        $this->exercise = $this->createMock(CliExerciseInterface::class);
        $this->runner = new CliRunner($this->exercise, new EventDispatcher(new ResultAggregator));

        $this->exercise
            ->expects($this->any())
            ->method('getType')
            ->will($this->returnValue(ExerciseType::CLI()));

        $this->assertEquals('CLI Program Runner', $this->runner->getName());
    }

    public function testVerifyThrowsExceptionIfSolutionFailsExecution()
    {
        $solution = SingleFileSolution::fromFile(realpath(__DIR__ . '/../res/cli/solution-error.php'));
        $this->exercise
            ->expects($this->once())
            ->method('getSolution')
            ->will($this->returnValue($solution));

        $this->exercise
            ->expects($this->once())
            ->method('getArgs')
            ->will($this->returnValue([]));

        $regex  = "/^PHP Code failed to execute\\. Error: \"PHP Parse error:  syntax error, unexpected end of file";
        $regex .= ", expecting ',' or ';'/";
        $this->expectException(SolutionExecutionException::class);
        $this->expectExceptionMessageRegExp($regex);
        $this->runner->verify(new Input('app', ['program' => '']));
    }

    public function testVerifyReturnsSuccessIfSolutionOutputMatchesUserOutput()
    {
        $solution = SingleFileSolution::fromFile(realpath(__DIR__ . '/../res/cli/solution.php'));
        $this->exercise
            ->expects($this->once())
            ->method('getSolution')
            ->will($this->returnValue($solution));

        $this->exercise
            ->expects($this->once())
            ->method('getArgs')
            ->will($this->returnValue([1, 2, 3]));

        $this->assertInstanceOf(
            Success::class,
            $this->runner->verify(new Input('app', ['program' => __DIR__ . '/../res/cli/user.php']))
        );
    }

    public function testVerifyReturnsFailureIfUserSolutionFailsToExecute()
    {
        $solution = SingleFileSolution::fromFile(realpath(__DIR__ . '/../res/cli/solution.php'));
        $this->exercise
            ->expects($this->once())
            ->method('getSolution')
            ->will($this->returnValue($solution));

        $this->exercise
            ->expects($this->once())
            ->method('getArgs')
            ->will($this->returnValue([1, 2, 3]));

        $failure = $this->runner->verify(new Input('app', ['program' => __DIR__ . '/../res/cli/user-error.php']));

        $failureMsg  = "/^PHP Code failed to execute. Error: \"PHP Parse error:  syntax error, ";
        $failureMsg .= "unexpected end of file, expecting ',' or ';'/";

        $this->assertInstanceOf(Failure::class, $failure);
        $this->assertRegExp($failureMsg, $failure->getReason());
    }

    public function testVerifyReturnsFailureIfSolutionOutputDoesNotMatchUserOutput()
    {
        $solution = SingleFileSolution::fromFile(realpath(__DIR__ . '/../res/cli/solution.php'));
        $this->exercise
            ->expects($this->once())
            ->method('getSolution')
            ->will($this->returnValue($solution));

        $this->exercise
            ->expects($this->once())
            ->method('getArgs')
            ->will($this->returnValue([1, 2, 3]));

        $failure = $this->runner->verify(new Input('app', ['program' => __DIR__ . '/../res/cli/user-wrong.php']));

        $this->assertInstanceOf(StdOutFailure::class, $failure);
        $this->assertEquals('6', $failure->getExpectedOutput());
        $this->assertEquals('10', $failure->getActualOutput());
    }

    public function testRunPassesOutputAndReturnsSuccessIfScriptIsSuccessful()
    {
        $output = new StdOutput(new Color, $this->createMock(TerminalInterface::class));

        $this->exercise
            ->expects($this->once())
            ->method('getArgs')
            ->will($this->returnValue([1, 2, 3]));

        $exp  = "\n\e[1m\e[4mArguments\e[0m\e[0m\n";
        $exp .= "1, 2, 3\n\n";
        $exp .= "\e[1m\e[4m";
        $exp .= "Output\e[0m\e[0m\n";
        $exp .= "6\n";

        $this->expectOutputString($exp);

        $success = $this->runner->run(new Input('app', ['program' => __DIR__ . '/../res/cli/user.php']), $output);
        $this->assertTrue($success);
    }

    public function testRunPassesOutputAndReturnsFailureIfScriptFails()
    {
        $output = new StdOutput(new Color, $this->createMock(TerminalInterface::class));

        $this->exercise
            ->expects($this->once())
            ->method('getArgs')
            ->will($this->returnValue([1, 2, 3]));

        $this->expectOutputRegex('/PHP Parse error:  syntax error, unexpected end of file, expecting \',\' or \';\' /');

        $success = $this->runner->run(new Input('app', ['program' => __DIR__ . '/../res/cli/user-error.php']), $output);
        $this->assertFalse($success);
    }
}
