# Server Configuration

This document outlines server-level configuration required for the application to function correctly.

## PHP Configuration (php.ini)

### File Upload Settings

The application supports file uploads up to 50MB. To ensure this works correctly, update your PHP configuration with the following settings:

```ini
; Maximum size of an uploaded file
upload_max_filesize = 64M

; Maximum size of POST data that PHP will accept
post_max_size = 64M

; Maximum memory a script may consume
memory_limit = 256M

; Maximum execution time for scripts (seconds)
max_execution_time = 120
```

### Finding Your php.ini Location

**Laravel Valet (macOS):**
```bash
php --ini
```

**Linux (Apache):**
```bash
php --ini
# Or find the loaded configuration file in PHP info
```

**Linux (Nginx with PHP-FPM):**
```bash
# PHP CLI
php --ini

# PHP-FPM pool configuration (may override php.ini)
/etc/php/8.3/fpm/pool.d/www.conf
```

### Applying Changes

After modifying php.ini:

**Laravel Valet:**
```bash
valet restart
```

**Apache:**
```bash
sudo systemctl restart apache2
```

**Nginx with PHP-FPM:**
```bash
sudo systemctl restart php8.3-fpm
sudo systemctl restart nginx
```

## Nginx Configuration (if applicable)

If using Nginx, you may need to increase the client body size limit:

```nginx
http {
    # Allow file uploads up to 64MB
    client_max_body_size 64M;
}
```

Or within your server block:

```nginx
server {
    client_max_body_size 64M;
    # ... other configuration
}
```

## Verification

To verify your configuration is correct, create a PHP info file or use artisan tinker:

```bash
php artisan tinker
>>> ini_get('upload_max_filesize')
=> "64M"
>>> ini_get('post_max_size')
=> "64M"
```

## Related Application Settings

The application enforces the following limits at the code level:

- **Maximum file size:** 50MB (validated in `FileUploadRequest`)
- **Blocked extensions:** `.exe`, `.bat`, `.cmd`, `.com`, `.msi`, `.dll`, `.scr`, `.vbs`, `.vbe`, `.js`, `.jse`, `.ws`, `.wsf`, `.ps1`, `.ps1xml`, `.psc1`, `.psd1`, `.psm1`, `.sh`, `.bash`, `.zsh`, `.csh`, `.ksh`, `.app`, `.dmg`, `.deb`, `.rpm`, `.jar`

The PHP configuration values should be set higher than the application limit (64M vs 50MB) to ensure PHP does not reject uploads before validation occurs.
