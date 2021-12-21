<?php

/*
 * This file is part of the FOSHttpCache package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCache\EventListener;

use FOS\HttpCache\Event;
use FOS\HttpCache\Events;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Log when the caching proxy client can't  send requests to the caching server.
 */
class LogListener implements EventSubscriberInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            Events::PROXY_UNREACHABLE_ERROR => 'onProxyUnreachableError',
            Events::PROXY_RESPONSE_ERROR => 'onProxyResponseError',
        ];
    }

    public function onProxyUnreachableError(Event $event)
    {
        $this->log(LogLevel::CRITICAL, $event->getException());
    }

    public function onProxyResponseError(Event $event)
    {
        $this->log(LogLevel::CRITICAL, $event->getException());
    }

    private function log($level, \Exception $exception)
    {
        $context = [
            'exception' => $exception,
        ];

        $this->logger->log($level, $exception->getMessage(), $context);
    }
}
