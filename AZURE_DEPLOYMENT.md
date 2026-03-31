# Voxxera POS - Azure Deployment Guide

## Security Checklist ✅

### 1. Environment Configuration

**Create `.env` file in production with these settings:**

```env
CI_ENVIRONMENT = production

# Azure App Service URL
app.baseURL = 'https://your-app-name.azurewebsites.net/'
app.forceGlobalSecureRequests = true

# Azure MySQL Database
database.default.hostname = your-server.mysql.database.azure.com
database.default.database = ospos
database.default.username = admin@your-server
database.default.password = STRONG_PASSWORD_HERE
database.default.DBDriver = MySQLi
database.default.DBPrefix = ospos_
database.default.port = 3306

# Keep this encryption key secure
encryption.key = 'base64:70179b0415e72883462fa9766577e314838a21ef14027f521b091b1577b403e4'

# Production logging
logger.threshold = 4

# Timezone
app.timezone = 'Asia/Kolkata'
```

---

## Azure Resources Needed

### 1. **Azure App Service**
- **Plan**: B1 or higher (Basic tier minimum)
- **Runtime**: PHP 8.2
- **OS**: Linux

### 2. **Azure Database for MySQL**
- **Version**: MySQL 8.0 or MariaDB 10.x
- **Tier**: Basic or General Purpose
- **Storage**: 20GB minimum (scalable)
- **Backup**: Enable automated backups (7-35 days retention)

### 3. **Azure Storage** (Optional but recommended)
- For file uploads (receipts, logos, etc.)
- Blob storage container

---

## Step-by-Step Azure Deployment

### Phase 1: Prepare Database

1. **Create Azure Database for MySQL**
   ```bash
   az mysql server create \
     --resource-group your-rg \
     --name voxxera-pos-db \
     --location eastus \
     --admin-user dbadmin \
     --admin-password YOUR_STRONG_PASSWORD \
     --sku-name B_Gen5_1 \
     --version 8.0
   ```

2. **Configure Firewall**
   - Allow Azure services
   - Add your IP for initial setup

3. **Import Database**
   ```bash
   mysql -h voxxera-pos-db.mysql.database.azure.com \
     -u dbadmin@voxxera-pos-db \
     -p ospos < app/Database/database.sql
   ```

### Phase 2: Prepare Application

1. **Update Configuration**
   - Edit `.env` with Azure database credentials
   - Set `CI_ENVIRONMENT = production`
   - Update `app.baseURL` to your Azure domain

2. **Build Assets**
   ```bash
   npm install
   gulp default
   composer install --no-dev --optimize-autoloader
   ```

3. **Secure Files**
   - Remove development files
   - Set proper permissions
   - Clear logs

### Phase 3: Deploy to Azure App Service

**Option A: Deploy via Git**

1. **Initialize Git in your project** (if not already)
   ```bash
   git init
   git add .
   git commit -m "Initial Voxxera POS deployment"
   ```

2. **Create Azure App Service**
   ```bash
   az webapp create \
     --resource-group your-rg \
     --plan your-app-service-plan \
     --name voxxera-pos \
     --runtime "PHP:8.2"
   ```

3. **Configure Git Deployment**
   ```bash
   az webapp deployment source config-local-git \
     --name voxxera-pos \
     --resource-group your-rg
   ```

4. **Push to Azure**
   ```bash
   git remote add azure <deployment-url>
   git push azure master
   ```

**Option B: Deploy via Docker**

1. **Build Docker Image**
   ```bash
   docker build -t voxxerapos:latest .
   ```

2. **Push to Azure Container Registry**
   ```bash
   az acr create --resource-group your-rg --name voxxeraregistry --sku Basic
   az acr login --name voxxeraregistry
   docker tag voxxerapos:latest voxxeraregistry.azurecr.io/voxxerapos:latest
   docker push voxxeraregistry.azurecr.io/voxxerapos:latest
   ```

3. **Create Web App from Container**
   ```bash
   az webapp create \
     --resource-group your-rg \
     --plan your-plan \
     --name voxxera-pos \
     --deployment-container-image-name voxxeraregistry.azurecr.io/voxxerapos:latest
   ```

### Phase 4: Configure App Service

1. **Set Environment Variables** in Azure Portal:
   - Go to App Service → Configuration → Application Settings
   - Add all variables from `.env` file:
     ```
     CI_ENVIRONMENT = production
     database.default.hostname = your-db.mysql.database.azure.com
     database.default.database = ospos
     database.default.username = dbadmin@your-db
     database.default.password = YOUR_PASSWORD
     encryption.key = base64:70179b0415e72883...
     ```

