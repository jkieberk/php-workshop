<?php

namespace PhpSchool\PhpWorkshopTest\ExerciseRunner;

use Colors\Color;
use PhpSchool\CliMenu\Terminal\TerminalInterface;
use PhpSchool\PhpWorkshop\Check\CodeParseCheck;
use PhpSchool\PhpWorkshop\Check\FileExistsCheck;
use PhpSchool\PhpWorkshop\Check\PhpLintCheck;
use PhpSchool\PhpWorkshop\Event\EventDispatcher;
use PhpSchool\PhpWorkshop\Exception\SolutionExecutionException;
use PhpSchool\PhpWorkshop\Exercise\ExerciseType;
use PhpSchool\PhpWorkshop\ExerciseRunner\CliRunner;
use PhpSchool\PhpWorkshop\Input\Input;
use PhpSchool\PhpWorkshop\Output\StdOutput;
use PhpSchool\PhpWorkshop\Result\Cli\CliResult;
use PhpSchool\PhpWorkshop\Result\Cli\GenericFailure;
use PhpSchool\PhpWorkshop\Result\Cli\RequestFailure;
use PhpSchool\PhpWorkshop\ResultAggregator;
use PhpSchool\PhpWorkshop\Solution\SingleFileSolution;
use PhpSchool\PhpWorkshopTest\Asset\CliExerciseInterface;
use PHPUnit_Framework_TestCase;

/**
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

    public function testRequiredChecks()
    {
        $requiredChecks = [
            FileExistsCheck::class,
            PhpLintCheck::class,
            CodeParseCheck::class,
        ];

        $this->assertEquals($requiredChecks, $this->runner->getRequiredChecks());
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
            ->will($this->returnValue([[]]));

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
            ->will($this->returnValue([[1, 2, 3]]));

        $this->assertInstanceOf(
            CliResult::class,
            $res = $this->runner->verify(new Input('app', ['program' => __DIR__ . '/../res/cli/user.php']))
        );

        $this->assertTrue($res->isSuccessful());
    }

    public function testSuccessWithSingleSetOfArgsForBC()
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
            CliResult::class,
            $res = $this->runner->verify(new Input('app', ['program' => __DIR__ . '/../res/cli/user.php']))
        );

        $this->assertTrue($res->isSuccessful());
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
            ->will($this->returnValue([[1, 2, 3]]));

        $failure = $this->runner->verify(new Input('app', ['program' => __DIR__ . '/../res/cli/user-error.php']));

        $failureMsg  = "/^PHP Code failed to execute. Error: \"PHP Parse error:  syntax error, ";
        $failureMsg .= "unexpected end of file, expecting ',' or ';'/";

        $this->assertInstanceOf(CLiResult::class, $failure);
        $this->assertCount(1, $failure);

        $result = iterator_to_array($failure)[0];
        $this->assertInstanceOf(GenericFailure::class, $result);
        $this->assertRegExp($failureMsg, $result->getReason());
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
            ->will($this->returnValue([[1, 2, 3]]));

        $failure = $this->runner->verify(new Input('app', ['program' => __DIR__ . '/../res/cli/user-wrong.php']));

        $this->assertInstanceOf(CLiResult::class, $failure);
        $this->assertCount(1, $failure);

        $result = iterator_to_array($failure)[0];
        $this->assertInstanceOf(RequestFailure::class, $result);

        $this->assertEquals('6', $result->getExpectedOutput());
        $this->assertEquals('10', $result->getActualOutput());
    }

    public function testRunPassesOutputAndReturnsSuccessIfScriptIsSuccessful()
    {
        $color = new Color;
        $color->setForceStyle(true);
        $output = new StdOutput($color, $this->createMock(TerminalInterface::class));

        $this->exercise
            ->expects($this->once())
            ->method('getArgs')
            ->will($this->returnValue([[1, 2, 3], [4, 5, 6]]));

        $exp  = "\n\e[1m\e[4mArguments\e[0m\e[0m\n";
        $exp .= "1, 2, 3\n";
        $exp .= "\n\e[1m\e[4mOutput\e[0m\e[0m\n";
        $exp .= "6\n";
        $exp .= "\e[33m\e[0m\n";
        $exp .= "\e[1m\e[4mArguments\e[0m\e[0m\n";
        $exp .= "4, 5, 6\n\n";
        $exp .= "\e[1m\e[4mOutput\e[0m\e[0m\n";
        $exp .= "15\n";
        $exp .= "\e[33m\e[0m";

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
            ->will($this->returnValue([[1, 2, 3]]));

        $this->expectOutputRegex('/PHP Parse error:  syntax error, unexpected end of file, expecting \',\' or \';\' /');

        $success = $this->runner->run(new Input('app', ['program' => __DIR__ . '/../res/cli/user-error.php']), $output);
        $this->assertFalse($success);
    }
}
