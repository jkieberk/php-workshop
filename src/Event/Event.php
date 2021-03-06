<?php

namespace PhpSchool\PhpWorkshop\Event;

use PhpSchool\PhpWorkshop\Exception\InvalidArgumentException;

/**
 * A generic `PhpSchool\PhpWorkshop\Event\EventInterface` implementation.
 *
 * @package PhpSchool\PhpWorkshop\Event
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class Event implements EventInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var array
     */
    protected $parameters;

    /**
     * @param string $name The event name.
     * @param array $parameters The event parameters.
     */
    public function __construct($name, array $parameters = [])
    {
        $this->name = $name;
        $this->parameters = $parameters;
    }

    /**
     * Get the name of this event.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get an array of parameters that were triggered with this event.
     *
     * @return mixed[]
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * Get a parameter by it's name.
     *
     * @param string $name The name of the parameter.
     * @return mixed The value.
     * @throws InvalidArgumentException If the parameter by name does not exist.
     */
    public function getParameter($name)
    {
        if (!array_key_exists($name, $this->parameters)) {
            throw new InvalidArgumentException(sprintf('Parameter: "%s" does not exist', $name));
        }

        return $this->parameters[$name];
    }
}
