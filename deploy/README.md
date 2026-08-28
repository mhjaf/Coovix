# Coovix Apache deployment

Clean URLs require Apache's rewrite module and permission to read the project's
`.htaccess` file. `DirectoryIndex` does not expose `.html`; it only selects the
file Apache serves when a directory URL such as `/` is requested.

## Enable clean URLs on Ubuntu/Debian

1. Enable Apache rewriting:

   ```bash
   sudo a2enmod rewrite
   ```

2. Ensure the active Coovix virtual host contains this directory configuration
   (change `/var/www/coovix` if the deployed path is different):

   ```apache
   <Directory /var/www/coovix>
       Options -Indexes +FollowSymLinks
       AllowOverride All
       Require all granted
   </Directory>
   ```

   A complete HTTP example is available at `deploy/apache/coovix.conf.example`.
   If Certbot created a separate `*:443` virtual host, the directory block above
   still applies globally when it remains outside the virtual-host block.

3. Validate and reload Apache:

   ```bash
   sudo apache2ctl configtest
   sudo systemctl reload apache2
   ```

4. Deploy the project's `.htaccess` and updated extensionless links, then purge
   the Cloudflare cache if Cloudflare is proxying the domain.

## Verify

```bash
curl -I https://coovix.com/index.html
curl -I https://coovix.com/ar.html
curl -I https://coovix.com/ar
```

Expected results:

- `/index.html` returns `301` with `Location: /`
- `/ar.html` returns `301` with `Location: /ar`
- `/ar` returns `200`

If `.html` pages still return `200`, inspect the active virtual host with:

```bash
sudo apache2ctl -M | grep rewrite
sudo apache2ctl -S
```

The first command must show `rewrite_module`, and the second identifies the
actual configuration file serving `coovix.com`.
