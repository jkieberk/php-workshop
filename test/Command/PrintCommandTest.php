<?php

namespace PhpSchool\PhpWorkshop\Command;

use Colors\Color;
use PHPUnit_Framework_TestCase;
use PhpSchool\PhpWorkshop\Exercise\ExerciseInterface;
use PhpSchool\PhpWorkshop\ExerciseRepository;
use PhpSchool\PhpWorkshop\MarkdownRenderer;
use PhpSchool\PhpWorkshop\Output\OutputInterface;
use PhpSchool\PhpWorkshop\UserState;

/**
 * Class PrintCommandTest
 * @package PhpSchool\PhpWorkshop\Command
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class PrintCommandTest extends PHPUnit_Framework_TestCase
{
    public function testExerciseIsPrintedIfAssigned()
    {
        $file = tempnam(sys_get_temp_dir(), 'pws');
        file_put_contents($file, '### Exercise 1');

        $exercise = $this->createMock(ExerciseInterface::class);
        $exercise
            ->expects($this->once())
            ->method('getProblem')
            ->will($this->returnValue($file));

        $exercise
            ->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('current-exercise'));

        $repo = new ExerciseRepository([$exercise]);

        $state = new UserState;
        $state->setCurrentExercise('current-exercise');

        $output = $this->createMock(OutputInterface::class);
        $renderer = $this->createMock(MarkdownRenderer::class);

        $renderer
            ->expects($this->once())
            ->method('render')
            ->with('### Exercise 1')
            ->will($this->returnValue('### Exercise 1'));

        $output
            ->expects($this->once())
            ->method('write')
            ->with('### Exercise 1');

        $command = new PrintCommand('phpschool', $repo, $state, $renderer, $output);
        $command->__invoke();

        unlink($file);
    }
}
