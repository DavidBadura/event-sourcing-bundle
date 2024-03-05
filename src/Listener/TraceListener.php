<?php

declare(strict_types=1);

namespace Patchlevel\EventSourcingBundle\Listener;

use Patchlevel\EventSourcing\Repository\MessageDecorator\Trace;
use Patchlevel\EventSourcing\Repository\MessageDecorator\TraceStack;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

final class TraceListener
{
    public function __construct(
        private readonly TraceStack $traceStack,
    ) {
    }

    public function onRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $this->traceStack->add(self::traceByRequest($event->getRequest()));
    }

    public function onResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $this->traceStack->remove(self::traceByRequest($event->getRequest()));
    }

    public function onConsoleCommand(ConsoleCommandEvent $event): void
    {
        $command = $event->getCommand();

        if (!$command) {
            return;
        }

        $this->traceStack->add(self::traceByCommand($command));
    }

    public function onConsoleTerminate(ConsoleTerminateEvent $event): void
    {
        $command = $event->getCommand();

        if (!$command) {
            return;
        }

        $this->traceStack->remove(self::traceByCommand($command));
    }

    private function traceByCommand(Command $command): Trace
    {
        return new Trace(
            $command->getName(),
            'symfony/console',
        );
    }

    private function traceByRequest(Request $request): Trace
    {
        return new Trace(
            $request->attributes->get('_controller', 'unknown'),
            'symfony/controller',
        );
    }
}
