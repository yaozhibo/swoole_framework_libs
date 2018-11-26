<?php
/**
 * Created by PhpStorm.
 * User: 姚志博
 * Date: 2018/11/21
 * Time: 14:34
 */
namespace YSwoole\Core;

use Illuminate\Http\Request;
use YSwoole\Traits\ResetApplication;
use Illuminate\Container\Container;
use YSwoole\Core\Coroutine\Context;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\Facades\Facade;
use YSwoole\Exceptions\SandboxException;
use Laravel\Lumen\Application as LumenApplication;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Contracts\Config\Repository as ConfigContract;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class Sandbox
{
    use ResetApplication;

    protected $app;

    protected $framework = 'laravel';

    public function __construct($app = null, $framework = null)
    {
        if (! $app instanceof Container) {
            return ;
        }
        $this->setBaseApp($app);
        $this->setFramework($framework ?? $this->framework);
        $this->initialize();
    }

    public function setFramework(string $framwork)
    {
        $this->framework = $framwork;
        return $this;
    }

    public function getFramework()
    {
        return $this->framework;
    }

    protected function setBaseApp(Container $app) {
        $this->app = $app;

        return $this;
    }

    public function getBaseApp()
    {
        return $this->app;
    }


    public function setRequest(Request $request)
    {
        Context::setData('_request', $request);

        return $this;
    }

    public function setSnapshot(Container $snapshot)
    {
        Context::setApp($snapshot);

        return $this;
    }

    public function initialize()
    {
        if (! $this->app instanceof Container) {
            throw new SandboxException('A base app has not been set.');
        }

        $this->setInitialConfig();
        $this->setInitialProviders();

        $this->setInitialResets();


        return $this;
    }

    public function getApplication()
    {
        $snapshot = clone $this->getBaseApp();
        $this->setSnapshot($snapshot);

        $snapshot = $this->getSnapshot();
        if ($snapshot instanceOf Container) {
            return $snapshot;
        }

        return $snapshot;
    }

    public function run(Request $request)
    {
        if (! $this->getSnapshot() instanceof Container) {
            throw new SandboxException('Sandbox is not enabled.');
        }

        $shouldUseOb = $this->config->get('yswoole_http.ob_output', true);
        if ($shouldUseOb) {
            return $this->prepareObResponse($request);
        }
        return $this->prepareResponse($request);
    }

    protected function prepareResponse(Request $request)
    {
        // handle request with laravel or lumen
        $response = $this->handleRequest($request);

        // process terminating logics
        $this->terminate($request, $response);

        return $response;
    }

    protected function prepareObResponse(Request $request)
    {
        ob_start();
        // handle request with laravel or lumen
        $response = $this->handleRequest($request);

        // prepare content for ob
        $content = '';
        $isFile = false;
        if ($isStream = $response instanceof StreamedResponse) {
            $response->sendContent();
        } elseif ($response instanceof SymfonyResponse) {
            $content = $response->getContent();
        } elseif (! $isFile = $response instanceof BinaryFileResponse) {
            $content = (string) $response;
        }

        // process terminating logics
        $this->terminate($request, $response);

        // append ob content to response
        if (! $isFile && ob_get_length() > 0) {
            if ($isStream) {
                $response->output = ob_get_contents();
            } else {
                $response->setContent(ob_get_contents() . $content);
            }
        }

        ob_end_clean();

        return $response;
    }

    protected function handleRequest(Request $request)
    {
        if ($this->isLaravel()) {

            return $this->getKernel()->handle($request);
        }

        return $this->getApplication()->dispatch($request);
    }

    protected function getKernel()
    {
        return $this->getApplication()->make(Kernel::class);
    }

    public function isLaravel()
    {
        return $this->framework === 'laravel';
    }

    public function terminate(Request $request, $response)
    {
        if ($this->isLaravel()) {
            $this->getKernel()->terminate($request, $response);
        } else {
            $app = $this->getApplication();
            $reflection = new \ReflectionObject($app);

            $middleware = $reflection->getProperty('middleware');
            $middleware->setAccessible(true);

            $callTerminableMiddleware = $reflection->getMethod('callTerminableMiddleware');
            $callTerminableMiddleware->setAccessible(true);

            if (count($middleware->getValue($app)) > 0) {
                $callTerminableMiddleware->invoke($app, $response);
            }
        }
    }

    public function enable()
    {
        if (! $this->config instanceof ConfigContract) {
            throw new SandboxException('Please initialize after setting base app.');
        }

        $this->setInstance($app = $this->getApplication());
        $this->resetApp($app);
    }

    public function disable()
    {
        Context::clear();
        $this->setInstance($this->getBaseApp());
    }

    public function setInstance(Container $app)
    {
        $app->instance('app', $app);
        $app->instance(Container::class, $app);

        if ($this->framework === 'lumen') {
            $app->instance(LumenApplication::class, $app);
        }

        Container::setInstance($app);
        Context::setApp($app);
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($app);
    }

    public function getSnapshot()
    {
        return Context::getApp();
    }

    protected function removeRequest()
    {
        return Context::removeData('_request');
    }

    public function getRequest()
    {
        return Context::getData('_request');
    }

}