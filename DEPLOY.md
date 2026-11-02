# Deploying CVO Portal API (Ubuntu + Apache 2 + PHP 8.3)

## 1. PHP and extensions

```bash
sudo apt update
sudo apt install -y apache2 libapache2-mod-php8.3 \
  php8.3 php8.3-cli php8.3-common php8.3-pgsql php8.3-mysql php8.3-sqlite3 \
  php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip php8.3-bcmath php8.3-intl \
  php8.3-dev php-pear unzip
```

### SQL Server driver (pdo_sqlsrv)

```bash
# Microsoft ODBC Driver 18 (see https://learn.microsoft.com/sql/connect/odbc/linux-mac/installing-the-microsoft-odbc-driver-for-sql-server)
curl -sSL https://packages.microsoft.com/keys/microsoft.asc | sudo gpg --dearmor -o /usr/share/keyrings/microsoft-prod.gpg
curl -sSL https://packages.microsoft.com/config/ubuntu/$(lsb_release -rs)/prod.list | sudo tee /etc/apt/sources.list.d/mssql-release.list
sudo apt update
sudo ACCEPT_EULA=Y apt install -y msodbcsql18 unixodbc-dev

sudo pecl install sqlsrv pdo_sqlsrv
echo "extension=sqlsrv.so"     | sudo tee /etc/php/8.3/mods-available/sqlsrv.ini
echo "extension=pdo_sqlsrv.so" | sudo tee /etc/php/8.3/mods-available/pdo_sqlsrv.ini
sudo phpenmod sqlsrv pdo_sqlsrv
```

Verify: `php -m | grep -Ei 'pdo_(pgsql|mysql|sqlsrv)'`

## 2. Application

```bash
cd /var/www
git clone <repo> cvo_portal_api   # or copy the project files
cd cvo_portal_api
composer install --no-dev --optimize-autoloader
cp .env.example .env              # then fill in APP_* and PG_/MYSQL_/MSSQL_ values
php artisan key:generate
touch database/database.sqlite && php artisan migrate --force   # local app tables only

sudo chown -R www-data:www-data storage bootstrap/cache database
php artisan config:cache
php artisan route:cache
```

Set `APP_ENV=production` and `APP_DEBUG=false` in `.env`.

## 3. Apache virtual host

`/etc/apache2/sites-available/cvo_portal_api.conf`:

```apache
<VirtualHost *:80>
    ServerName api.example.com
    DocumentRoot /var/www/cvo_portal_api/public

    <Directory /var/www/cvo_portal_api/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/cvo_portal_api_error.log
    CustomLog ${APACHE_LOG_DIR}/cvo_portal_api_access.log combined
</VirtualHost>
```

```bash
sudo a2enmod rewrite
sudo a2ensite cvo_portal_api
sudo a2dissite 000-default
sudo systemctl reload apache2
```

## 4. Endpoints

| Method | URL | Params |
|---|---|---|
| GET | `/api/frames/territories` | `email` |
| GET | `/api/frames/search` | `territory_id`, `search`, `period` (days) |

Remember to re-run `php artisan config:cache route:cache` after changing `.env` or routes.
