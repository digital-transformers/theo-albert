# Email delivery

Both Theo and Jules use Symfony Mailer and the same application code. The website
hostname does not need to be on Cloudflare: only the sender domain needs to be
onboarded for Cloudflare Email Sending.

## Per-server configuration

Keep credentials in the deployment's untracked `.env.local` (or secret environment
variables). Do not put credentials in Git, console output, or shell history.

```dotenv
MAILER_FROM=theo@digital-transformers.it
MAILER_FROM_NAME=Theo
MAILER_DSN="cloudflare+api://URL_ENCODED_TOKEN@default?account_id=ACCOUNT_ID"
```

The Cloudflare token needs **Email Sending Write/Edit** for the owning account.
Use a dedicated API token, not Wrangler's short-lived OAuth token. The sender's
domain must be enabled, with the required DKIM and `cf-bounce` DNS records.
Cloudflare Email Sending is currently beta; check the account's sending quota
before using it for large reports or bulk notifications.

To switch an environment to Level27 SMTP, change only its DSN:

```dotenv
MAILER_DSN="smtp://URL_ENCODED_USER:URL_ENCODED_PASSWORD@SMTP_HOST:587?require_tls=true"
```

Use the hostname, port and TLS mode supplied by Level27. For implicit TLS, use
`smtps://...:465`. Level27 must authorize the chosen From domain and provide the
SPF/DKIM records needed for that service. Preserve existing SPF includes and MX
records when adding another sending provider. Enabling Cloudflare outbound mail
does not require moving inbound mail away from Google Workspace.

The application defaults to `native://default` until a server overrides the DSN.
Local `.env.local` already selects Mailpit; keep that configuration for local
development. Pimcore's configured sender and Symfony's default From/envelope
sender use `MAILER_FROM`.

## Deployment and verification

Deploy `.env`, `composer.json`, `composer.lock`, `config/services.yaml`,
`config/packages/mailer.yaml`, and `src/Mailer/` together. Install dependencies
from the lock file, clear/warm the production cache, and restart long-running
Messenger workers. If the deployment uses `.env.local.php`, regenerate it after
changing the environment variables.

On Level27, use the deployment's configured PHP binary:

```bash
~/.bin/php /path/to/composer install --no-dev --no-interaction --no-scripts --optimize-autoloader
~/.bin/php bin/console cache:clear --env=prod --no-debug
/usr/bin/supervisorctl -c deploy/level27/supervisord.conf restart all
~/.bin/php bin/console mailer:test davide@digital-transformers.it --from=theo@digital-transformers.it --env=prod --no-debug
```

For Docker, run Composer and console commands inside the existing production PHP
container and restart its PHP/worker services using `docker-compose.production.yaml`.

Cloudflare setup/inspection commands (Wrangler 4.136.1 or later):

```bash
npx --yes wrangler@4.136.1 email sending enable digital-transformers.it
npx --yes wrangler@4.136.1 email sending settings digital-transformers.it
npx --yes wrangler@4.136.1 email sending dns get digital-transformers.it
```

The adapter submits raw MIME over HTTPS, preserving HTML, attachments, inline
images and envelope recipients. BCC addresses are omitted from MIME headers.
Rejected API requests or messages with no accepted recipients raise a mailer
transport error. Partial delivery logs rejected recipients without retrying the
already accepted recipients. A timeout can leave delivery uncertain; check the
Cloudflare logs before manually retrying.

Cloudflare accepting or queueing a message does not prove inbox delivery. Check
the recipient mailbox and Cloudflare delivery logs for the end-to-end test.

Run the isolated transport tests without booting Pimcore or its database:

```bash
php vendor/bin/phpunit --bootstrap vendor/autoload.php tests/Unit/Mailer/CloudflareTransportTest.php
```

References: [Cloudflare Email Sending](https://developers.cloudflare.com/email-service/get-started/send-emails/)
and [raw MIME API](https://developers.cloudflare.com/api/resources/email_sending/methods/send_raw/).
