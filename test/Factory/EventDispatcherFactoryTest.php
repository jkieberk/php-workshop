<?php

namespace PhpSchool\PhpWorkshopTest\Factory;

use DI\ContainerBuilder;
use PhpSchool\PhpWorkshop\Event\Event;
use function PhpSchool\PhpWorkshop\Event\containerListener;
use Interop\Container\ContainerInterface;
use PhpSchool\PhpWorkshop\Event\EventDispatcher;
use PhpSchool\PhpWorkshop\Exception\InvalidArgumentException;
use PhpSchool\PhpWorkshop\Factory\EventDispatcherFactory;
use PhpSchool\PhpWorkshop\ResultAggregator;
use PHPUnit_Framework_TestCase;

/**
 * Class EventDispatcherFactoryTest
 * @package PhpSchool\PhpWorkshopTest\Event
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class EventDispatcherFactoryTest extends PHPUnit_Framework_TestCase
{

    public function testCreateWithNoConfig()
    {
        $c = $this->prophesize(ContainerInterface::class);
        $c->get(ResultAggregator::class)->willReturn(new ResultAggregator);
        $c->has('eventListeners')->willReturn(false);

        $dispatcher = (new EventDispatcherFactory)->__invoke($c->reveal());
        $this->assertInstanceOf(EventDispatcher::class, $dispatcher);
        $this->assertSame([], $this->readAttribute($dispatcher, 'listeners'));
    }

    public function testExceptionIsThrownIfEventListenerGroupsNotArray()
    {
        $c = $this->prophesize(ContainerInterface::class);
        $c->get(ResultAggregator::class)->willReturn(new ResultAggregator);
        $c->has('eventListeners')->willReturn(true);
        $c->get('eventListeners')->willReturn(new \stdClass);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected: "array" Received: "stdClass"');

        (new EventDispatcherFactory)->__invoke($c->reveal());
    }

    public function testExceptionIsThrownIfEventsNotArray()
    {
        $c = $this->prophesize(ContainerInterface::class);
        $c->get(ResultAggregator::class)->willReturn(new ResultAggregator);
        $c->has('eventListeners')->willReturn(true);
        $c->get('eventListeners')->willReturn(['my-group' => new \stdClass]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected: "array" Received: "stdClass"');

        (new EventDispatcherFactory)->__invoke($c->reveal());
    }

    public function testExceptionIsThrownIfEventListenersNotArray()
    {
        $eventConfig = [
            'my-group' => [
                'someEvent' => new \stdClass
            ]
        ];

        $c = $this->prophesize(ContainerInterface::class);
        $c->get(ResultAggregator::class)->willReturn(new ResultAggregator);
        $c->has('eventListeners')->willReturn(true);
        $c->get('eventListeners')->willReturn($eventConfig);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected: "array" Received: "stdClass"');

        (new EventDispatcherFactory)->__invoke($c->reveal());
    }

    public function testExceptionIsThrownIfListenerNotCallable()
    {
        $eventConfig = [
            'my-group' => [
                'someEvent' => [new \stdClass]
            ]
        ];

        $c = $this->prophesize(ContainerInterface::class);
        $c->get(ResultAggregator::class)->willReturn(new ResultAggregator);
        $c->has('eventListeners')->willReturn(true);
        $c->get('eventListeners')->willReturn($eventConfig);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Listener must be a callable or a container entry for a callable service.');

        (new EventDispatcherFactory)->__invoke($c->reveal());
    }

    public function testExceptionIsThrownIfEventsListenerContainerEntryNotExist()
    {
        $eventConfig = [
            'my-group' => [
                'someEvent' => [containerListener('nonExistingContainerEntry')]
            ]
        ];

        $c = $this->prophesize(ContainerInterface::class);
        $c->get(ResultAggregator::class)->willReturn(new ResultAggregator);
        $c->has('eventListeners')->willReturn(true);
        $c->get('eventListeners')->willReturn($eventConfig);

        $c->has('nonExistingContainerEntry')->willReturn(false);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Container has no entry named: "nonExistingContainerEntry"');

        (new EventDispatcherFactory)->__invoke($c->reveal());
    }

    public function testConfigEventListenersWithAnonymousFunction()
    {
        $callback = function () {
        };

        $eventConfig = [
            'my-group' => [
                'someEvent' => [$callback]
            ]
        ];

        $c = $this->prophesize(ContainerInterface::class);
        $c->get(ResultAggregator::class)->willReturn(new ResultAggregator);
        $c->has('eventListeners')->willReturn(true);
        $c->get('eventListeners')->willReturn($eventConfig);

        $dispatcher = (new EventDispatcherFactory)->__invoke($c->reveal());
        $this->assertInstanceOf(EventDispatcher::class, $dispatcher);
        $this->assertSame(
            [
                'someEvent' => [
                    $callback
                ]
            ],
            $this->readAttribute($dispatcher, 'listeners')
        );
    }

    public function testListenerFromContainerIsNotFetchedDuringAttaching()
    {
        $eventConfig = [
            'my-group' => [
                'someEvent' => [containerListener('containerEntry')]
            ]
        ];

        $c = $this->prophesize(ContainerInterface::class);

        $c->get(ResultAggregator::class)->willReturn(new ResultAggregator);
        $c->has('eventListeners')->willReturn(true);
        $c->get('eventListeners')->willReturn($eventConfig);
        $c->has('containerEntry')->willReturn(true);


        $dispatcher = (new EventDispatcherFactory)->__invoke($c->reveal());
        $this->assertInstanceOf(EventDispatcher::class, $dispatcher);
        $this->assertArrayHasKey('someEvent', $this->readAttribute($dispatcher, 'listeners'));

        $c->get('containerEntry')->shouldNotHaveBeenCalled();
    }

    public function testListenerFromContainerIsFetchedWhenEventDispatched()
    {
        $eventConfig = [
            'my-group' => [
                'someEvent' => [containerListener('containerEntry')]
            ]
        ];

        $c = $this->prophesize(ContainerInterface::class);

        $c->get(ResultAggregator::class)->willReturn(new ResultAggregator);
        $c->has('eventListeners')->willReturn(true);
        $c->get('eventListeners')->willReturn($eventConfig);
        $c->has('containerEntry')->willReturn(true);
        $c->get('containerEntry')->willReturn(function () {
        });

        $dispatcher = (new EventDispatcherFactory)->__invoke($c->reveal());
        $this->assertInstanceOf(EventDispatcher::class, $dispatcher);
        $this->assertArrayHasKey('someEvent', $this->readAttribute($dispatcher, 'listeners'));

        $dispatcher->dispatch(new Event('someEvent'));
    }

    public function testExceptionIsThrownIfMethodDoesNotExistOnContainerEntry()
    {
        $eventConfig = [
            'my-group' => [
                'someEvent' => [containerListener('containerEntry', 'notHere')]
            ]
        ];

        $c = $this->prophesize(ContainerInterface::class);

        $c->get(ResultAggregator::class)->willReturn(new ResultAggregator);
        $c->has('eventListeners')->willReturn(true);
        $c->get('eventListeners')->willReturn($eventConfig);
        $c->has('containerEntry')->willReturn(true);
        $c->get('containerEntry')->willReturn(new \stdClass);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Method "notHere" does not exist on "stdClass"');

        $dispatcher = (new EventDispatcherFactory)->__invoke($c->reveal());
        $this->assertInstanceOf(EventDispatcher::class, $dispatcher);

        $dispatcher->dispatch(new Event('someEvent'));
    }

    public function testDefaultListenersAreRegisteredFromConfig()
    {
        $containerBuilder = new ContainerBuilder;
        $containerBuilder->addDefinitions(__DIR__ . '/../../app/config.php');

        $container = $containerBuilder->build();

        $dispatcher = (new EventDispatcherFactory)->__invoke($container);

        $listeners = $this->readAttribute($dispatcher, 'listeners');

        $this->assertArrayHasKey('cli.verify.start', $listeners);
        $this->assertArrayHasKey('cli.run.start', $listeners);
        $this->assertArrayHasKey('cgi.verify.start', $listeners);
        $this->assertArrayHasKey('cgi.run.start', $listeners);
        $this->assertArrayHasKey('verify.post.check', $listeners);
    }
}
