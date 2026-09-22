<?php

declare(strict_types=1);

namespace App\Mailer;

use Symfony\Component\Mailer\Exception\IncompleteDsnException;
use Symfony\Component\Mailer\Exception\UnsupportedSchemeException;
use Symfony\Component\Mailer\Transport\AbstractTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\TransportInterface;

final class CloudflareTransportFactory extends AbstractTransportFactory
{
    public function create(Dsn $dsn): TransportInterface
    {
        if (!$this->supports($dsn)) {
            throw new UnsupportedSchemeException($dsn, 'cloudflare', $this->getSupportedSchemes());
        }

        $accountId = (string) $dsn->getOption('account_id', '');
        if (!preg_match('/^[a-f0-9]{32}$/i', $accountId)) {
            throw new IncompleteDsnException('Cloudflare requires a 32-character account_id DSN option.');
        }
        $token = $this->getUser($dsn);
        if ($token === '') {
            throw new IncompleteDsnException('Cloudflare requires an Email Sending API token as the DSN user.');
        }
        if ($dsn->getHost() !== 'default') {
            throw new IncompleteDsnException('The Cloudflare DSN host must be "default".');
        }
        if ($this->client === null) {
            throw new \LogicException('The Cloudflare transport requires Symfony HttpClient.');
        }

        return new CloudflareTransport($this->client, $accountId, $token, $this->dispatcher, $this->logger);
    }

    protected function getSupportedSchemes(): array
    {
        return ['cloudflare+api'];
    }
}
