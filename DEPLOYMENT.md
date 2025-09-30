# Digital Ocean Deployment Guide

Bu rehber, Laravel eSIM uygulamasını Digital Ocean'da GitHub Actions ile deploy etmek için gerekli adımları açıklar.

## 1. Digital Ocean Server Kurulumu

### Droplet Oluşturma
1. Digital Ocean'da yeni droplet oluşturun
2. Ubuntu 22.04 LTS seçin
3. En az 2GB RAM önerilir
4. SSH key'inizi ekleyin

### Server Kurulumu
```bash
# Server'a SSH ile bağlanın
ssh root@your_server_ip

# Kurulum scriptini çalıştırın
wget https://raw.githubusercontent.com/yaysoft-ltd/esim-backend/main/server-setup.sh
chmod +x server-setup.sh
sudo ./server-setup.sh
```

## 2. Domain ve SSL Konfigürasyonu

### Domain Ayarları
1. Domain'inizi Digital Ocean IP'sine yönlendirin
2. Nginx konfigürasyonunu güncelleyin:
```bash
sudo nano /etc/nginx/sites-available/esim-app
# server_name esimetry.net www.esimetry.net; satırı zaten yapılandırılmış
```

### SSL Sertifikası
```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d esimetry.net -d www.esimetry.net
```

## 3. GitHub Secrets Konfigürasyonu

GitHub repository'nizin Settings > Secrets and variables > Actions bölümünde şu secrets'ları ekleyin:

### Required Secrets
- `DO_HOST`: Digital Ocean server IP adresi
- `DO_USERNAME`: SSH kullanıcı adı (genellikle 'root')
- `DO_SSH_KEY`: SSH private key (server'a erişim için)
- `DO_PORT`: SSH port (genellikle 22)

### SSH Key Oluşturma
```bash
# Local makinenizde
ssh-keygen -t ed25519 -C "github-actions@your-domain.com"
cat ~/.ssh/id_ed25519.pub

# Public key'i server'a ekleyin
ssh-copy-id -i ~/.ssh/id_ed25519.pub root@your_server_ip

# Private key'i GitHub secrets'a ekleyin
cat ~/.ssh/id_ed25519
```

## 4. Environment Konfigürasyonu

Server'da `.env` dosyasını oluşturun:
```bash
sudo cp /var/www/html/.env.template /var/www/html/app/.env
sudo nano /var/www/html/app/.env
```

### Önemli Environment Variables
```env
APP_NAME="eSIM Application"
APP_ENV=production
APP_KEY=base64:your_app_key_here
APP_DEBUG=false
APP_URL=https://esimetry.net

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=esim_app
DB_USERNAME=esim_user
DB_PASSWORD=yrtemiseten

# Cache & Sessions
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST=your_smtp_host
MAIL_PORT=587
MAIL_USERNAME=your_email
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls

# API Keys (populate with your actual keys)
AIRALO_CLIENT_ID=your_airalo_client_id
AIRALO_CLIENT_SECRET=your_airalo_client_secret
RAZORPAY_KEY=your_razorpay_key
RAZORPAY_SECRET=your_razorpay_secret
STRIPE_KEY=your_stripe_key
STRIPE_SECRET=your_stripe_secret
CASHFREE_APP_ID=your_cashfree_app_id
CASHFREE_SECRET_KEY=your_cashfree_secret_key
```

## 5. Database Kurulumu

```bash
# MySQL'e bağlanın
sudo mysql -u root -p

# Database ve user oluşturun
CREATE DATABASE esim_app;
CREATE USER 'esim_user'@'localhost' IDENTIFIED BY 'yrtemiseten';
GRANT ALL PRIVILEGES ON esim_app.* TO 'esim_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

## 6. İlk Deploy

### Repository Hazırlama
1. Kodu GitHub'a push edin
2. GitHub Actions workflow'u otomatik çalışacak
3. İlk deploy sonrası:

```bash
# Server'da
cd /var/www/html/app
sudo -u www-data php artisan key:generate
sudo -u www-data php artisan migrate --force
sudo -u www-data php artisan db:seed --force
sudo -u www-data php artisan storage:link
sudo -u www-data php artisan config:clear
sudo -u www-data php artisan cache:clear
```

## 7. Monitoring ve Logs

### Log Dosyaları
```bash
# Laravel logs
sudo tail -f /var/www/html/app/storage/logs/laravel.log

# Nginx logs
sudo tail -f /var/log/nginx/error.log
sudo tail -f /var/log/nginx/access.log

# PHP-FPM logs
sudo tail -f /var/log/php8.2-fpm.log

# Queue worker logs
sudo tail -f /var/log/laravel-worker.log
```

### Service Durumu
```bash
sudo systemctl status nginx
sudo systemctl status php8.2-fpm
sudo systemctl status mysql
sudo systemctl status redis-server
sudo systemctl status supervisor
```

## 8. Backup Stratejisi

### Database Backup
```bash
# Crontab ekleme
sudo crontab -e

# Her gün saat 02:00'da backup al
0 2 * * * /usr/bin/mysqldump -u esim_user -p'your_password' esim_app > /backup/esim_$(date +\%Y\%m\%d).sql
```

### File Backup
```bash
# Storage ve uploads backup
0 3 * * * tar -czf /backup/files_$(date +\%Y\%m\%d).tar.gz /var/www/html/app/storage/app/public
```

## 9. Performance Optimizasyonu

### Redis Konfigürasyonu
```bash
sudo nano /etc/redis/redis.conf
# maxmemory 512mb
# maxmemory-policy allkeys-lru
```

### PHP Optimizasyonu
```bash
sudo nano /etc/php/8.2/fpm/php.ini
# memory_limit = 512M
# max_execution_time = 300
# upload_max_filesize = 20M
# post_max_size = 20M
```

### Laravel Optimizasyonu
```bash
cd /var/www/html/app
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache
```

## 10. Güvenlik

### Firewall
```bash
sudo ufw status
sudo ufw allow OpenSSH
sudo ufw allow 'Nginx Full'
sudo ufw enable
```

### SSL/TLS Sertifika Yenileme
```bash
# Crontab ekleme
sudo crontab -e
# Her ay 1'inde sertifikayı yenile
0 0 1 * * /usr/bin/certbot renew --quiet && /bin/systemctl reload nginx
```

## Troubleshooting

### Common Issues

1. **Permission Issues**
```bash
sudo chown -R www-data:www-data /var/www/html/app
sudo chmod -R 755 /var/www/html/app
sudo chmod -R 775 /var/www/html/app/storage /var/www/html/app/bootstrap/cache
```

2. **Queue Not Working**
```bash
sudo supervisorctl restart laravel-worker:*
sudo supervisorctl status
```

3. **502 Bad Gateway**
```bash
sudo systemctl restart php8.2-fpm
sudo systemctl restart nginx
```

4. **Database Connection Error**
```bash
# Check MySQL status
sudo systemctl status mysql
# Check credentials in .env file (DB_PASSWORD=yrtemiseten)
```

### Deployment Failed

GitHub Actions deployment başarısız olursa:

1. Secrets'ları kontrol edin
2. Server erişimini test edin:
```bash
ssh -i ~/.ssh/id_ed25519 root@your_server_ip
```
3. Server disk alanını kontrol edin:
```bash
df -h
```

Bu rehberi takip ederek Laravel eSIM uygulamanızı Digital Ocean'da başarıyla deploy edebilirsiniz.