<?php

namespace PhpSchool\PhpWorkshop\Result\Cgi;

use PhpSchool\PhpWorkshop\Check\CheckInterface;
use Psr\Http\Message\RequestInterface;

/**
 * Default implementation of `PhpSchool\PhpWorkshop\Result\Cgi\SuccessInterface`.
 *
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class Success implements SuccessInterface
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var string
     */
    private $name = 'CGI Program Runner';

    /**
     * @param RequestInterface $request The request for this success.
     */
    public function __construct(RequestInterface $request)
    {
        $this->request = $request;
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
     * Get the request for this success.
     *
     * @return RequestInterface
     */
    public function getRequest()
    {
        return $this->request;
    }
}
