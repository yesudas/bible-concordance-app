<?php
// Sitemap Generator for Bible Concordance
// This script generates XML sitemaps for all language, bible, letter, word, and verse combinations

ini_set('memory_limit', '1G');
ini_set('max_execution_time', 600); // 10 minutes
error_reporting(E_ALL);

$baseUrl = 'https://www.wordofgod.in/bible-concordance/';
$maxUrlsPerSitemap = 10000;
$currentDate = date('Y-m-d');

// Clean up old sitemap files
$oldSitemaps = glob('sitemap*.xml');
foreach ($oldSitemaps as $oldSitemap) {
    unlink($oldSitemap);
}

// Include the same helper functions from index.php
function getLanguages() {
    $languages = [];
    $dataDir = __DIR__ . '/data';
    if (is_dir($dataDir)) {
        $dirs = scandir($dataDir);
        foreach ($dirs as $dir) {
            if ($dir !== '.' && $dir !== '..' && is_dir($dataDir . '/' . $dir)) {
                $languages[] = $dir;
            }
        }
    }
    return $languages;
}

function getBibles($language) {
    $bibles = [];
    $langDir = __DIR__ . '/data/' . $language;
    if (is_dir($langDir)) {
        $dirs = scandir($langDir);
        foreach ($dirs as $dir) {
            if ($dir !== '.' && $dir !== '..' && is_dir($langDir . '/' . $dir)) {
                $concordanceFile = $langDir . '/' . $dir . '/Concordance.json';
                if (file_exists($concordanceFile)) {
                    $bibles[] = $dir;
                }
            }
        }
    }
    return $bibles;
}

function getLetters($language, $bible) {
    $letters = [];
    $concordanceFile = __DIR__ . '/data/' . $language . '/' . $bible . '/Concordance.json';
    if (file_exists($concordanceFile)) {
        $concordanceData = json_decode(file_get_contents($concordanceFile), true);
        if (isset($concordanceData['letters'])) {
            foreach ($concordanceData['letters'] as $letterItem) {
                $letters[] = $letterItem['letter'];
            }
        }
    }
    return array_unique($letters);
}

function getWords($language, $bible, $letter) {
    $words = [];
    
    // Try both uppercase and lowercase versions of the letter for file path
    $possiblePaths = [
        __DIR__ . '/data/' . $language . '/' . $bible . '/letters/' . $letter . '.json',
        __DIR__ . '/data/' . $language . '/' . $bible . '/letters/' . strtolower($letter) . '.json',
        __DIR__ . '/data/' . $language . '/' . $bible . '/letters/' . strtoupper($letter) . '.json'
    ];
    
    $letterFile = '';
    foreach ($possiblePaths as $path) {
        if (file_exists($path)) {
            $letterFile = $path;
            break;
        }
    }
    
    if ($letterFile) {
        $letterData = json_decode(file_get_contents($letterFile), true);
        if (isset($letterData['words'])) {
            foreach ($letterData['words'] as $wordItem) {
                $words[] = $wordItem['word'];
            }
        }
    }
    return array_unique($words);
}

function buildSitemapUrl($params = []) {
    global $baseUrl;
    
    $url = $baseUrl;
    $queryParams = [];
    
    if (!empty($params['lang'])) $queryParams['lang'] = $params['lang'];
    if (!empty($params['bible'])) $queryParams['bible'] = $params['bible'];
    if (!empty($params['letter'])) $queryParams['letter'] = $params['letter'];
    if (!empty($params['word'])) $queryParams['word'] = $params['word'];
    
    if (!empty($queryParams)) {
        $url .= '?' . http_build_query($queryParams);
    }
    
    return $url;
}

class SitemapGenerator {
    private $currentChunk = 1;
    private $currentUrls = [];
    private $sitemapFiles = [];
    private $totalUrls = 0;
    
    public function addUrl($url, $changefreq = 'monthly', $priority = '0.5') {
        global $maxUrlsPerSitemap;
        
        $this->currentUrls[] = [
            'loc' => $url,
            'changefreq' => $changefreq,
            'priority' => $priority
        ];
        
        $this->totalUrls++;
        
        // If we've reached the max URLs per sitemap, write the chunk
        if (count($this->currentUrls) >= $maxUrlsPerSitemap) {
            $this->writeChunk();
        }
    }
    
    public function writeChunk() {
        global $currentDate;
        
        if (empty($this->currentUrls)) {
            return;
        }
        
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        
        foreach ($this->currentUrls as $url) {
            $xml .= '  <url>' . "\n";
            $xml .= '    <loc>' . htmlspecialchars($url['loc']) . '</loc>' . "\n";
            $xml .= '    <lastmod>' . $currentDate . '</lastmod>' . "\n";
            $xml .= '    <changefreq>' . $url['changefreq'] . '</changefreq>' . "\n";
            $xml .= '    <priority>' . $url['priority'] . '</priority>' . "\n";
            $xml .= '  </url>' . "\n";
        }
        
        $xml .= '</urlset>';
        
        $filename = "sitemap-{$this->currentChunk}.xml";
        file_put_contents($filename, $xml);
        $this->sitemapFiles[] = $filename;
        
        echo "Created {$filename} with " . count($this->currentUrls) . " URLs\n";
        
        // Reset for next chunk
        $this->currentUrls = [];
        $this->currentChunk++;
        
        // Force garbage collection to manage memory
        gc_collect_cycles();
    }
    
