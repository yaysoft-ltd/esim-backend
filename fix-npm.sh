#!/bin/bash

echo "Fixing NPM dependency issues..."

# Remove conflicting packages
sudo apt remove -y nodejs npm

# Clean package cache
sudo apt autoremove -y
sudo apt autoclean

# Add NodeSource repository
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -

# Install Node.js (includes npm)
sudo apt install -y nodejs

# Verify installation
echo "Node.js version:"
node --version
echo "NPM version:"
npm --version

echo "NPM fix completed!"