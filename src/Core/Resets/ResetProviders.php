<?php

namespace YSwoole\Core\Resets;

use YSwoole\Core\Sandbox;
use Illuminate\Contracts\Container\Container;
use YSwoole\Core\Resets\ResetContract;

class ResetProviders implements ResetContract
{
    /**
     * "handle" function for resetting app.
     *
     * @param \Illuminate\Contracts\Container\Container $app
     * @param \SwooleTW\Http\Server\Sandbox $sandbox
     */
    public function handle(Container $app, Sandbox $sandbox)
    {
        foreach ($sandbox->getProviders() as $provider) {
            $this->rebindProviderContainer($app, $provider);
            if (method_exists($provider, 'register')) {
                $provider->register();
            }
            if (method_exists($provider, 'boot')) {
                $app->call([$provider, 'boot']);
            }
        }

        return $app;
    }

    /**
     * Rebind service provider's container.
     */
    protected function rebindProviderContainer($app, $provider)
    {
        $closure = function () use ($app) {
            $this->app = $app;
        };

        $resetProvider = $closure->bindTo($provider, $provider);
        $resetProvider();
    }
}
