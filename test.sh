#!/bin/bash

# ===== CONFIGURATION =====
BASE_URL="https://codecarehub.space/x9Nq4GkL2v7TzR1s"  # Change this to your actual domain path
OUTPUT_FILE="/tmp/php_routes_report.txt"
MAX_CHARS=100   # Limit response body to 100 chars

# ===== INIT =====
echo "Testing PHP files under $BASE_URL" > "$OUTPUT_FILE"
echo "Generated on: $(date)" >> "$OUTPUT_FILE"
echo "------------------------------------" >> "$OUTPUT_FILE"

# ===== LOOP THROUGH FILES =====
for file in *.php; do
    echo "Testing: $file ..."
    
    # Perform request
    RESPONSE=$(curl -s -w "HTTP_CODE:%{http_code}" "$BASE_URL/$file")
    
    # Extract HTTP code
    HTTP_CODE=$(echo "$RESPONSE" | grep -o "HTTP_CODE:[0-9]*" | cut -d':' -f2)
    
    # Extract body and trim to 100 chars
    BODY=$(echo "$RESPONSE" | sed 's/HTTP_CODE:[0-9]*//' | tr -d '\n' | cut -c1-$MAX_CHARS)
    
    # Write to log
    echo -e "\n$file  --->  HTTP $HTTP_CODE" >> "$OUTPUT_FILE"
    echo "$BODY" >> "$OUTPUT_FILE"
    echo "------------------------------------" >> "$OUTPUT_FILE"
done

echo "✅ Report saved at $OUTPUT_FILE"

