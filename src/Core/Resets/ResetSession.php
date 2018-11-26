<?php

namespace YSwoole\Core\Resets;

use YSwoole\Core\Sandbox;
use Illuminate\Contracts\Container\Container;
use YSwoole\Core\Resets\ResetContract;

class ResetSession implements ResetContract
{
    /**
     * "handle" function for resetting app.
     *
     * @param \Illuminate\Contracts\Container\Container $app
     * @param \SwooleTW\Http\Server\Sandbox $sandbox
     */
    public function handle(Container $app, Sandbox $sandbox)
    {
        if (isset($app['session'])) {
            $session = $app->make('session');
            $session->flush();
            $session->regenerate();
        }

        return $app;
    }
}
