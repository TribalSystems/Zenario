<?php

namespace React\Socket;

use React\Promise\Deferred;
use React\Stream\Stream;
use React\EventLoop\LoopInterface;
use UnexpectedValueException;

/**
 * This class is considered internal and its API should not be relied upon
 * outside of Socket.
 *
 * @internal
 */
class StreamEncryption
{
    private $loop;
    private $method = STREAM_CRYPTO_METHOD_TLS_SERVER;

    private $errstr;
    private $errno;

    private $wrapSecure = false;

    public function __construct(LoopInterface $loop)
    {
        if (!function_exists('stream_socket_enable_crypto')) {
            throw new \BadMethodCallException('Encryption not supported on your platform (HHVM < 3.8?)');
        }

        $this->loop = $loop;

        // See https://bugs.php.net/bug.php?id=65137
        // https://bugs.php.net/bug.php?id=41631
        // https://github.com/reactphp/socket-client/issues/24
        // On versions affected by this bug we need to fread the stream until we
        //  get an empty string back because the buffer indicator could be wrong
        if (version_compare(PHP_VERSION, '5.6.8', '<')) {
            $this->wrapSecure = true;
        }

        if (defined('STREAM_CRYPTO_METHOD_TLSv1_0_SERVER')) {
            $this->method |= STREAM_CRYPTO_METHOD_TLSv1_0_SERVER;
        }
        if (defined('STREAM_CRYPTO_METHOD_TLSv1_1_SERVER')) {
            $this->method |= STREAM_CRYPTO_METHOD_TLSv1_1_SERVER;
        }
        if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_SERVER')) {
            $this->method |= STREAM_CRYPTO_METHOD_TLSv1_2_SERVER;
        }
    }

    public function enable(Stream $stream)
    {
        return $this->toggle($stream, true);
    }

    public function disable(Stream $stream)
    {
        return $this->toggle($stream, false);
    }

    public function toggle(Stream $stream, $toggle)
    {
        // pause actual stream instance to continue operation on raw stream socket
        $stream->pause();

        // TODO: add write() event to make sure we're not sending any excessive data

        $deferred = new Deferred();

        // get actual stream socket from stream instance
        $socket = $stream->stream;

        $that = $this;
        $toggleCrypto = function () use ($socket, $deferred, $toggle, $that) {
            $that->toggleCrypto($socket, $deferred, $toggle);
        };

        $this->loop->addReadStream($socket, $toggleCrypto);

        $wrap = $this->wrapSecure && $toggle;

        return $deferred->promise()->then(function () use ($stream, $wrap) {
            if ($wrap) {
                $stream->bufferSize = null;
            }

            $stream->resume();

            return $stream;
        }, function($error) use ($stream) {
            $stream->resume();
            throw $error;
        });
    }

    public function toggleCrypto($socket, Deferred $deferred, $toggle)
    {
        set_error_handler(array($this, 'handleError'));
        $result = stream_socket_enable_crypto($socket, $toggle, $this->method);
        restore_error_handler();

        if (true === $result) {
            $this->loop->removeStream($socket);

            $deferred->resolve();
        } else if (false === $result) {
            $this->loop->removeStream($socket);

            $deferred->reject(new UnexpectedValueException(
                sprintf("Unable to complete SSL/TLS handshake: %s", $this->errstr),
                $this->errno
            ));
        } else {
            // need more data, will retry
        }
    }

    public function handleError($errno, $errstr)
    {
        $this->errstr = str_replace(array("\r", "\n"), ' ', $errstr);
        $this->errno  = $errno;
    }
}
