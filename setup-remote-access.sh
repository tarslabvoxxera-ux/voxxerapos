#!/bin/bash

# Voxxera POS - Remote Access Setup
# This script helps you expose your local Voxxera POS to the internet securely

echo "╔══════════════════════════════════════════════════════════╗"
echo "║     Voxxera POS - Remote Access Setup                    ║"
echo "╚══════════════════════════════════════════════════════════╝"
echo ""

# Check if Docker containers are running
echo "🔍 Checking if Voxxera POS is running..."
if docker ps | grep -q voxxera_pos_prod; then
    echo "✅ Voxxera POS is running!"
else
    echo "❌ Voxxera POS is not running. Starting it..."
    cd "/Users/iamsnh/Downloads/opensourcepos-master 12"
    docker-compose -f docker-compose.production.yml up -d
    sleep 10
fi

echo ""
echo "📋 Select Remote Access Method:"
echo ""
echo "1. Cloudflare Tunnel (FREE, Recommended)"
echo "2. ngrok (FREE tier available)"
echo "3. Show local network URL"
echo "4. Exit"
echo ""
read -p "Enter your choice (1-4): " choice

case $choice in
    1)
        echo ""
        echo "🌐 Setting up Cloudflare Tunnel..."
        echo ""
        
        # Check if cloudflared is installed
        if ! command -v cloudflared &> /dev/null; then
            echo "📥 Installing Cloudflare Tunnel..."
            brew install cloudflare/cloudflare/cloudflared
        else
            echo "✅ Cloudflare Tunnel already installed"
        fi
        
        echo ""
        echo "🚀 Starting Cloudflare Tunnel..."
        echo ""
        echo "⚠️  Keep this terminal window open!"
        echo "⚠️  You'll get a URL like: https://random-name.trycloudflare.com"
        echo "⚠️  Share that URL to access your POS from anywhere!"
        echo ""
        echo "Press Ctrl+C to stop the tunnel when done."
        echo ""
        sleep 3
        cloudflared tunnel --url http://localhost:8888
        ;;
    
    2)
        echo ""
        echo "🌐 Setting up ngrok..."
        echo ""
        
        # Check if ngrok is installed
        if ! command -v ngrok &> /dev/null; then
            echo "📥 Installing ngrok..."
            brew install ngrok
        else
            echo "✅ ngrok already installed"
        fi
        
        echo ""
        echo "🔐 ngrok requires a free account:"
        echo "   1. Go to https://dashboard.ngrok.com/signup"
        echo "   2. Sign up (free)"
        echo "   3. Get your authtoken"
        echo "   4. Run: ngrok authtoken YOUR_TOKEN"
        echo ""
        read -p "Have you set up ngrok authtoken? (y/n): " setup
        
        if [[ $setup == "y" || $setup == "Y" ]]; then
            echo ""
            echo "🚀 Starting ngrok tunnel..."
            echo ""
            echo "⚠️  Keep this terminal window open!"
            echo "⚠️  You'll get a URL like: https://abc123.ngrok.io"
            echo ""
            echo "Press Ctrl+C to stop the tunnel when done."
            echo ""
            sleep 3
            ngrok http 8888
        else
            echo ""
            echo "Please complete ngrok setup first:"
            echo "1. Visit: https://dashboard.ngrok.com/signup"
            echo "2. Sign up and get your authtoken"
            echo "3. Run: ngrok authtoken YOUR_TOKEN"
            echo "4. Then run this script again"
        fi
        ;;
    
    3)
        echo ""
        echo "📱 Local Network Access:"
        echo ""
        echo "Your Voxxera POS is accessible on your local network at:"
        echo ""
        echo "🌐 Local URLs:"
        echo "   - http://localhost:8888 (this computer)"
        
        # Get all IP addresses
        IPS=$(ifconfig | grep "inet " | grep -v 127.0.0.1 | awk '{print $2}')
        
        for IP in $IPS; do
            echo "   - http://$IP:8888 (from other devices on same WiFi)"
        done
        
        echo ""
        echo "📱 To access from your phone/tablet:"
        echo "   1. Connect to the same WiFi network"
        echo "   2. Open browser and go to one of the URLs above"
        echo ""
        echo "⚠️  Note: Only works on same network, not from outside"
        echo ""
        ;;
    
    4)
        echo "Goodbye!"
        exit 0
        ;;
    
    *)
        echo "Invalid choice. Please run the script again."
        exit 1
        ;;
esac

