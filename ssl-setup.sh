#!/bin/bash

# Install Certbot
apt install certbot python3-certbot-nginx -y

# Get SSL certificate (replace yourdomain.com with your actual domain)
certbot --nginx -d yourdomain.com -d www.yourdomain.com

# Auto-renewal setup (should be automatic, but verify)
systemctl status certbot.timer

# Test renewal
certbot renew --dry-run