#!/bin/bash

# Export Database for Azure MySQL Deployment
# This script exports your current database for import to Azure MySQL

echo "🗄️  Exporting Voxxera POS Database for Azure..."

# Configuration
CONTAINER_NAME="ospos_mysql"
DB_NAME="ospos"
DB_USER="admin"
DB_PASS="pointofsale"
OUTPUT_FILE="voxxera_pos_azure_$(date +%Y%m%d_%H%M%S).sql"

# Export database
docker exec $CONTAINER_NAME mysqldump \
  -u$DB_USER \
  -p$DB_PASS \
  --databases $DB_NAME \
  --single-transaction \
  --routines \
  --triggers \
  --events \
  --add-drop-database \
  --compress \
  > $OUTPUT_FILE

if [ $? -eq 0 ]; then
    echo "✅ Database exported successfully!"
    echo "📁 File: $OUTPUT_FILE"
    echo "📊 Size: $(ls -lh $OUTPUT_FILE | awk '{print $5}')"
    echo ""
    echo "Next steps:"
    echo "1. Upload this file to your Azure MySQL server"
    echo "2. Import using: mysql -h your-db.mysql.database.azure.com -u dbadmin@your-db -p ospos < $OUTPUT_FILE"
else
    echo "❌ Database export failed!"
    exit 1
fi

