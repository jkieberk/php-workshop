<?php

namespace PhpSchool\PhpWorkshop\ExerciseRunner;

use PhpSchool\PhpWorkshop\Exercise\CustomVerifyingExercise;
use PhpSchool\PhpWorkshop\Input\Input;
use PhpSchool\PhpWorkshop\Output\OutputInterface;
use PhpSchool\PhpWorkshop\Result\ResultInterface;

/**
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class CustomVerifyingRunner implements ExerciseRunnerInterface
{
    /**
     * @var CustomVerifyingExercise
     */
    private $exercise;

    /**
     * @param CustomVerifyingExercise $exercise
     */
    public function __construct(CustomVerifyingExercise $exercise)
    {
        $this->exercise = $exercise;
    }

    /**
     * Get the name of the exercise runner.
     *
     * @return string
     */
    public function getName()
    {
        return 'Custom Verifying Runner';
    }

    /**
     * Get an array of the class names of the required checks this runner needs.
     *
     * @return array
     */
    public function getRequiredChecks()
    {
        return [];
    }

    /**
     * Delegate to the exercise for verifying. Verifying could mean checking that a program was installed or that some
     * other arbitrary task was performed.
     *
     * @param Input $input The command line arguments passed to the command.
     * @return ResultInterface The result of the check.
     */
    public function verify(Input $input)
    {
        return $this->exercise->verify();
    }

    /**
     * Running a custom verifying exercise does nothing. There is no program required, therefore there is nothing
     * to run.
     *
     * @param Input $input The command line arguments passed to the command.
     * @param OutputInterface $output A wrapper around STDOUT.
     * @return bool If the solution was successfully executed, eg. exit code was 0.
     */
    public function run(Input $input, OutputInterface $output)
    {
        $message  = 'Nothing to run here. This exercise does not require a code solution, ';
        $message .= 'so there is nothing to execute.';
        $output->writeLine($message);
        return true;
    }
}
