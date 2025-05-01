<?php
namespace Nava\Pandago\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Get the private key for testing.
     *
     * @return string
     */
    protected function getPrivateKey(): string
    {
        $privateKey = getenv('PANDAGO_PRIVATE_KEY');

        if (empty($privateKey) && file_exists(__DIR__ . '/../.env')) {
            $lines = file(__DIR__ . '/../.env');
            foreach ($lines as $line) {
                if (strpos($line, 'PANDAGO_PRIVATE_KEY=') === 0) {
                    $privateKey = trim(substr($line, strlen('PANDAGO_PRIVATE_KEY=')));
                    break;
                }
            }
        }

        return $privateKey;
    }

    /**
     * Get the test config.
     *
     * @return array
     */
    protected function getConfig(): array
    {
        return [
            'client_id'   => getenv('PANDAGO_CLIENT_ID') ?: 'pandago:my:00000000-0000-0000-0000-000000000000',
            'key_id'      => getenv('PANDAGO_KEY_ID') ?: '00000000-0000-0000-0000-000000000001',
            'scope'       => getenv('PANDAGO_SCOPE') ?: 'pandago.api.my.*',
            'private_key' => $this->getPrivateKey() ?: '-----BEGIN RSA PRIVATE KEY-----
MIIEpAIBAAKCAQEAx5T8DyI6M7PXJ0DEsC+CnjMLUBzJA97sMH+FeEDqxPBDViq+
o9MEG8OQoq1R8WG3KOtyrw52nDw2LHSWZRqJd3HFP8AELiA1LEyNAz8xz0hTk3pi
m/pQG38+vYunC8qBHvaIYYcCTb9zeAlCaUnYqgZ4yxgzJGCPFCKnPrsANBW/F2kn
+QRWDM0Loh4Iw1Ljr5V7nGYNHWGGKBzNyhxTw2oNbgaxSNKSFIz5gMYu3eLWUcsn
u1oA0UG3nkRD2hAuYa9KRXJKNHcXZw42YYfkLDgVHiMz/tc7aIjvv9qAzK4yRj3u
fAUVzfWH1n/8rQRoX+QcR1mxN9WjCaxeFMT5RQIDAQABAoIBABRU2aCZ9yG34EVe
sW5UzEheKMO/JtW3yyWdI7Z1L1+e61Q3yRhX95EfUFEh0CaFr4aPOFQQQYeC8aB0
8LbtTWEGrn/NfZxLtMzg1Aq8qZ1woNnPib9YpeAVi1WbM1wgpFeMU68vSI8NvPaz
omVRBCN34QLU9/dR2DfT7YHpEA3CXqiJ6cANQcBsQGDLrYYMsrEKqGMn52Oy6XmA
9VykWZKk/u3FqjZwJvKVz4j0c0YSNpPmKQpvJqxYfw8MNb0BDn5K5a9ZiBVwoJdT
zR4g1yQ7xJ9noohTyY4ai7dtSPd4vRlvPKsQJBZYyR+F9ClJLzBwQFkDlVPxQbGp
H5Z3BAECgYEA7ffbDVaIcee0rDLdTiQFOeifdIAaOkOuWRcXrYwOwd3D3kbxsY5i
a44i61UGQ+vU36vX6s9Kw7AQAnHBDGjmO6j1pLbLQF9ajs9S6MiYmBWun4fVbVJc
FOnL6gqRd1CEMXZOduA/7RK2QoYXy4PI1UcLLpjqGPDSg/mxZDDgmEUCgYEA1tp1
YqXKQvoFgVrGwfPYxDlvNVcZVYXMQoHxKEA/WiDFJbEUXt5uVTYlFVwixPHDfP8k
NJA0xLVSJxEYuVPn+QRrKOXzXfbbGZE4cMY4SIOlHpTvW3CQnbWvkKZOcPfR0z0U
pPJGvzYZyd5DDgxYfaMXKvDGWw/zXUqM9F4d9EECgYEApcr73DiJ/Edg/fXNWIZW
3s9UgcQJNYJvyVGaDAPXAmGyjM0gVDIkZJe1WpIQw4/AzolqkWOPLkQ2RTiFoC87
K2cZiLpYrP+7qU+i1M6t+6Iq3YnaWkjgCisMCRrfCW0aQjZFU/dg7FRwTEO79YYp
OxsYZ+0oIVP7a9kDGHE0MjECgYEAsnEnlv0yIuUW+r5wu9X4BUU8mpi6tB+DYE8B
g5e2Rw7kuLXmF64cHbQbSF3Wt0oJ3fpCl0VRK5yj4Qw2hvxJy4GhN6K3PI7QwnbC
TpVgdKHP9iLEQ7rpWHqFFzX66CYfB/o3W+lRxb6oDKXYPqZyyjMp9RW2gkMPzx7n
zYEDuIECgYAy5R2HiMnDRwcGJLnTylzULmhAFUMPQLnLJHELDwJC0BV/2o9Z7u4c
1PYdWSkXYp1CfkE9Yf+POBxzm9FlLKWTGTUYm0A9VJa+sxQJh+vuzjYQPzKjGhJT
0W/lvbUy0AKJWpRJvllaP5DMzdIHvTvKqbZdYFwQmjUbWoM5QYCNFA==
-----END RSA PRIVATE KEY-----',
            'country'     => getenv('PANDAGO_COUNTRY') ?: 'my',
            'environment' => getenv('PANDAGO_ENVIRONMENT') ?: 'sandbox',
            'timeout'     => 30,
        ];
    }
}
