<?php
namespace Swoole\Http;

use Swoole;
use Swoole\Coroutine\Context;

/**
 * Class Http_LAMP
 * @package Swoole
 */
class ExtServer implements Swoole\IFace\Http
{
    /**
     * @var \swoole_http_request
     */
    public $request;

    /**
     * @var \swoole_http_response
     */
    public $response;

    public $document_root;
    public $charset = 'utf-8';
    public $expire_time = 86400;
    const DATE_FORMAT_HTTP = 'D, d-M-Y H:i:s T';

    protected $mimes;
    protected $types;
    protected $config;

    static $gzip_extname = array('js' => true, 'css' => true, 'html' => true, 'txt' => true);

    function __construct($config)
    {
        $mimes = require LIBPATH . '/data/mimes.php';
        $this->mimes = $mimes;
        $this->types = array_flip($mimes);

        if (!empty($config['document_root']))
        {
            $this->document_root = trim($config['document_root']);
        }
        if (!empty($config['charset']))
        {
            $this->charset = trim($config['charset']);
        }
        $this->config = $config;
    }

    protected function getRequest()
    {
        if (Swoole::$enableCoroutine)
        {
            return Context::get('request');
        }
        else
        {
            return $this->request;
        }
    }

    protected function getResponse()
    {
        if (Swoole::$enableCoroutine)
        {
            return Context::get('response');
        }
        else
        {
            return $this->response;
        }
    }

    function header($k, $v)
    {
        $k = ucwords($k);
        $this->getResponse()->header($k, $v);
    }

    function status($code)
    {
        $this->getResponse()->status($code);
    }

    function response($content)
    {
        $this->finish($content);
    }

    function redirect($url, $mode = 302)
    {
        $this->getResponse()->status($mode);
        $this->getResponse()->header('Location', $url);
    }

    function finish($content = '')
    {
        throw new Swoole\Exception\Response($content);
    }

    function getRequestBody()
    {
        return $this->getRequest->rawContent();
    }

    function setcookie($name, $value = null, $expire = null, $path = '/', $domain = null, $secure = null, $httponly = null)
    {
        $this->getResponse()->cookie($name, $value, $expire, $path, $domain, $secure, $httponly);
    }

    /**
     * 将swoole扩展产生的请求对象数据赋值给框架的Request对象
     * @param Swoole\Request $request
     */
    function assign(Swoole\Request $request)
    {
        $_request = $this->getRequest();
        if (!empty($_request->get))
        {
            $request->get = $_request->get;
        }
        if (!empty($_request->post))
        {
            $request->post = $_request->post;
        }
        if (!empty($_request->files))
        {
            $request->files = $_request->files;
        }
        if (!empty($_request->cookie))
        {
            $request->cookie = $_request->cookie;
        }
        if (!empty($_request->server))
        {
            foreach ($_request->server as $key => $value)
            {
                $request->server[strtoupper($key)] = $value;
            }
            $request->remote_ip = $_request->server['remote_addr'];
        }
        $request->header = $_request->header;
        $request->setGlobal();
    }

    function doStatic(\swoole_http_request $req, \swoole_http_response $resp)
    {
        $file = $this->document_root . $req->server['request_uri'];
        $read_file = true;
        $fstat = stat($file);

        //过期控制信息
        if (isset($req->header['if-modified-since']))
        {
            $lastModifiedSince = strtotime($req->header['if-modified-since']);
            if ($lastModifiedSince and $fstat['mtime'] <= $lastModifiedSince)
            {
                //不需要读文件了
                $read_file = false;
                $resp->status(304);
            }
        }
        else
        {
            $resp->header('Cache-Control', "max-age={$this->expire_time}");
            $resp->header('Pragma', "max-age={$this->expire_time}");
            $resp->header('Last-Modified', date(self::DATE_FORMAT_HTTP, $fstat['mtime']));
            $resp->header('Expires',  "max-age={$this->expire_time}");
        }

        if ($read_file)
        {
            $extname = Swoole\Upload::getFileExt($file);
            if (empty($this->types[$extname]))
            {
                $mime_type = 'text/html; charset='.$this->charset;
            }
            else
            {
                $mime_type = $this->types[$extname];
            }
            $resp->header('Content-Type', $mime_type);
            $resp->sendfile($file);
        }
        else
        {
            $resp->end();
        }
        return true;
    }

    function onRequest(\swoole_http_request $req, \swoole_http_response $resp)
    {
        if ($this->document_root and is_file($this->document_root . $req->server['request_uri']))
        {
            $this->doStatic($req, $resp);
            return;
        }

        $this->request = $req;
        $this->response = $resp;

        //保存协程上下文
        if (Swoole::$enableCoroutine)
        {
            Context::put('request', $req);
            Context::put('response', $resp);
        }

        $php = Swoole::getInstance();
        $php->request = new Swoole\Request();
        $php->response = new Swoole\Response();
        $this->assign($php->request);
        try
        {
            try
            {
                if (Swoole::$enableOutputBuffer)
                {
                    ob_start();
                    /*---------------------处理MVC----------------------*/
                    $body = $php->runMVC();
                    $echo_output = ob_get_contents();
                    ob_end_clean();
                }
                else
                {
                    $body = $php->runMVC();
                }

                if (!isset($resp->header['Cache-Control']))
                {
                    $resp->header('Cache-Control', 'no-store, no-cache, must-revalidate');
                }
                if (!isset($resp->header['Pragma']))
                {
                    $resp->header('Pragma', 'no-cache');
                }
                $resp->end($echo_output.$body);
            }
            catch (Swoole\Exception\Response $e)
            {
                $resp->end($e->getMessage());
            }
        }
        catch (\Exception $e)
        {
            $resp->status(500);
            $resp->end($e->getMessage() . "<hr />" . nl2br($e->getTraceAsString()));
        }
        //保存协程上下文
        if (Swoole::$enableCoroutine)
        {
            Context::delete('request');
            Context::delete('response');
        }
    }

    function __clean()
    {
        $php = Swoole::getInstance();
        //模板初始化
        if (!empty($php->tpl))
        {
            $php->tpl->clear_all_assign();
        }
    }
}