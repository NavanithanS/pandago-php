<?php
namespace Nava\Pandago\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Get the test config.
     *
     * @return array
     */
    protected function getConfig(): array
    {
        $testConfig = require __DIR__ . '/config.php';
        return array_merge($testConfig, [
            'client_id'   => $testConfig['client_id'],
            'key_id'      => $testConfig['key_id'],
            'scope'       => $testConfig['scope'],
            'private_key' => $testConfig['private_key'],
            'country'     => $testConfig['country'],
            'environment' => $testConfig['environment'],
            'timeout'     => (int) $testConfig['timeout'],
        ]);
    }

    /**
     * Check if the required configuration values are set.
     *
     * @param array $requiredKeys
     * @return bool
     */
    protected function checkRequiredConfig(array $requiredKeys): bool
    {
        $config = $this->getConfig();
        foreach ($requiredKeys as $key) {
            if (empty($config[$key])) {
                return false;
            }
        }
        return true;
    }
}
