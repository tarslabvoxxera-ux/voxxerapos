#!/bin/bash

echo "╔═══════════════════════════════════════════════════════════╗"
echo "║   Voxxera POS - INSTANT PUBLIC DEPLOYMENT                 ║"
echo "╚═══════════════════════════════════════════════════════════╝"
echo ""

# Check if Voxxera is running
if ! docker ps | grep -q voxxera_pos_prod; then
    echo "🚀 Starting Voxxera POS..."
    cd "/Users/iamsnh/Downloads/opensourcepos-master 12"
    docker-compose -f docker-compose.production.yml up -d
    echo "⏳ Waiting for startup..."
    sleep 15
fi

echo "✅ Voxxera POS is running!"
echo ""
echo "🌐 DEPLOYING PUBLICLY NOW..."
echo ""
echo "╔═══════════════════════════════════════════════════════════╗"
echo "║  Installing Cloudflare Tunnel (if needed)...             ║"
echo "╚═══════════════════════════════════════════════════════════╝"

if ! command -v cloudflared &> /dev/null; then
    echo "📥 Installing Cloudflare Tunnel..."
    brew install cloudflare/cloudflare/cloudflared
    echo "✅ Installed!"
else
    echo "✅ Already installed!"
fi

echo ""
echo "╔═══════════════════════════════════════════════════════════╗"
echo "║  🎊 CREATING PUBLIC URL - KEEP THIS WINDOW OPEN!         ║"
echo "╚═══════════════════════════════════════════════════════════╝"
echo ""
echo "⚠️  You will get a URL like: https://random.trycloudflare.com"
echo "⚠️  Share that URL to access from anywhere!"
echo "⚠️  Press Ctrl+C when done using the public URL"
echo ""
echo "Starting tunnel in 3 seconds..."
sleep 3
echo ""
echo "🚀 YOUR PUBLIC URL:"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

cloudflared tunnel --url http://localhost:8888

