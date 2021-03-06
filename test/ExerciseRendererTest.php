<?php

namespace PhpSchool\PhpWorkshopTest;

use AydinHassan\CliMdRenderer\CliRendererFactory;
use Colors\Color;
use League\CommonMark\DocParser;
use League\CommonMark\Environment;
use PhpSchool\CliMenu\CliMenu;
use PhpSchool\CliMenu\MenuItem\MenuItemInterface;
use PhpSchool\CliMenu\Terminal\TerminalInterface;
use PHPUnit_Framework_TestCase;
use PhpSchool\PhpWorkshop\Exercise\ExerciseInterface;
use PhpSchool\PhpWorkshop\ExerciseRenderer;
use PhpSchool\PhpWorkshop\ExerciseRepository;
use PhpSchool\PhpWorkshop\MarkdownRenderer;
use PhpSchool\PhpWorkshop\Output\StdOutput;
use PhpSchool\PhpWorkshop\UserState;
use PhpSchool\PhpWorkshop\UserStateSerializer;

/**
 * Class ExerciseRendererTest
 * @package PhpSchool\PhpWorkshopTest
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class ExerciseRendererTest extends PHPUnit_Framework_TestCase
{
    public function testExerciseRendererSetsCurrentExerciseAndRendersExercise()
    {
        $menu = $this->createMock(CliMenu::class);

        $item = $this->createMock(MenuItemInterface::class);
        $item
            ->expects($this->any())
            ->method('getText')
            ->will($this->returnValue('Exercise 2'));
        $menu
            ->expects($this->once())
            ->method('getSelectedItem')
            ->will($this->returnValue($item));

        $menu
            ->expects($this->once())
            ->method('close');

        $exercise1 = $this->createMock(ExerciseInterface::class);
        $exercise2 = $this->createMock(ExerciseInterface::class);
        $exercises = [$exercise1, $exercise2];
        $exerciseRepository = $this->createMock(ExerciseRepository::class);
        $userState = $this->createMock(UserState::class);
        $userStateSerializer = $this->createMock(UserStateSerializer::class);

        $exerciseRepository
            ->expects($this->once())
            ->method('findByName')
            ->with('Exercise 2')
            ->will($this->returnValue($exercise2));

        $exerciseRepository
            ->expects($this->once())
            ->method('findAll')
            ->will($this->returnValue($exercises));

        $userState
            ->expects($this->once())
            ->method('setCurrentExercise')
            ->with('Exercise 2');

        $userStateSerializer
            ->expects($this->once())
            ->method('serialize')
            ->with($userState);
        $problemFile = sprintf('%s/%s/problem.md', sys_get_temp_dir(), $this->getName());
        $exercise2
            ->expects($this->once())
            ->method('getProblem')
            ->will($this->returnValue($problemFile));

        if (!is_dir(dirname($problemFile))) {
            mkdir(dirname($problemFile), 0775, true);
        }
        file_put_contents($problemFile, '### Exercise Content');

        $markdownRenderer = new MarkdownRenderer(
            new DocParser(Environment::createCommonMarkEnvironment()),
            (new CliRendererFactory)->__invoke()
        );

        $color = new Color;
        $color->setForceStyle(true);

        $exerciseRenderer = new ExerciseRenderer(
            'phpschool',
            $exerciseRepository,
            $userState,
            $userStateSerializer,
            $markdownRenderer,
            $color,
            new StdOutput($color, $this->createMock(TerminalInterface::class))
        );

        $this->expectOutputString(file_get_contents(__DIR__ . '/res/exercise-help-expected.txt'));
        $exerciseRenderer->__invoke($menu);

        unlink($problemFile);
    }
}
