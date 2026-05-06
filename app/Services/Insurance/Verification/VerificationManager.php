<?php

namespace App\Services\Insurance\Verification;

use App\Contracts\InsuranceVerificationDriver;
use App\Models\InsuranceProvider;
use App\Services\Insurance\Verification\Drivers\ApiVerificationDriver;
use App\Services\Insurance\Verification\Drivers\CodeVerificationDriver;
use App\Services\Insurance\Verification\Drivers\ManualVerificationDriver;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Manager;
use InvalidArgumentException;

/**
 * Resolves the right verification driver for a given provider by reading
 * insurance_providers.verification_driver. Drivers are registered in
 * config/insurance_verification.php so adding a new style of verification
 * never requires touching this manager.
 */
class VerificationManager extends Manager
{
    public function __construct(Container $container)
    {
        parent::__construct($container);
    }

    public function getDefaultDriver(): string
    {
        return (string) config('insurance_verification.default', 'manual');
    }

    /**
     * Resolve the driver instance for a particular provider.
     */
    public function for(InsuranceProvider $provider): InsuranceVerificationDriver
    {
        $key = $provider->verification_driver ?: $this->getDefaultDriver();
        return $this->driver($key);
    }

    protected function createManualDriver(): InsuranceVerificationDriver
    {
        return $this->container->make(ManualVerificationDriver::class);
    }

    protected function createCodeDriver(): InsuranceVerificationDriver
    {
        return $this->container->make(CodeVerificationDriver::class);
    }

    protected function createApiDriver(): InsuranceVerificationDriver
    {
        return $this->container->make(ApiVerificationDriver::class);
    }

    /**
     * Allow custom drivers registered in config to resolve through the container.
     */
    protected function createDriver($driver)
    {
        $custom = config("insurance_verification.drivers.{$driver}");
        if ($custom && class_exists($custom)) {
            $instance = $this->container->make($custom);
            if (! $instance instanceof InsuranceVerificationDriver) {
                throw new InvalidArgumentException("Driver [{$driver}] must implement InsuranceVerificationDriver.");
            }
            return $instance;
        }
        return parent::createDriver($driver);
    }
}
