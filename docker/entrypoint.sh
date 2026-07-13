#!/bin/sh
set -e

if [ "$(id -u)" = "0" ]; then
    chown -R appuser:appuser /var/www/storage /var/www/bootstrap/cache
fi

if [ ! -f vendor/autoload.php ]; then
    echo "vendor/ no encontrado, instalando (esto no debería pasar normalmente)..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi

# ── Descubrir paquetes de Laravel (requiere que artisan ya esté montado) ──
php artisan package:discover --ansi || true

if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "" ]; then
    echo "Generando APP_KEY..."
    php artisan key:generate --force
fi

if [ -n "$DB_HOST" ]; then
    echo "Esperando a MariaDB..."
    until php -r "new PDO('mysql:host=$DB_HOST;port=${DB_PORT:-3306}', '$DB_USERNAME', '$DB_PASSWORD');" 2>/dev/null; do
        sleep 1
    done
    echo "MariaDB disponible."
    php artisan migrate --force || true
fi

if [ -n "$AWS_ENDPOINT" ] && [ "$FILESYSTEM_DISK" = "s3" ]; then
    echo "Verificando bucket MinIO..."
    php -r "
    \$s3 = new Aws\S3\S3Client([
        'version' => 'latest',
        'region'  => '${AWS_DEFAULT_REGION:-us-east-1}',
        'endpoint' => '${AWS_ENDPOINT}',
        'use_path_style_endpoint' => true,
        'credentials' => ['key' => '${AWS_ACCESS_KEY_ID}', 'secret' => '${AWS_SECRET_ACCESS_KEY}'],
    ]);
    if (!\$s3->doesBucketExist('${AWS_BUCKET}')) {
        \$s3->createBucket(['Bucket' => '${AWS_BUCKET}']);
        echo 'Bucket ${AWS_BUCKET} creado.\n';
    } else {
        echo 'Bucket ${AWS_BUCKET} ya existe.\n';
    }
    " || echo "No se pudo verificar MinIO. ¿Está corriendo?"
fi

exec "$@"