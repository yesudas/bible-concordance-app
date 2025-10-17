<?php
// counter.php

// List of common bot keywords in User-Agent
$botKeywords = [
    'bot', 'crawl', 'slurp', 'spider', 'mediapartners', 'curl', 'python', 'wget', 'baiduspider', 'bingpreview', 'facebookexternalhit', 'pingdom'
];

// Get lowercase user agent
$userAgent = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');

// Check if it's a bot
$isBot = false;
foreach ($botKeywords as $keyword) {
    if (strpos($userAgent, $keyword) !== false) {
        $isBot = true;
        break;
    }
}

$visitors2 = '1';

// If not a bot, increment the counter
if (!$isBot) {
    $counterFile = __DIR__ . '/counter.txt';
    
    // If file doesn’t exist, create it with 0
    if (!file_exists($counterFile)) {
        file_put_contents($counterFile, "0");
    }

    // Open file for reading and writing
    $fp = fopen($counterFile, "c+"); // c+ = read/write, create if not exists

    if ($fp && flock($fp, LOCK_EX)) { // lock file exclusively
        // Read current count - read entire file content
        rewind($fp); // Make sure we're at the beginning
        $currentContent = fread($fp, 1024); // Read up to 1024 bytes (more than enough for a counter)
        $count = (int)trim($currentContent); // Convert to integer and trim whitespace
        
        // If file was empty or invalid, start from 0
        if ($count < 0) {
            $count = 0;
        }
    
        // Increment
        $count++;
    
        $visitors2 = $count;
    
        // Rewind and write new value
        ftruncate($fp, 0);  // clear file
        rewind($fp);
        fwrite($fp, (string)$count);
    
        fflush($fp);        // flush output
        flock($fp, LOCK_UN); // unlock
        fclose($fp);
    } else {
        // Could not open or lock file - fallback to reading existing value
        if (file_exists($counterFile)) {
            $visitors2 = (int)trim(file_get_contents($counterFile));
        } else {
            $visitors2 = 1; // Default fallback
        }
        
        if ($fp) {
            fclose($fp);
        }
    }

}

?>