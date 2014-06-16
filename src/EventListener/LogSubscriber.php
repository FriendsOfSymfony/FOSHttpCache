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

class LogSubscriber implements EventSubscriberInterface
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
    public static function getSubscribedEvents()
    {
        return array(
            Events::PROXY_UNREACHABLE_ERROR => 'onProxyUnreachableError',
            Events::PROXY_RESPONSE_ERROR    => 'onProxyResponseError'
        );
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
        $context = array(
            'exception' => $exception
        );

        $this->logger->log($level, $exception->getMessage(), $context);
    }
}
