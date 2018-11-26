<?php

namespace YSwoole\Traits;

use Illuminate\Contracts\Container\Container;
use YSwoole\Exceptions\SandboxException;
use YSwoole\Core\Resets\ResetContract;

trait ResetApplication
{
    /**
     * @var \Illuminate\Config\Repository
     */
    protected $config;

    /**
     * @var array
     */
    protected $providers = [];

    /**
     * @var array
     */
    protected $resetters = [];

    /**
     * Set initial config.
     */
    protected function setInitialConfig()
    {
        $this->config = clone $this->getBaseApp()->make('config');
    }

    /**
     * Get config snapshot.
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Initialize customized service providers.
     */
    protected function setInitialProviders()
    {
        $app = $this->getBaseApp();
        $providers = $this->config->get('yswoole_http.providers', []);

        foreach ($providers as $provider) {
            if (class_exists($provider) && ! in_array($provider, $this->providers)) {
                $providerClass = new $provider($app);
                $this->providers[$provider] = $providerClass;
            }
        }
    }

    /**
     * Get Initialized providers.
     */
    public function getProviders()
    {
        return $this->providers;
    }

    /**
     * Initialize resetters.
     */
    public function setInitialResets()
    {
        $app = $this->getBaseApp();
        $resets = $this->config->get('yswoole_http.resets', []);
        foreach ($resets as $reset) {
            $resetClass = $app->make($reset);
            if (! $resetClass instanceof ResetContract) {
                throw new SandboxException("{$reset} must implement " . ResetContract::class);
            }
            $this->resetters[$reset] = $resetClass;
        }
    }

    /**
     * Get Initialized resetters.
     */
    public function getResetters()
    {
        return $this->resetters;
    }

    /**
     * Reset Laravel/Lumen Application.
     */
    public function resetApp(Container $app)
    {
        foreach ($this->resetters as $resetter) {
            $resetter->handle($app, $this);
        }
    }
}
