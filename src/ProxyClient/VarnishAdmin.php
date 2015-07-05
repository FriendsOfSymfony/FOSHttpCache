<?php

/*
 * This file is part of the FOSHttpCache package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCache\ProxyClient;

use FOS\HttpCache\Exception\ProxyUnreachableException;
use FOS\HttpCache\ProxyClient\Invalidation\BanInterface;
use FOS\HttpCache\ProxyClient\VarnishAdmin\Response;

/**
 * Varnish Admin CLI (also known as Management Port) client
 */
class VarnishAdmin extends AbstractVarnishClient implements BanInterface
{
    const CLIS_CLOSE = 50;
    const CLIS_SYNTAX = 100;
    const CLIS_UNKNOWN = 101;
    const CLIS_UNIMPL = 102;
    const CLIS_TOOFEW = 104;
    const CLIS_TOOMANY = 105;
    const CLIS_PARAM = 106;
    const CLIS_AUTH = 107;
    const CLIS_OK = 200;
    const CLIS_TRUNCATED = 201;
    const CLIS_CANT = 300;
    const CLIS_COMMS = 400;

    const TIMEOUT = 3;

    /**
     * @var string
     */
    private $host;

    /**
     * @var int
     */
    private $port;

    private $connection;

    /**
     * @var string[]
     */
    private $queuedBans = [];

    /**
     * @var string
     */
    private $secret;

    public function __construct($host, $port, $secret = null)
    {
        $this->host = $host;
        $this->port = $port;
        $this->secret = $secret;
    }

    /**
     * {@inheritdoc}
     */
    public function ban(array $headers)
    {
        $mappedHeaders = array_map(
            function ($name, $value) {
                return sprintf('obj.http.%s ~ "%s"', $name, $value);
            },
            array_keys($headers),
            $headers
        );

        $this->queuedBans[] = implode('&&', $mappedHeaders);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function banPath($path, $contentType = null, $hosts = null)
    {
        $ban = sprintf('obj.http.%s ~ "%s"', self::HTTP_HEADER_URL, $path);

        if ($contentType) {
            $ban .= sprintf(
                ' && obj.http.content-type ~ "%s"',
                $contentType
            );
        }

        if ($hosts) {
            $ban .= sprintf(
                ' && obj.http.%s ~ "%s"',
                self::HTTP_HEADER_HOST,
                $this->createHostsRegex($hosts)
            );
        }

        $this->queuedBans[] = $ban;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function flush()
    {
        foreach ($this->queuedBans as $ban) {
            $this->executeCommand('ban', $ban);
        }
    }

    private function getConnection()
    {
        if ($this->connection === null) {
            $connection = fsockopen($this->host, $this->port, $errno, $errstr, self::TIMEOUT);
            if ($connection === false) {
                throw new ProxyUnreachableException('Unreachable');
            }

            stream_set_timeout($connection, self::TIMEOUT);
            $response = $this->read($connection);

            switch ($response->getStatusCode()) {
                case self::CLIS_AUTH:
                    $this->authenticate(substr($response->getResponse(), 0, 32), $connection);
                    break;
            }

            $this->connection = $connection;
        }

        return $this->connection;
    }

    private function read($connection)
    {
        while (!feof($connection)) {
            $line = fgets($connection, 1024);
            if ($line === false) {
                throw new ProxyUnreachableException('bla');
            }
            if (strlen($line) === 13
                && preg_match('/^(?P<status>\d{3}) (?P<length>\d+)/', $line, $matches)
            ) {
                $response = '';
                while (!feof($connection) && strlen($response) < $matches['length']) {
                    $response .= fread($connection, $matches['length']);
                }

                return new Response($matches['status'], $response);
            }
        }
    }

    private function authenticate($challenge, $connection = null)
    {
        $data = sprintf("%1\$s\n%2\$s\n%1\$s\n", $challenge, $this->secret);
        $hash = hash('sha256', $data);

        $this->executeCommand('auth', $hash, $connection);
    }

    /**
     * Execute a command
     *
     * @param string    $command
     * @param string    $param
     * @param \resource $connection
     *
     * @return Response
     */
    private function executeCommand($command, $param = null, $connection = null)
    {
        $connection = $connection ?: $this->getConnection();
        $all = sprintf("%s %s\n", $command, $param);
        fwrite($connection, $all);

        $response = $this->read($connection);
        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException($response->getResponse());
        }

        return $response;
    }
}
