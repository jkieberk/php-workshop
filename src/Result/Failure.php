<?php

namespace PhpSchool\PhpWorkshop\Result;

use PhpParser\Error as ParseErrorException;
use PhpSchool\PhpWorkshop\Check\CheckInterface;
use PhpSchool\PhpWorkshop\Exception\CodeExecutionException;

/**
 * Default implementation of `PhpSchool\PhpWorkshop\Result\FailureInterface`.
 *
 * @package PhpSchool\PhpWorkshop
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class Failure implements FailureInterface
{
    /**
     * @var string|null
     */
    private $reason;

    /**
     * @var string
     */
    private $name;

    /**
     * Create an instance from the name of the check that produces this result
     * and the reason for the failure.
     *
     * @param string $name The name of the check that produced this result.
     * @param string|null $reason The reason (if any) of the failure.
     */
    public function __construct($name, $reason = null)
    {
        $this->name     = $name;
        $this->reason   = $reason;
    }

    /**
     * Named constructor, for added code legibility.
     *
     * @param string $name The name of the check that produced this result.
     * @param string|null $reason The reason (if any) of the failure.
     * @return static The result.
     */
    public static function fromNameAndReason($name, $reason)
    {
        return new static($name, $reason);
    }
    
    /**
     * Static constructor to create from an instance of `PhpSchool\PhpWorkshop\Check\CheckInterface`.
     *
     * @param CheckInterface $check The check instance.
     * @param string $reason The reason (if any) of the failure.
     * @return static The result.
     */
    public static function fromCheckAndReason(CheckInterface $check, $reason)
    {
        return new static($check->getName(), $reason);
    }

    /**
     * Static constructor to create from a `PhpSchool\PhpWorkshop\Exception\CodeExecutionException` exception.
     *
     * @param string $name The name of the check that produced this result.
     * @param CodeExecutionException $e The exception.
     * @return static The result.
     */
    public static function fromNameAndCodeExecutionFailure($name, CodeExecutionException $e)
    {
        return new static($name, $e->getMessage());
    }

    /**
     * Static constructor to create from a `PhpParser\Error` exception. Many checks will need to parse the student's
     * solution, so this serves as a helper to create a consistent failure.
     *
     * @param CheckInterface $check The check that attempted to parse the solution.
     * @param ParseErrorException $e The parse exception.
     * @param string $file The absolute path to the solution.
     * @return static The result.
     */
    public static function fromCheckAndCodeParseFailure(CheckInterface $check, ParseErrorException $e, $file)
    {
        return new static(
            $check->getName(),
            sprintf('File: "%s" could not be parsed. Error: "%s"', $file, $e->getMessage())
        );
    }

    /**
     * Get the name of the check that this result was produced from.
     *
     * @return string
     */
    public function getCheckName()
    {
        return $this->name;
    }

    /**
     * Get the reason, or `null` if there is no reason.
     *
     * @return string|null
     */
    public function getReason()
    {
        return $this->reason;
    }
}
