# Coovix deployment

Coovix uses clean URLs: `/ar` maps to `ar.html`, `/kr` maps to `kr.html`, and
the same rule applies to project, privacy, and terms pages. The web server must
provide that mapping; `DirectoryIndex` alone only handles directory URLs.

## Zoho contact-form email

The contact form sends from and delivers to `info@coovix.com` using Zoho Mail.
SMTP credentials stay outside the public website directory.

On the VPS, create the private configuration from the provided example:

```bash
sudo install -d -m 750 -o root -g www-data /etc/coovix
sudo cp /var/www/coovix/deploy/mail/zoho-mail.php.example /etc/coovix/mail.php
sudo chown root:www-data /etc/coovix/mail.php
sudo chmod 640 /etc/coovix/mail.php
sudo vi /etc/coovix/mail.php
```

Replace the placeholder with the Zoho password for `info@coovix.com`. If Zoho
two-factor authentication is enabled, use an app-specific password. Then test
the form from the website; no Nginx restart is required for this PHP file.

## Nginx (production)

The live Coovix server runs Nginx. In the active HTTPS `server` block, add the
canonical redirects and replace the existing `location /` block with:

```nginx
if ($request_uri ~ ^(.*/)index\.html(?:\?.*)?$) {
    return 301 $scheme://$host$1$is_args$args;
}

if ($request_uri ~ ^(.+)\.html(?:\?.*)?$) {
    return 301 $scheme://$host$1$is_args$args;
}

location / {
    try_files $uri $uri/ $uri.html =404;
}
```

The redirects remove `.html` from browser-visible URLs. The `$uri.html`
fallback serves the matching static file while keeping the clean URL in the
address bar. A reusable example is available at `deploy/nginx/coovix.conf.example`.

Validate and reload Nginx:

```bash
sudo nginx -t
sudo systemctl reload nginx
```

Verify the Arabic route and its section anchor:

```bash
curl -I https://coovix.com/ar
curl -I https://coovix.com/ar.html
curl -I https://coovix.com/Projects/ar-projects-index
curl -I https://coovix.com/Projects/ar-projects-index.html
```

The clean Arabic project URL must return `200`; its `.html` version must return
`301` to the clean URL. Browser fragments such as `#website-creation` are
handled after the page loads.

## Apache

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
