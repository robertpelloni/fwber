# SSL Certificate Setup Guide

This directory should contain your SSL/TLS certificates for HTTPS configuration.

## Quick Setup with Let's Encrypt (Recommended)

### Option 1: Certbot (Automated)

```bash
# Install Certbot
sudo apt-get update
sudo apt-get install certbot

# Obtain certificate (standalone mode)
sudo certbot certonly --standalone -d yourdomain.com -d www.yourdomain.com

# Certificates will be in: /etc/letsencrypt/live/yourdomain.com/
# Copy to this directory:
sudo cp /etc/letsencrypt/live/yourdomain.com/fullchain.pem ./ssl/
sudo cp /etc/letsencrypt/live/yourdomain.com/privkey.pem ./ssl/

# Set proper permissions
chmod 644 ./ssl/fullchain.pem
chmod 600 ./ssl/privkey.pem
```

### Option 2: Docker with Certbot

```bash
# Run Certbot in Docker
docker run -it --rm \
  -v "${PWD}/ssl:/etc/letsencrypt" \
  -p 80:80 \
  certbot/certbot certonly --standalone \
  -d yourdomain.com \
  -d www.yourdomain.com \
  --agree-tos \
  -m your-email@example.com
```

### Option 3: Use Existing Certificates

If you have certificates from another provider (Cloudflare, DigiCert, etc.):

```bash
# Place your files here:
./ssl/fullchain.pem  # Full certificate chain
./ssl/privkey.pem    # Private key
```

## File Structure

```
ssl/
├── fullchain.pem    # Certificate + intermediate chain
├── privkey.pem      # Private key (keep secret!)
└── README.md        # This file
```

## Enabling HTTPS in nginx.conf

Once certificates are in place:

1. **Uncomment the HTTPS server block** in `nginx.conf`
2. **Uncomment the HTTP to HTTPS redirect** in the HTTP server block
3. **Update server_name** from `_` to your actual domain(s)
4. **Restart nginx**:
   ```bash
   docker compose -f docker-compose.prod.yml restart nginx
   ```

## Certificate Renewal

### Let's Encrypt (auto-renewal)

```bash
# Test renewal (dry run)
sudo certbot renew --dry-run

# Set up auto-renewal cron job (runs twice daily)
sudo crontab -e
# Add this line:
0 0,12 * * * certbot renew --quiet && docker compose -f /path/to/fwber/docker-compose.prod.yml restart nginx
```

### Docker-based renewal

```bash
# Create renewal script
cat > renew-ssl.sh << 'EOF'
#!/bin/bash
docker run --rm \
  -v "${PWD}/ssl:/etc/letsencrypt" \
  -p 80:80 \
  certbot/certbot renew --quiet
docker compose -f docker-compose.prod.yml restart nginx
EOF

chmod +x renew-ssl.sh

# Add to crontab
0 0 * * * /path/to/fwber/renew-ssl.sh
```

## Security Best Practices

1. **Protect private key**:
   ```bash
   chmod 600 ./ssl/privkey.pem
   ```

2. **Never commit certificates to Git**:
   - Already in `.gitignore` as `ssl/*.pem`

3. **Use strong TLS configuration**:
   - TLSv1.2 and TLSv1.3 only (configured in nginx.conf)
   - Strong cipher suites
   - HSTS enabled

4. **Monitor expiration**:
   ```bash
   openssl x509 -in ./ssl/fullchain.pem -noout -enddate
   ```

## Testing SSL Configuration

After enabling HTTPS:

```bash
# Check certificate validity
openssl s_client -connect yourdomain.com:443 -servername yourdomain.com

# Test SSL configuration (online)
# Visit: https://www.ssllabs.com/ssltest/
```

## Troubleshooting

### Certificate not found error
- Verify files exist: `ls -la ./ssl/`
- Check permissions: `chmod 644 fullchain.pem && chmod 600 privkey.pem`
- Ensure correct file names in nginx.conf

### Certificate/key mismatch
```bash
# Verify certificate and key match
openssl x509 -noout -modulus -in ./ssl/fullchain.pem | openssl md5
openssl rsa -noout -modulus -in ./ssl/privkey.pem | openssl md5
# MD5 hashes should match
```

### Port 443 connection refused
- Ensure nginx is listening on 443: `docker compose -f docker-compose.prod.yml ps`
- Check firewall: `sudo ufw status` or `sudo iptables -L`
- Verify port mapping in docker-compose.prod.yml

## Self-Signed Certificates (Development Only)

**NOT for production!** For local testing:

```bash
openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
  -keyout ./ssl/privkey.pem \
  -out ./ssl/fullchain.pem \
  -subj "/C=US/ST=State/L=City/O=Organization/CN=localhost"
```

Browsers will show security warnings for self-signed certificates.

## Resources

- Let's Encrypt: https://letsencrypt.org/
- Certbot: https://certbot.eff.org/
- SSL Labs Test: https://www.ssllabs.com/ssltest/
- Mozilla SSL Config Generator: https://ssl-config.mozilla.org/
