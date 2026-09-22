<?php

declare(strict_types=1);

namespace App\Tests\Unit\Mailer;

use App\Mailer\CloudflareTransport;
use App\Mailer\CloudflareTransportFactory;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\Exception\IncompleteDsnException;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

final class CloudflareTransportTest extends TestCase
{
    private const ACCOUNT = '0123456789abcdef0123456789abcdef';

    public function testPreservesMimeAttachmentsAndEnvelopeWithoutLeakingBcc(): void
    {
        $client = new MockHttpClient(function ($method, $url, $options): MockResponse {
            self::assertSame('POST', $method);
            self::assertSame('https://api.cloudflare.com/client/v4/accounts/'.self::ACCOUNT.'/email/sending/send_raw', $url);
            $body = json_decode($options['body'], true, 512, JSON_THROW_ON_ERROR);
            self::assertSame('theo@digital-transformers.it', $body['from']);
            self::assertSame(['to@example.com', 'cc@example.com', 'private@example.com'], $body['recipients']);
            self::assertStringContainsString('Content-Type: multipart/mixed', $body['mime_message']);
            self::assertStringContainsString('report.csv', $body['mime_message']);
            self::assertStringContainsString('Reply-To: support@example.com', $body['mime_message']);
            self::assertStringNotContainsString('private@example.com', $body['mime_message']);
            self::assertStringNotContainsString('Bcc:', $body['mime_message']);
            self::assertSame(0, $options['max_redirects']);

            return $this->response(['queued' => $body['recipients'], 'message_id' => 'cf-message-id']);
        });
        $transport = new CloudflareTransport($client, self::ACCOUNT, 'secret-token');
        $email = $this->email()->cc('cc@example.com')->bcc('private@example.com')
            ->replyTo('support@example.com')->html('<p>Report</p>')->attach('id,name', 'report.csv', 'text/csv');

        self::assertSame('cf-message-id', $transport->send($email)->getMessageId());
        self::assertStringNotContainsString('secret-token', (string) $transport);
    }

    public function testUsesExplicitEnvelopeForRedirectedMail(): void
    {
        $client = new MockHttpClient(function ($method, $url, $options): MockResponse {
            $body = json_decode($options['body'], true);
            self::assertSame(['debug@example.com'], $body['recipients']);

            return $this->response(['delivered' => ['debug@example.com']]);
        });
        $transport = new CloudflareTransport($client, self::ACCOUNT, 'secret-token');
        $transport->send($this->email(), new Envelope(new Address('theo@digital-transformers.it'), [new Address('debug@example.com')]));
    }

    public function testApiFailureIsReportedWithoutResponseSecrets(): void
    {
        $client = new MockHttpClient(new MockResponse(json_encode([
            'success' => false,
            'errors' => [['code' => 10102, 'message' => 'secret-token']],
        ]), ['http_code' => 403]));
        $transport = new CloudflareTransport($client, self::ACCOUNT, 'secret-token');
        $this->expectException(TransportException::class);
        $this->expectExceptionMessage('HTTP 403; error codes: 10102');
        $transport->send($this->email());
    }

    public function testAllBouncedRecipientsAreNotReportedAsSent(): void
    {
        $transport = new CloudflareTransport(new MockHttpClient($this->response([
            'permanent_bounces' => ['to@example.com'],
        ])), self::ACCOUNT, 'secret-token');
        $this->expectException(TransportException::class);
        $this->expectExceptionMessage('did not accept any');
        $transport->send($this->email());
    }

    public function testPartialDeliveryLogsRejectionsWithoutRetryingAcceptedRecipients(): void
    {
        $logger = new class extends AbstractLogger {
            public array $entries = [];
            public function log($level, string|\Stringable $message, array $context = []): void
            {
                $this->entries[] = [$level, $context];
            }
        };
        $transport = new CloudflareTransport(new MockHttpClient($this->response([
            'queued' => ['to@example.com'],
            'suppressed_recipients' => ['blocked@example.com'],
        ])), self::ACCOUNT, 'secret-token', logger: $logger);
        self::assertNotNull($transport->send($this->email()->cc('blocked@example.com')));
        self::assertSame('warning', $logger->entries[0][0]);
        self::assertSame(['blocked@example.com'], $logger->entries[0][1]['rejected_recipients']);
    }

    public function testInvalidJsonIsATransportFailure(): void
    {
        $transport = new CloudflareTransport(new MockHttpClient(new MockResponse('not json')), self::ACCOUNT, 'secret-token');
        $this->expectException(TransportException::class);
        $transport->send($this->email());
    }

    public function testFactorySupportsCloudflareAndLeavesSmtpToSymfony(): void
    {
        $factory = new CloudflareTransportFactory(client: new MockHttpClient());
        $dsn = Dsn::fromString('cloudflare+api://secret-token@default?account_id='.self::ACCOUNT);
        self::assertTrue($factory->supports($dsn));
        self::assertFalse($factory->supports(Dsn::fromString('smtp://localhost')));
        self::assertInstanceOf(CloudflareTransport::class, $factory->create($dsn));
    }

    public function testFactoryRejectsMissingAccount(): void
    {
        $this->expectException(IncompleteDsnException::class);
        (new CloudflareTransportFactory(client: new MockHttpClient()))->create(Dsn::fromString('cloudflare+api://token@default'));
    }

    private function email(): Email
    {
        return (new Email())->from('theo@digital-transformers.it')->to('to@example.com')->subject('Report')->text('Report');
    }

    private function response(array $result): MockResponse
    {
        return new MockResponse(json_encode(['success' => true, 'result' => $result], JSON_THROW_ON_ERROR));
    }
}
