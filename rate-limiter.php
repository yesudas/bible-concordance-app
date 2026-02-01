<?php
/**
 * Rate Limiter for AI Crawlers
 * Include this at the top of your main PHP files
 * Limits requests to 10 per minute per IP/User-Agent combination
 */

class CrawlerRateLimiter {
    private $rateFile;
    private $maxRequests = 10;  // Maximum requests per minute
    private $windowSeconds = 60; // Time window in seconds
    
    public function __construct() {
        $this->rateFile = __DIR__ . '/crawler-rate-limit.json';
    }
    
    /**
     * Check if current request should be rate limited
     */
    public function checkRateLimit() {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $ip = $this->getClientIP();
        
        // Check if it's an AI crawler
        $isAICrawler = $this->isAICrawler($userAgent);
        
        if ($isAICrawler) {
            // For AI crawlers, enforce strict rate limiting
            $key = md5($ip . ':' . $userAgent);
            
            if ($this->isRateLimited($key)) {
                // Rate limit exceeded
                http_response_code(429); // Too Many Requests
                header('Retry-After: 60');
                header('X-RateLimit-Limit: ' . $this->maxRequests);
                header('X-RateLimit-Remaining: 0');
                header('X-RateLimit-Reset: ' . (time() + 60));
                
                echo json_encode([
                    'error' => 'Rate limit exceeded',
                    'message' => 'Too many requests. Please wait before making another request.',
                    'retry_after' => 60,
                    'limit' => $this->maxRequests . ' requests per minute'
                ]);
                exit;
            }
            
            // Record this request
            $this->recordRequest($key);
        }
    }
    
    /**
     * Check if user agent is an AI crawler
     */
    private function isAICrawler($userAgent) {
        $aiCrawlers = [
            'GPTBot', 'ChatGPT', 'Claude', 'ClaudeBot', 'anthropic',
            'CCBot', 'Google-Extended', 'Omgilibot', 'Amazonbot',
            'Bytespider', 'PerplexityBot', 'Applebot-Extended', 'Diffbot',
            'ImagesiftBot', 'img2dataset', 'cohere-ai', 'FacebookBot'
        ];
        
        foreach ($aiCrawlers as $crawler) {
            if (stripos($userAgent, $crawler) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if request is rate limited
     */
    private function isRateLimited($key) {
        $data = $this->loadRateData();
        
        if (!isset($data[$key])) {
            return false;
        }
        
        // Clean old entries
        $cutoff = time() - $this->windowSeconds;
        $data[$key] = array_filter($data[$key], function($timestamp) use ($cutoff) {
            return $timestamp > $cutoff;
        });
        
        // Check if limit exceeded
        return count($data[$key]) >= $this->maxRequests;
    }
    
    /**
     * Record a request
     */
    private function recordRequest($key) {
        $data = $this->loadRateData();
        
        if (!isset($data[$key])) {
            $data[$key] = [];
        }
        
        // Add current timestamp
        $data[$key][] = time();
        
        // Clean old entries from all keys
        $cutoff = time() - $this->windowSeconds;
        foreach ($data as $k => $timestamps) {
            $data[$k] = array_filter($timestamps, function($timestamp) use ($cutoff) {
                return $timestamp > $cutoff;
            });
            
            // Remove empty entries
            if (empty($data[$k])) {
                unset($data[$k]);
            }
        }
        
        $this->saveRateData($data);
    }
    
    /**
     * Load rate limiting data from file
     */
    private function loadRateData() {
        if (!file_exists($this->rateFile)) {
            return [];
        }
        
        $data = json_decode(file_get_contents($this->rateFile), true);
        return is_array($data) ? $data : [];
    }
    
    /**
     * Save rate limiting data to file
     */
    private function saveRateData($data) {
        file_put_contents($this->rateFile, json_encode($data));
    }
    
    /**
     * Get client IP address
     */
    private function getClientIP() {
        $ipKeys = [
            'HTTP_CF_CONNECTING_IP',  // CloudFlare
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR'
        ];
        
        foreach ($ipKeys as $key) {
            if (isset($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                // Handle multiple IPs (take first one)
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    /**
     * Get current rate limit stats for monitoring
     */
    public function getStats() {
        $data = $this->loadRateData();
        $cutoff = time() - $this->windowSeconds;
        
        $stats = [];
        foreach ($data as $key => $timestamps) {
            $recent = array_filter($timestamps, function($ts) use ($cutoff) {
                return $ts > $cutoff;
            });
            
            if (!empty($recent)) {
                $stats[$key] = [
                    'requests' => count($recent),
                    'limit' => $this->maxRequests,
                    'remaining' => max(0, $this->maxRequests - count($recent))
                ];
            }
        }
        
        return $stats;
    }
}

// Usage: Include this at the top of your index.php files
// require_once __DIR__ . '/../rate-limiter.php';
// $rateLimiter = new CrawlerRateLimiter();
// $rateLimiter->checkRateLimit();

?>
