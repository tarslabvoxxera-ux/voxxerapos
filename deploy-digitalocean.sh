#!/bin/bash

echo "╔═══════════════════════════════════════════════════════════╗"
echo "║   Voxxera POS - DigitalOcean Deployment Script           ║"
echo "╚═══════════════════════════════════════════════════════════╝"
echo ""

# Check if doctl is installed
if ! command -v doctl &> /dev/null; then
    echo "📥 Installing DigitalOcean CLI (doctl)..."
    brew install doctl
    echo "✅ Installed!"
    echo ""
    echo "🔐 Now you need to authenticate:"
    echo "   1. Go to: https://cloud.digitalocean.com/account/api/tokens"
    echo "   2. Create new token (read + write access)"
    echo "   3. Run: doctl auth init"
    echo "   4. Paste your token"
    echo ""
    read -p "Press Enter after completing authentication..."
else
    echo "✅ DigitalOcean CLI already installed"
fi

echo ""
echo "🔍 Checking authentication..."
if ! doctl account get &> /dev/null; then
    echo "❌ Not authenticated. Please run: doctl auth init"
    exit 1
fi

echo "✅ Authenticated!"
echo ""

# Get account info
ACCOUNT_EMAIL=$(doctl account get --format Email --no-header)
echo "📧 Account: $ACCOUNT_EMAIL"
echo ""

# Configuration
DROPLET_NAME="voxxera-pos"
REGION="nyc1"  # Change if needed (sgp1 for Singapore, blr1 for Bangalore, etc.)
SIZE="s-1vcpu-1gb"  # $6/month
IMAGE="docker-20-04"  # Ubuntu 20.04 with Docker pre-installed

echo "╔═══════════════════════════════════════════════════════════╗"
echo "║   Creating Droplet Configuration                          ║"
echo "╚═══════════════════════════════════════════════════════════╝"
echo ""
echo "Droplet Name: $DROPLET_NAME"
echo "Region: $REGION"
echo "Size: $SIZE ($6/month - but FREE with your $200 credit!)"
echo "Image: Ubuntu 20.04 with Docker"
echo ""
read -p "Press Enter to create droplet (or Ctrl+C to cancel)..."

# Create droplet
echo ""
echo "🚀 Creating droplet (this takes ~60 seconds)..."
DROPLET_ID=$(doctl compute droplet create $DROPLET_NAME \
    --region $REGION \
    --size $SIZE \
    --image $IMAGE \
    --wait \
    --format ID \
    --no-header)

if [ -z "$DROPLET_ID" ]; then
    echo "❌ Failed to create droplet"
    exit 1
fi

echo "✅ Droplet created! ID: $DROPLET_ID"
echo ""

# Get droplet IP
echo "⏳ Waiting for droplet to be ready..."
sleep 10
DROPLET_IP=$(doctl compute droplet get $DROPLET_ID --format PublicIPv4 --no-header)

echo "✅ Droplet ready!"
echo ""
echo "╔═══════════════════════════════════════════════════════════╗"
echo "║   Droplet Information                                     ║"
echo "╚═══════════════════════════════════════════════════════════╝"
echo ""
echo "🌐 IP Address: $DROPLET_IP"
echo "📛 Name: $DROPLET_NAME"
echo "🗺️  Region: $REGION"
echo "💰 Cost: $6/month (FREE for 33 months with $200 credit)"
echo ""

# Wait for SSH to be ready
echo "⏳ Waiting for SSH to be ready (30 seconds)..."
sleep 30

echo ""
echo "╔═══════════════════════════════════════════════════════════╗"
echo "║   Deploying Voxxera POS                                   ║"
echo "╚═══════════════════════════════════════════════════════════╝"
echo ""

# Copy files to droplet
echo "📤 Uploading Voxxera POS files..."
cd "/Users/iamsnh/Downloads/opensourcepos-master 12"

# Create deployment package (exclude large files)
echo "📦 Creating deployment package..."
tar -czf /tmp/voxxera-deploy.tar.gz \
    --exclude='vendor' \
    --exclude='node_modules' \
    --exclude='.git' \
    --exclude='writable/cache/*' \
    --exclude='writable/logs/*' \
    .

# Upload to droplet
echo "⬆️  Uploading to droplet..."
scp -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null \
    /tmp/voxxera-deploy.tar.gz root@$DROPLET_IP:/root/

# Extract and deploy
echo "🚀 Deploying on droplet..."
ssh -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null root@$DROPLET_IP << 'ENDSSH'
cd /root
mkdir -p voxxera
tar -xzf voxxera-deploy.tar.gz -C voxxera/
cd voxxera

# Install docker-compose if not present
if ! command -v docker-compose &> /dev/null; then
    apt-get update
    apt-get install -y docker-compose
fi

# Start services
docker-compose -f docker-compose.production.yml up -d

# Wait for services to be ready
echo "⏳ Waiting for services to start..."
sleep 20

# Check status
docker-compose -f docker-compose.production.yml ps
ENDSSH

echo ""
echo "╔═══════════════════════════════════════════════════════════╗"
echo "║   🎊 DEPLOYMENT COMPLETE!                                 ║"
echo "╚═══════════════════════════════════════════════════════════╝"
echo ""
echo "🌐 Your Voxxera POS is now PUBLIC and accessible at:"
echo ""
echo "   ┌─────────────────────────────────────────┐"
echo "   │  http://$DROPLET_IP:8888         │"
echo "   └─────────────────────────────────────────┘"
echo ""
echo "🔐 Login Credentials:"
echo "   Username: Faizankhanstores"
echo "   Password: Faizankhanstores"
echo ""
echo "📝 Next Steps:"
echo "   1. Open: http://$DROPLET_IP:8888"
echo "   2. Login and change password"
echo "   3. Configure store details (Office → Information)"
echo ""
echo "🔧 Useful Commands:"
echo "   SSH to droplet: ssh root@$DROPLET_IP"
echo "   View logs: ssh root@$DROPLET_IP 'cd voxxera && docker-compose logs -f'"
echo "   Restart: ssh root@$DROPLET_IP 'cd voxxera && docker-compose restart'"
echo ""
echo "💰 Cost: $6/month (FREE for 33 months with $200 credit)"
echo ""
echo "🎉 Your store is now online worldwide!"
echo ""

# Save deployment info
cat > /tmp/voxxera-deployment-info.txt << EOF
Voxxera POS - DigitalOcean Deployment
======================================

Deployment Date: $(date)
Droplet ID: $DROPLET_ID
Droplet IP: $DROPLET_IP
Region: $REGION

Access URL: http://$DROPLET_IP:8888

Login:
  Username: Faizankhanstores
  Password: Faizankhanstores

SSH Access: ssh root@$DROPLET_IP

Useful Commands:
  - View logs: ssh root@$DROPLET_IP 'cd voxxera && docker-compose -f docker-compose.production.yml logs -f'
  - Restart: ssh root@$DROPLET_IP 'cd voxxera && docker-compose -f docker-compose.production.yml restart'
  - Stop: ssh root@$DROPLET_IP 'cd voxxera && docker-compose -f docker-compose.production.yml down'
  - Start: ssh root@$DROPLET_IP 'cd voxxera && docker-compose -f docker-compose.production.yml up -d'

Cost: $6/month (FREE for 33 months with $200 credit)

Status: ✅ DEPLOYED
EOF

echo "📄 Deployment info saved to: /tmp/voxxera-deployment-info.txt"
cat /tmp/voxxera-deployment-info.txt