    public function finalize() {
        // Write any remaining URLs
        if (!empty($this->currentUrls)) {
            $this->writeChunk();
        }
        
        // Create master sitemap
        $this->createMasterSitemap();
        
        return [
            'totalUrls' => $this->totalUrls,
            'sitemapFiles' => $this->sitemapFiles
        ];
    }
    
    private function createMasterSitemap() {
        global $baseUrl, $currentDate;
        
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        
        foreach ($this->sitemapFiles as $sitemapFile) {
            $xml .= '  <sitemap>' . "\n";
            $xml .= '    <loc>' . $baseUrl . $sitemapFile . '</loc>' . "\n";
            $xml .= '    <lastmod>' . $currentDate . '</lastmod>' . "\n";
            $xml .= '  </sitemap>' . "\n";
        }
        
        $xml .= '</sitemapindex>';
        
        file_put_contents('sitemap.xml', $xml);
        echo "Created master sitemap.xml\n";
    }
}

// Start generating sitemap
echo "Starting sitemap generation...\n";
echo "Base URL: {$baseUrl}\n";
echo "Date: {$currentDate}\n\n";
$startTime = microtime(true);

$generator = new SitemapGenerator();

// 1. Home page
$generator->addUrl(buildSitemapUrl(), 'daily', '1.0');
echo "Added home page URL\n";

// 2. Language pages
$languages = getLanguages();
echo "Found " . count($languages) . " languages\n";

foreach ($languages as $language) {
    try {
        // Language page
        $generator->addUrl(buildSitemapUrl(['lang' => $language]), 'weekly', '0.9');
        
        // 3. Bible pages for this language
        $bibles = getBibles($language);
        echo "Processing language '{$language}' with " . count($bibles) . " bibles\n";
        
        foreach ($bibles as $bible) {
            try {
                // Bible page
                $generator->addUrl(buildSitemapUrl(['lang' => $language, 'bible' => $bible]), 'weekly', '0.8');
                
                // 4. Letter pages for this bible
                $letters = getLetters($language, $bible);
                echo "  Bible '{$bible}' has " . count($letters) . " letters\n";
                
                foreach ($letters as $letter) {
                    try {
                        // Letter page
                        $generator->addUrl(buildSitemapUrl(['lang' => $language, 'bible' => $bible, 'letter' => $letter]), 'monthly', '0.7');
                        
                        // 5. Word pages for this letter
                        $words = getWords($language, $bible, $letter);
                        echo "    Letter '{$letter}' has " . count($words) . " words\n";
                        
                        foreach ($words as $word) {
                            // Word page (verses view)
                            $generator->addUrl(buildSitemapUrl(['lang' => $language, 'bible' => $bible, 'letter' => $letter, 'word' => $word]), 'monthly', '0.6');
                        }
                        
                    } catch (Exception $e) {
                        echo "    Error processing letter '{$letter}': " . $e->getMessage() . "\n";
                    }
                }
                
            } catch (Exception $e) {
                echo "  Error processing bible '{$bible}': " . $e->getMessage() . "\n";
            }
        }
        
    } catch (Exception $e) {
        echo "Error processing language '{$language}': " . $e->getMessage() . "\n";
    }
    
    // Show progress and memory usage
    echo "Progress: Completed language '{$language}'\n";
    echo "Memory usage: " . round(memory_get_usage(true) / 1024 / 1024, 2) . " MB\n\n";
    
    // Flush output buffer
    if (ob_get_level()) {
        ob_flush();
        flush();
    }
}

// Finalize sitemap generation
$result = $generator->finalize();

$endTime = microtime(true);
$executionTime = round($endTime - $startTime, 2);

echo "\n" . str_repeat("=", 60) . "\n";
echo "SITEMAP GENERATION COMPLETED!\n";
echo str_repeat("=", 60) . "\n";
echo "Execution time: {$executionTime} seconds\n";
echo "Peak memory usage: " . round(memory_get_peak_usage(true) / 1024 / 1024, 2) . " MB\n";
echo "Total URLs generated: " . number_format($result['totalUrls']) . "\n";
echo "Sitemap files created: " . count($result['sitemapFiles']) . "\n";
echo "Max URLs per file: " . number_format($maxUrlsPerSitemap) . "\n";

echo "\nFiles created:\n";
echo "- sitemap.xml (master sitemap)\n";
foreach ($result['sitemapFiles'] as $file) {
    $fileSize = round(filesize($file) / 1024, 2);
    echo "- {$file} ({$fileSize} KB)\n";
}

echo "\nSitemap URLs:\n";
echo "- Master sitemap: {$baseUrl}sitemap.xml\n";
foreach ($result['sitemapFiles'] as $file) {
    echo "- {$baseUrl}{$file}\n";
}

echo "\nTo submit to search engines:\n";
echo "- Google: https://search.google.com/search-console\n";
echo "- Bing: https://www.bing.com/webmasters\n";
echo "- Submit this URL: {$baseUrl}sitemap.xml\n";

?>