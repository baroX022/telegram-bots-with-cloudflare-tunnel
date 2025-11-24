#!/bin/bash

# --- Configuration ---
TOKEN=""
LOCAL_BOT_URL="http://127.0.0.1:6644/"
LOG_FILE="/tmp/cloudflared.log"
ENTRY_POINT="main.php"
WAIT_TIME=15

echo "Starting cloudflared tunnel..."

stdbuf -oL cloudflared tunnel --url "$LOCAL_BOT_URL" 2>&1 | tee "$LOG_FILE" &
CLOUDFLARED_PID=$!

echo "Waiting ${WAIT_TIME} seconds for tunnel establishment..."
sleep $WAIT_TIME

GENERATED_URL=$(grep -o "https://[^[:space:]]*trycloudflare\\.com" "$LOG_FILE" | head -1)

if [[ -z "$GENERATED_URL" ]]; then
    echo "[ERROR]: Failed to extract Cloudflare URL from logs"
    kill $CLOUDFLARED_PID 2>/dev/null
    exit 1
fi

WEBHOOK_URL="$GENERATED_URL/$ENTRY_POINT"
TELEGRAM_API_URL="https://api.telegram.org/bot$TOKEN/setWebhook?url=$WEBHOOK_URL"

echo "Extracted URL: $GENERATED_URL"
echo "Setting webhook to: $WEBHOOK_URL"

if curl -s -X POST "$TELEGRAM_API_URL"; then
    echo "Webhook set successfully. Tunnel running (PID: $CLOUDFLARED_PID)"
else
    echo "[ERROR]: Failed to set webhook"
    kill $CLOUDFLARED_PID 2>/dev/null
    exit 1
fi

wait $CLOUDFLARED_PID