2. **Configure PHP Settings**:
   - PHP version: 8.2
   - Enable extensions: mysqli, gd, intl, mbstring, zip

3. **Set Document Root**:
   - Set to `/public` folder

### Phase 5: Configure SSL/Security

1. **Enable HTTPS**:
   - Azure provides free SSL certificate
   - Go to App Service → TLS/SSL settings
   - Enable "HTTPS Only"

2. **Configure Custom Domain** (Optional):
   - Add your custom domain
   - Configure DNS
   - Enable SSL certificate

---

## Security Hardening Checklist

- [x] Production environment enabled
- [x] Debug mode disabled
- [x] Strong database password
- [x] Encryption key set
- [x] HTTPS enforced
- [x] Admin-only inventory access
- [x] Double verification for deletions
- [x] Customer mandatory for checkout
- [x] Session security enabled
- [x] CSRF protection enabled
- [x] SQL injection protection (built-in)
- [x] XSS protection (built-in)

---

## Post-Deployment Steps

### 1. **Verify Application**
- Visit `https://your-app-name.azurewebsites.net`
- Login with admin credentials
- Test all features

### 2. **Configure Store Details**
- Go to **Office → Store Config**
- Update:
  - Company name
  - Address
  - Phone
  - GSTIN number
  - Terms & Conditions

### 3. **Set Up Backups**
- Azure Database: Enable automatic backups
- App Service: Enable backup (Settings → Backups)
- Schedule: Daily backups with 30-day retention

### 4. **Configure Monitoring**
- Enable Application Insights
- Set up alerts for:
  - Database connection failures
  - High CPU usage
  - Error rates

### 5. **Performance Optimization**
- Enable Azure CDN for static files
- Configure caching headers
- Enable compression

---

## Azure Costs Estimate (Monthly)

| Service | Tier | Cost (USD) |
|---------|------|------------|
| App Service | B1 Basic | ~$13 |
| Azure MySQL | B1 Basic | ~$25 |
| Storage (optional) | 10GB | ~$2 |
| **Total** | | **~$40/month** |

*For production with higher traffic, consider Standard tier (~$70-100/month)*

---

## Maintenance & Updates

### Database Backups
```bash
# Automated via Azure (configured above)
# Manual backup:
mysqldump -h your-db.mysql.database.azure.com \
  -u dbadmin@your-db -p ospos > backup_$(date +%Y%m%d).sql
```

### Application Updates
```bash
git pull origin master
gulp default
composer install --no-dev
git push azure master
```

### Monitor Logs
- Azure Portal → App Service → Log Stream
- Or download logs:
  ```bash
  az webapp log download --name voxxera-pos --resource-group your-rg
  ```

---

## Troubleshooting Common Issues

### Issue: Database Connection Failed
- Check firewall rules allow Azure services
- Verify credentials in Application Settings
- Check SSL certificate path

### Issue: 500 Internal Server Error
- Check logs in Azure Log Stream
- Verify writable folders have permissions
- Check .env file is present

### Issue: Sessions Not Persisting
- Verify database sessions table exists
- Check session handler in .env

---

## Support & Resources

- **Azure MySQL Documentation**: https://docs.microsoft.com/azure/mysql/
- **Azure App Service PHP**: https://docs.microsoft.com/azure/app-service/
- **OSPOS Documentation**: Already customized for Voxxera POS

---

## Quick Deploy Commands (Summary)

```bash
# 1. Create resource group
az group create --name voxxera-rg --location eastus

# 2. Create MySQL server
az mysql server create --resource-group voxxera-rg --name voxxera-db \
  --admin-user dbadmin --admin-password STRONG_PASSWORD --sku-name B_Gen5_1

# 3. Create App Service plan
az appservice plan create --name voxxera-plan --resource-group voxxera-rg \
  --sku B1 --is-linux

# 4. Create Web App
az webapp create --resource-group voxxera-rg --plan voxxera-plan \
  --name voxxera-pos --runtime "PHP:8.2"

# 5. Configure database firewall
az mysql server firewall-rule create --resource-group voxxera-rg \
  --server voxxera-db --name AllowAzure --start-ip-address 0.0.0.0 --end-ip-address 0.0.0.0

# 6. Deploy code
git remote add azure <git-url-from-azure>
git push azure master
```

---

**Ready to deploy!** Would you like me to help with any specific step?

