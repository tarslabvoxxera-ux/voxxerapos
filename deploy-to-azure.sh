#!/bin/bash

# Voxxera POS - Azure Deployment Script
# This script helps deploy Voxxera POS to Azure

set -e

echo "🚀 Voxxera POS - Azure Deployment Wizard"
echo "=========================================="
echo ""

# Check if Azure CLI is installed
if ! command -v az &> /dev/null; then
    echo "❌ Azure CLI not found. Please install it first:"
    echo "   https://docs.microsoft.com/cli/azure/install-azure-cli"
    exit 1
fi

# Login to Azure
echo "📋 Step 1: Azure Login"
az login

# Get configuration
read -p "Enter Resource Group name (e.g., voxxera-rg): " RESOURCE_GROUP
read -p "Enter Azure region (e.g., eastus, centralindia): " LOCATION
read -p "Enter App name (e.g., voxxera-pos): " APP_NAME
read -p "Enter MySQL server name (e.g., voxxera-db): " DB_SERVER
read -sp "Enter MySQL admin password: " DB_PASSWORD
echo ""

echo ""
echo "📦 Creating Azure resources..."
echo ""

# Create resource group
echo "1️⃣  Creating resource group..."
az group create --name $RESOURCE_GROUP --location $LOCATION

# Create MySQL server
echo "2️⃣  Creating Azure MySQL server (this may take 5-10 minutes)..."
az mysql server create \
  --resource-group $RESOURCE_GROUP \
  --name $DB_SERVER \
  --location $LOCATION \
  --admin-user dbadmin \
  --admin-password "$DB_PASSWORD" \
  --sku-name B_Gen5_1 \
  --ssl-enforcement Enabled \
  --version 8.0

# Create database
echo "3️⃣  Creating database..."
az mysql db create \
  --resource-group $RESOURCE_GROUP \
  --server-name $DB_SERVER \
  --name ospos

# Configure firewall
echo "4️⃣  Configuring firewall..."
az mysql server firewall-rule create \
  --resource-group $RESOURCE_GROUP \
  --server $DB_SERVER \
  --name AllowAzureServices \
  --start-ip-address 0.0.0.0 \
  --end-ip-address 0.0.0.0

# Create App Service plan
echo "5️⃣  Creating App Service plan..."
az appservice plan create \
  --name ${APP_NAME}-plan \
  --resource-group $RESOURCE_GROUP \
  --sku B1 \
  --is-linux

# Create Web App
echo "6️⃣  Creating Web App..."
az webapp create \
  --resource-group $RESOURCE_GROUP \
  --plan ${APP_NAME}-plan \
  --name $APP_NAME \
  --runtime "PHP:8.2"

# Configure App Settings
echo "7️⃣  Configuring application settings..."
az webapp config appsettings set \
  --resource-group $RESOURCE_GROUP \
  --name $APP_NAME \
  --settings \
    CI_ENVIRONMENT=production \
    MYSQL_HOST_NAME=${DB_SERVER}.mysql.database.azure.com \
    MYSQL_DB_NAME=ospos \
    MYSQL_USERNAME=dbadmin@${DB_SERVER} \
    MYSQL_PASSWORD="$DB_PASSWORD" \
    PHP_TIMEZONE=Asia/Kolkata

# Enable HTTPS only
echo "8️⃣  Enabling HTTPS..."
az webapp update \
  --resource-group $RESOURCE_GROUP \
  --name $APP_NAME \
  --https-only true

# Get deployment URL
DEPLOYMENT_URL=$(az webapp deployment source config-local-git \
  --name $APP_NAME \
  --resource-group $RESOURCE_GROUP \
  --query url \
  --output tsv)

echo ""
echo "✅ Azure resources created successfully!"
echo ""
echo "📊 Deployment Summary:"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "App URL:        https://${APP_NAME}.azurewebsites.net"
echo "Database:       ${DB_SERVER}.mysql.database.azure.com"
echo "Resource Group: $RESOURCE_GROUP"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "🗄️  Next Steps:"
echo ""
echo "1. Import database to Azure MySQL:"
echo "   ./export-database-for-azure.sh"
echo "   Then upload the generated SQL file to Azure"
echo ""
echo "2. Deploy application code:"
echo "   git remote add azure $DEPLOYMENT_URL"
echo "   git add ."
echo "   git commit -m 'Deploy to Azure'"
echo "   git push azure master"
echo ""
echo "3. Configure your store details:"
echo "   Visit: https://${APP_NAME}.azurewebsites.net"
echo "   Login: admin / Faizankhanstores"
echo "   Go to: Office → Store Config"
echo ""
echo "4. Import your inventory:"
echo "   Items → CSV Import"
echo ""
echo "✨ Your Voxxera POS will be live on Azure!"

