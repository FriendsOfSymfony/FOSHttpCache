<?php

/*
 * This file is part of the FOSHttpCache package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCache\Test;

use PHPUnit\Event\Code\TestMethod;
use PHPUnit\Event\TestRunner\ExecutionStarted;
use PHPUnit\Event\TestRunner\ExecutionStartedSubscriber;
use PHPUnit\Event\TestSuite\TestSuite;
use PHPUnit\Metadata\Group;

class WebServerSubscriber implements ExecutionStartedSubscriber
{
    /**
     * PHP web server PID.
     */
    private int $pid;
    private bool $isTopLevel = true;

    public function notify(ExecutionStarted $event): void
    {
        if (!$this->isTopLevel) {
            return;
        }
        $this->isTopLevel = false;

        if (isset($this->pid)
            || !$this->hasTestsWithGroup($event->testSuite(), 'webserver')
        ) {
            return;
        }

        $this->pid = $pid = $this->startPhpWebServer();

        register_shutdown_function(static function () use ($pid): void {
            exec('kill '.$pid);
        });
    }

    private function hasTestsWithGroup(TestSuite $testSuite, string $group): bool
    {
        foreach ($testSuite->tests() as $test) {
            if (!$test->isTestMethod()) {
                continue;
            }

            assert($test instanceof TestMethod);

            foreach ($test->metadata()->isGroup() as $testGroup) {
                assert($testGroup instanceof Group);

                if ($testGroup->groupName() === $group) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Start PHP built-in web server.
     *
     * @return int PID
     */
    public function startPhpWebServer(): int
    {
        $command = sprintf(
            'php -S %s:%d -t %s >/dev/null 2>&1 & echo $!',
            '127.0.0.1',
            $this->getPort(),
            $this->getDocRoot()
        );
        exec($command, $output);

        $this->waitFor($this->getHostName(), (int) $this->getPort(), 2000);

        return $output[0];
    }

    public function getHostName(): string
    {
        if (!defined('WEB_SERVER_HOSTNAME')) {
            throw new \Exception('Set WEB_SERVER_HOSTNAME in your phpunit.xml');
        }

        return WEB_SERVER_HOSTNAME;
    }

    public function getPort(): string
    {
        if (!defined('WEB_SERVER_PORT')) {
            throw new \Exception('Set WEB_SERVER_PORT in your phpunit.xml');
        }

        return WEB_SERVER_PORT;
    }

    public function getDocRoot(): string
    {
        if (!defined('WEB_SERVER_DOCROOT')) {
            throw new \Exception('Set WEB_SERVER_DOCROOT in your phpunit.xml');
        }

        return WEB_SERVER_DOCROOT;
    }

    /**
     * Wait for caching proxy to be started up and reachable.
     *
     * @param int $timeout Timeout in milliseconds
     *
     * @throws \RuntimeException If proxy is not reachable within timeout
     */
    public function waitFor(string $ip, int $port, int $timeout): void
    {
        echo "Starting webserver at $ip:$port";

        for ($i = 0; $i < $timeout; ++$i) {
            echo '.';
            if (@fsockopen($ip, $port)) {
                echo " done.\n\n";

                return;
            }

            usleep(1000);
        }

        throw new \RuntimeException(
            sprintf(
                'Webserver cannot be reached at %s:%s',
                $ip,
                $port
            )
        );
    }
}
