<?php
/**
 * Created by PhpStorm.
 * User: E
 * Date: 2018/5/14
 * Time: 15:37
 */

namespace Rock\Swoole\Client;

class WebSocket
{
    const VERSION = '0.1.4';
    const TOKEN_LENGTH = 16;

    const TYPE_ID_WELCOME = 0;
    const TYPE_ID_PREFIX = 1;
    const TYPE_ID_CALL = 2;
    const TYPE_ID_CALLRESULT = 3;
    const TYPE_ID_ERROR = 4;
    const TYPE_ID_SUBSCRIBE = 5;
    const TYPE_ID_UNSUBSCRIBE = 6;
    const TYPE_ID__PUBLISH = 7;
    const TYPE_ID_EVENT = 8;

    protected $key;
    protected $host;
    protected $port;
    protected $path;

    /**
     * @var TCP
     */
    protected $socket;
    protected $buffer = '';

    /**
     * @var bool
     */
    protected $connected = false;
    protected $handshake = false;
    protected $ssl = false;
    protected $ssl_key_file;
    protected $ssl_cert_file;

    protected $haveSwooleEncoder = false;

    protected $header;

    const GUID = '258EAFA5-E914-47DA-95CA-C5AB0DC85B11';
    const UserAgent = 'SwooleWebsocketClient';

    /**
     * @param string $host
     * @param int $port
     * @param string $path
     * @throws Rock\Swoole\Http\WebSocketException
     */

    function __construct($host, $port = 80, $path ='/')
    {
        if(empty($host))
        {
            throw new \Exception("require websocket server host.");
        }
        $this->haveSwooleEncoder = method_exists('swoole_websocket_server', 'pack');
        $this->host = $host;
        $this->port = $port;
        $this->path = $path;
        $this->key = $this->generateToken(self::TOKEN_LENGTH);
        $this->parser = new Rock\Swoole\Http\WebSocketParser();
    }

    /**
     * @param string $keyFile
     * @param string $certFile
     * @throws Rock\Swoole\Http\WebSocketException
     */
    function enableCrypto($keyFile = '', $certFile = '')
    {
        if(!extension_loaded('swoole')) {
            throw new \Exception("require swoole extension.");
        }
        $this->ssl = true;
        $this->ssl_key_file = $keyFile;
        $this->ssl_cert_file = $certFile;
    }

    /**
     * Disconnect on destruct.
     */
    function __destruct()
    {
        // TODO: Implement __destruct() method.
        if($this->connected)
        {
            $this->disconnect();
        }
    }

    /**
     * Connect client to server
     * @param $timeout
     * @return $this
     */
    public function connect($timeout = 0.5)
    {
        if(extension_loaded('swoole')) {
            $type = SWOOLE_TCP;
            if($this->ssl) {
                $type |=SWOOLE_SSL;
            }
            $this->socket = new \swoole_client($type);
        }
    }

    /**
     * Generate token
     *
     * @param int $length
     * @return string
     */
    private function generateToken($length)
    {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!"§$%&/()=[]{}';

        $useChars = array();
        // select some random chars:
        for ($i = 0; $i < $length; $i++) {
            $useChars[] = $characters[mt_rand(0, strlen($characters) - 1)];
        }
        // Add numbers
        array_push($useChars, rand(0, 9), rand(0, 9), rand(0, 9));
        shuffle($useChars);
        $randomString = trim(implode('', $useChars));
        $randomString = substr($randomString, 0, self::TOKEN_LENGTH);

        return base64_encode($randomString);
    }
}