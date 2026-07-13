#!/bin/bash
# Backup diario de Intermedius
# Uso: ./backup.sh
# Debe ejecutarse en el servidor de producción (cron diario)

BACKUP_DIR="/var/backups/intermedius"
DB_NAME="intermedius_casa_cambio"
DB_USER="laravel_user"
DB_PASS="${DB_PASSWORD}"  # Tomar de variable de entorno
RETENTION_DAYS=7
DATE=$(date +%Y-%m-%d_%H-%M)

mkdir -p "$BACKUP_DIR"

# Backup de base de datos
mysqldump -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" > "$BACKUP_DIR/db_$DATE.sql"

# Backup de archivos (storage, documentos)
tar -czf "$BACKUP_DIR/files_$DATE.tar.gz" /www/wwwroot/api.intermediusg.com/backend/api/storage

# Eliminar backups antiguos (>7 días)
find "$BACKUP_DIR" -name "*.sql" -mtime +$RETENTION_DAYS -delete
find "$BACKUP_DIR" -name "*.tar.gz" -mtime +$RETENTION_DAYS -delete

echo "Backup completado: $DATE"
