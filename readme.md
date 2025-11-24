# تشغيل بوتات التليقرام على جهازك باستخدام Cloudflare Tunnel + PM2

السلام عليكم

بهذا الشرح بوضح لكم كيف خليت واحد من بوتاتي على التليقرام يشتغل مع جهازي بشكل دائم، حتى لو سويت restart يرجع يشتغل لحاله بدون أي تدخل.

الطريقة سهلة وتناسب أي شخص يبغى يربط البوت بجهازه بدل ما يحطه على استضافة.

## الفكرة باختصار

- نشغل البوت محليًا على جهازك.
- نشغل [Cloudflare Tunnel](https://developers.cloudflare.com/cloudflare-one/networks/connectors/cloudflare-tunnel/) عشان يصنع رابط عشوائي للبوت.
- نسوي setWebhook تلقائي كل مرة يشتغل فيها الجهاز.
- نستخدم [PM2](https://www.npmjs.com/package/pm2) عشان نخلي كل شيء يعمل أوتماتيك بدون قروشة.

تقدر تستخدم رابط دومين ثابت بدون الرابط العشوائي الي يعطيك اياها الكلاود. بالاضافة اذا بوتك مايتفاعل مع المستخدم ماتحتاج التونل بكبره لان غالبا بيكون وضعك ارسال تنبيهات.

# تشغيل البوت محليًا

أول شيء شغلت البوت عن طريق السيرفر المدمج حق PHP:

```
php -S 127.0.0.1:6644 -t /home/mhd/bots/bot1
```

بس طبعا ماراح نشغله بشكل مباشر كذا راح نستخدم سكربت شل. لاتنسى تغير مسار /home/mhd/bots/bot1 الى مسار البوت حقك.

# تجهيز سكربت تشغيل البوت عبر PM2

## إنشاء ملف bot1.sh:

```bash
touch bot1.sh
nano bot1.sh
```

محتوى الملف:

```bash
#!/bin/bash
php -S 127.0.0.1:6644 -t /home/mhd/bots/bot1
```

نعطيه تصريح التشغيل:

```bash
chmod +x bot1.sh
```

نضيفه للـ pm2:

```bash
pm2 start /home/mhd/autoloading/bot1.sh --name "php server for my 1st bot"
```

# سكربت Cloudflare Tunnel + Webhook (أوتوماتيكي)

هذا السكربت يشغل التونل، يستخرج الرابط الجديد، ويسوي setWebhook لوحده:

```bash
#!/bin/bash

# --- Configuration ---
TOKEN="" # التوكن حق البوت من BotFather
LOCAL_BOT_URL="http://127.0.0.1:6644/"
LOG_FILE="/tmp/cloudflared.log"
ENTRY_POINT="main.php" # ملف البوت الأساسي
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
```

بعد ماتعدل المتغيرات الي فوق ,طبق

```bash
chmod +x tunnel.sh
pm2 start /home/mhd/autoloading/tunnel.sh --name "autoloading tunnel for my bot 1"
```

### تقدر تتأكد من أن كل شيء شغل من خلال

```bash
pm2 list #pm2 status
```

لو شفتهم كلهم **online**، أمورك تمام.

---

# تفعيل التشغيل التلقائي لـ PM2

اذا أول مرة تستخدم البرنامج , طبق الامر التالي :

```bash
pm2 startup
```

راح يطلع لك امر خاص فيك انسخه وطبقه, بيكون شكله قريب من هذا

```bash
sudo env PATH=$PATH:/usr/local/bin /usr/local/lib/node_modules/pm2/bin/pm2 startup systemd -u mhd --hp /home/mhd
```

بعدين نحفظ الي سويناه من خلال

```bash
pm2 save
```

وبكذا تكون ضامن إن كل شيء يرجع يشتغل بعد restart بدون أي تدخل منك.

بعد كل اللي فوق:

- البوت يشتغل تلقائي.
- التونل يتفتح لحاله.
- الويب هوك يتحدث برابط جديد كل مرة.
- ولا تحتاج تلمس أي شيء بعد الإعداد.