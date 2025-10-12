#!/bin/bash

# Sitemap Generation Script for Bible Concordance
# This script can be run via cron to automatically update sitemaps

# Change to the script directory
cd "$(dirname "$0")"

# Log file for tracking sitemap generation
LOG_FILE="sitemap_generation.log"

# Function to log messages with timestamp
log_message() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') - $1" >> "$LOG_FILE"
    echo "$1"
}

log_message "Starting sitemap generation..."

# Run the PHP sitemap generator
php sitemap.php >> "$LOG_FILE" 2>&1

# Check if sitemap.xml was created successfully
if [ -f "sitemap.xml" ]; then
    log_message "Sitemap generation completed successfully!"
    
    # Submit sitemap to Google (requires Google Search Console setup)
    # Uncomment the line below if you want to automatically ping Google
    # curl -s "https://www.google.com/webmasters/tools/ping?sitemap=https://www.wordofgod.in/bible-concordance/sitemap.xml"
    
else
    log_message "ERROR: Sitemap generation failed!"
    exit 1
fi

log_message "Sitemap generation process completed."