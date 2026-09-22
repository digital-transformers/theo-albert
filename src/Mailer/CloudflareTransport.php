<?php

declare(strict_types=1);

namespace App\Mailer;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class CloudflareTransport extends AbstractTransport
{
    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly string $accountId,
        #[\SensitiveParameter] private readonly string $apiToken,
        ?EventDispatcherInterface $dispatcher = null,
        ?LoggerInterface $logger = null,
    ) {
        parent::__construct($dispatcher, $logger);
    }

    public function __toString(): string
    {
        return 'cloudflare+api://default?account_id='.$this->accountId;
    }

    protected function doSend(SentMessage $message): void
    {
        $envelope = $message->getEnvelope();
        try {
            // Raw MIME preserves Pimcore templates, attachments and inline images.
            // Envelope recipients include BCC; Symfony omits BCC from the MIME headers.
            $response = $this->client->request('POST', sprintf(
                'https://api.cloudflare.com/client/v4/accounts/%s/email/sending/send_raw',
                $this->accountId,
            ), [
                'auth_bearer' => $this->apiToken,
                'max_redirects' => 0,
                'timeout' => 30,
                'max_duration' => 60,
                'json' => [
                    'from' => $envelope->getSender()->getAddress(),
                    'recipients' => array_map(static fn ($address): string => $address->getAddress(), $envelope->getRecipients()),
                    'mime_message' => $message->toString(),
                ],
            ]);
            $status = $response->getStatusCode();
            $data = $response->toArray(false);
        } catch (\Throwable $exception) {
            // Do not include request options, message bodies or credentials in errors.
            throw new TransportException('Cloudflare email request failed; delivery status is unknown.', 0, $exception);
        }

        if ($status < 200 || $status >= 300 || ($data['success'] ?? false) !== true) {
            $codes = array_column($data['errors'] ?? [], 'code');
            throw new TransportException(sprintf('Cloudflare rejected the email (HTTP %d; error codes: %s).', $status, implode(', ', $codes)));
        }

        $result = $data['result'] ?? [];
        $accepted = array_merge($result['delivered'] ?? [], $result['queued'] ?? []);
        $rejected = array_merge($result['permanent_bounces'] ?? [], $result['suppressed_recipients'] ?? []);
        if ($accepted === []) {
            throw new TransportException('Cloudflare did not accept any email recipients. Check Email Sending logs for bounces or suppressions.');
        }

        if (!empty($result['message_id'])) {
            $message->setMessageId($result['message_id']);
        }
        if ($rejected !== []) {
            // Retrying a partially delivered message would duplicate accepted mail.
            $this->getLogger()->warning('Cloudflare accepted an email but rejected some recipients.', [
                'message_id' => $message->getMessageId(),
                'rejected_recipients' => $rejected,
            ]);
        }
    }
}
