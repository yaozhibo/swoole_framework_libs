<?php

namespace YSwoole\Core\Resets;

use YSwoole\Core\Sandbox;
use Illuminate\Contracts\Container\Container;
use YSwoole\Core\Resets\ResetContract;

class RebindViewContainer implements ResetContract
{
    /**
     * "handle" function for resetting app.
     *
     * @param \Illuminate\Contracts\Container\Container $app
     * @param \SwooleTW\Http\Server\Sandbox $sandbox
     */
    public function handle(Container $app, Sandbox $sandbox)
    {
        $view = $app->make('view');

        $closure = function () use ($app) {
            $this->container = $app;
            $this->shared['app'] = $app;
        };

        $resetView = $closure->bindTo($view, $view);
        $resetView();

        return $app;
    }
}
