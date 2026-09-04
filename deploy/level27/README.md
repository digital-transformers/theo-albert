# Level27 background processing

The Level27 account runs a user-owned Supervisor instance because the shared
system Supervisor cannot be controlled by `vd31987`.

The Supervisor configuration keeps consumers alive for all transports used by
this application:

- Pimcore core, maintenance, scheduled-task, and asset-update queues
- image optimization
- simple backend search indexing
- Data Importer processing
- Generic Execution Engine jobs

The installed crontab:

- ensures the user Supervisor is running every minute and after reboot
- dispatches Pimcore maintenance work every five minutes
- checks Data Importer cron definitions every minute

Useful commands:

```bash
/usr/bin/supervisorctl -c deploy/level27/supervisord.conf status
/usr/bin/supervisorctl -c deploy/level27/supervisord.conf restart all
php bin/console messenger:stats --env=prod --no-debug
```

The PrestaShop importer is a separate one-shot job. Each upload starts
`app:prestashop-export:sync` in the background and writes its PID, status,
report, and worker log under `var/prestashop-import/jobs/<job-id>/`.
