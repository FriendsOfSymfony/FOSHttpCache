<?php

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
    protected $logger;

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

    protected function log($level, \Exception $exception)
    {
        $context = array(
            'exception' => $exception
        );

        $this->logger->log($level, $exception->getMessage(), $context);
    }
}
