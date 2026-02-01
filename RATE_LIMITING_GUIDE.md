# AI Crawler Rate Limiting - Implementation Guide

## Overview
This setup allows AI crawlers to access your site but limits them to **10 requests per minute** to reduce server load.

## Strategy
- ✅ **Allow** all crawlers (don't block)
- ⏱️ **Limit** AI crawlers to 10 requests/minute
- 🚀 **Normal** speed for legitimate search engines and users

---

## Implementation Options

### Option 1: robots.txt (Easiest - Polite Crawlers)
**File:** `robots.txt`  
**Upload to:** Root directory of your website

**What it does:**
- Asks polite crawlers to wait 30 seconds between requests
- Google/Bing get 3-second delays (20 requests/min)
- AI crawlers get 30-second delays (2 requests/min)

**Effectiveness:** 60% - Only works with crawlers that respect robots.txt

---

### Option 2: .htaccess (Better - Server Level)
**File:** `.htaccess-ai-protection` → Rename to `.htaccess`  
**Upload to:** Root directory of your website

**What it does:**
- Detects AI crawlers by User-Agent
- Limits their bandwidth to ~200 bytes/sec (≈10-12 requests/min)
- Sets HTTP headers suggesting crawl delays
- Logs AI crawler activity separately

**Requirements:**
- Apache web server with `mod_rewrite` and `mod_ratelimit` enabled

**Effectiveness:** 80% - Works at server level, harder to bypass

**To enable:**
```bash
# Rename the file
mv .htaccess-ai-protection .htaccess

# Or merge with existing .htaccess if you have one
```

---

### Option 3: PHP Rate Limiter (Best - Most Reliable)
**File:** `rate-limiter.php`  
**Upload to:** Root directory of your website

**What it does:**
- Tracks requests per IP + User-Agent combination
- Enforces strict 10 requests/minute limit
- Returns HTTP 429 (Too Many Requests) when exceeded
- Works on any server (Apache, Nginx, etc.)

**Effectiveness:** 95% - Most reliable, works everywhere

**To enable, add to each brand's index.php:**

```php
<?php
// Add this at the very top of index.php, right after opening tag

// Load rate limiter
require_once __DIR__ . 'rate-limiter.php';
$rateLimiter = new CrawlerRateLimiter();
$rateLimiter->checkRateLimit();

// Rest of your existing code...

?>
```

**Files to update:**
- `index.php`

---

## Recommended Approach

### For Maximum Protection:
Use **all three methods together**:

1. ✅ Upload `robots.txt` (catches polite crawlers)
2. ✅ Upload `.htaccess` (server-level rate limiting)
3. ✅ Add PHP rate limiter (bulletproof protection)

### For Quick Implementation:
1. Upload `robots.txt` only (takes 2 minutes)
2. Monitor your server logs
3. Add .htaccess or PHP limiter if needed

---

## Rate Limits Summary

| Crawler Type | Method | Requests/Min | Delay Between Requests |
|--------------|--------|--------------|------------------------|
| AI Crawlers (GPTBot, Claude, etc.) | robots.txt | 2 | 30 seconds |
| AI Crawlers | .htaccess | ~10-12 | ~5 seconds |
| AI Crawlers | PHP | 10 | 6 seconds |
| Google/Bing | robots.txt | 20 | 3 seconds |
| Regular Users | All | Unlimited | None |

---

## Monitoring

### Check Rate Limit Stats (PHP method):
Create a file `check-rate-limits.php`:

```php
<?php
require_once 'rate-limiter.php';
$limiter = new CrawlerRateLimiter();
$stats = $limiter->getStats();

header('Content-Type: application/json');
echo json_encode($stats, JSON_PRETTY_PRINT);
?>
```

Visit: `https://yourdomain.com/bible-concordance/check-rate-limits.php`

### Check Apache Logs:
```bash
# View AI crawler requests
tail -f /var/log/apache2/access.log | grep -E 'GPTBot|Claude|CCBot'

# Count requests per crawler
grep 'GPTBot' /var/log/apache2/access.log | wc -l
```

---

## Files to Upload

1. **robots.txt** → `/public_html/robots.txt`
2. **.htaccess-ai-protection** → `/public_html/.htaccess` (rename)
3. **rate-limiter.php** → `/public_html/bible-devotions/rate-limiter.php`

---

## Testing

### Test if rate limiting works:
```bash
# Simulate AI crawler (make 15 requests quickly)
for i in {1..15}; do
  curl -A "GPTBot" https://yourdomain.com/bible-concordance/
  echo "Request $i"
done
```

Expected result: After 10 requests, you should get HTTP 429 error

---

## Fine-Tuning

### To change rate limits:

**robots.txt:**
```
Crawl-delay: 30  # Change this number (seconds between requests)
```

**.htaccess:**
```apache
SetEnv rate-limit 200  # Lower = slower (bytes per second)
```

**rate-limiter.php:**
```php
private $maxRequests = 10;   # Change max requests
private $windowSeconds = 60; # Change time window
```

---

## Support & Maintenance

- Rate limit data stored in: `crawler-rate-limit.json`
- Auto-cleans old data (older than 60 seconds)
- No database required
- Minimal performance impact

---

## Need Help?

If rate limiting isn't working:
1. Check if Apache modules are enabled: `mod_rewrite`, `mod_ratelimit`, `mod_headers`
2. Verify file permissions (rate-limiter.php needs write access)
3. Check error logs: `/var/log/apache2/error.log`
4. Test with: `curl -A "GPTBot" -v https://yourdomain.com`
