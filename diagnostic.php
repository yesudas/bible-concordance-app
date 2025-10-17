<?php
// Diagnostic script to check Numbers data availability
header('Content-Type: text/plain; charset=utf-8');

echo "Bible Concordance - Numbers Data Diagnostic\n";
echo "==========================================\n\n";

$language = 'தமிழ்';
$bible = 'TERV1998';
$letter = 'Numbers';
$word = '100000';

echo "Testing data for:\n";
echo "Language: $language\n";
echo "Bible: $bible\n";
echo "Letter: $letter\n";
echo "Word: $word\n\n";

// Check if data directory exists
$dataDir = __DIR__ . '/data';
echo "1. Data directory check:\n";
echo "   Path: $dataDir\n";
echo "   Exists: " . (is_dir($dataDir) ? "YES" : "NO") . "\n";
if (is_dir($dataDir)) {
    echo "   Readable: " . (is_readable($dataDir) ? "YES" : "NO") . "\n";
    $dirs = scandir($dataDir);
    echo "   Contents: " . implode(', ', array_filter($dirs, function($d) { return $d !== '.' && $d !== '..'; })) . "\n";
}
echo "\n";

// Check if language directory exists
$langDir = $dataDir . '/' . $language;
echo "2. Language directory check:\n";
echo "   Path: $langDir\n";
echo "   Exists: " . (is_dir($langDir) ? "YES" : "NO") . "\n";
if (is_dir($langDir)) {
    echo "   Readable: " . (is_readable($langDir) ? "YES" : "NO") . "\n";
    $dirs = scandir($langDir);
    echo "   Contents: " . implode(', ', array_filter($dirs, function($d) { return $d !== '.' && $d !== '..'; })) . "\n";
}
echo "\n";

// Check if bible directory exists
$bibleDir = $langDir . '/' . $bible;
echo "3. Bible directory check:\n";
echo "   Path: $bibleDir\n";
echo "   Exists: " . (is_dir($bibleDir) ? "YES" : "NO") . "\n";
if (is_dir($bibleDir)) {
    echo "   Readable: " . (is_readable($bibleDir) ? "YES" : "NO") . "\n";
    $dirs = scandir($bibleDir);
    echo "   Contents: " . implode(', ', array_filter($dirs, function($d) { return $d !== '.' && $d !== '..'; })) . "\n";
}
echo "\n";

// Check if letters directory exists
$lettersDir = $bibleDir . '/letters';
echo "4. Letters directory check:\n";
echo "   Path: $lettersDir\n";
echo "   Exists: " . (is_dir($lettersDir) ? "YES" : "NO") . "\n";
if (is_dir($lettersDir)) {
    echo "   Readable: " . (is_readable($lettersDir) ? "YES" : "NO") . "\n";
    $files = scandir($lettersDir);
    $jsonFiles = array_filter($files, function($f) { return strpos($f, '.json') !== false; });
    echo "   JSON files count: " . count($jsonFiles) . "\n";
    echo "   Numbers.json exists: " . (file_exists($lettersDir . '/Numbers.json') ? "YES" : "NO") . "\n";
}
echo "\n";

// Check Numbers.json content
$numbersFile = $lettersDir . '/Numbers.json';
echo "5. Numbers.json file check:\n";
echo "   Path: $numbersFile\n";
echo "   Exists: " . (file_exists($numbersFile) ? "YES" : "NO") . "\n";
if (file_exists($numbersFile)) {
    echo "   Readable: " . (is_readable($numbersFile) ? "YES" : "NO") . "\n";
    echo "   File size: " . filesize($numbersFile) . " bytes\n";
    
    $content = file_get_contents($numbersFile);
    if ($content !== false) {
        $data = json_decode($content, true);
        if ($data) {
            echo "   JSON valid: YES\n";
            echo "   Words count: " . (isset($data['words']) ? count($data['words']) : 0) . "\n";
            
            // Check if 100000 word exists
            $found100000 = false;
            if (isset($data['words'])) {
                foreach ($data['words'] as $wordItem) {
                    if ($wordItem['word'] === '100000') {
                        $found100000 = true;
                        echo "   Word '100000' found: YES\n";
                        echo "   File for 100000: " . $wordItem['file'] . "\n";
                        echo "   Verses count: " . $wordItem['versesCount'] . "\n";
                        break;
                    }
                }
            }
            if (!$found100000) {
                echo "   Word '100000' found: NO\n";
            }
        } else {
            echo "   JSON valid: NO\n";
            echo "   JSON error: " . json_last_error_msg() . "\n";
        }
    } else {
        echo "   Cannot read file content\n";
    }
}
echo "\n";

// Check words directory
$wordsDir = $bibleDir . '/words';
echo "6. Words directory check:\n";
echo "   Path: $wordsDir\n";
echo "   Exists: " . (is_dir($wordsDir) ? "YES" : "NO") . "\n";
if (is_dir($wordsDir)) {
    echo "   Readable: " . (is_readable($wordsDir) ? "YES" : "NO") . "\n";
    $dirs = scandir($wordsDir);
    $subdirs = array_filter($dirs, function($d) use ($wordsDir) { 
        return $d !== '.' && $d !== '..' && is_dir($wordsDir . '/' . $d); 
    });
    echo "   Subdirectories: " . implode(', ', $subdirs) . "\n";
    echo "   Numbers subdirectory exists: " . (is_dir($wordsDir . '/Numbers') ? "YES" : "NO") . "\n";
}
echo "\n";

// Check Numbers word directory
$numbersWordsDir = $wordsDir . '/Numbers';
echo "7. Numbers words directory check:\n";
echo "   Path: $numbersWordsDir\n";
echo "   Exists: " . (is_dir($numbersWordsDir) ? "YES" : "NO") . "\n";
if (is_dir($numbersWordsDir)) {
    echo "   Readable: " . (is_readable($numbersWordsDir) ? "YES" : "NO") . "\n";
    $files = scandir($numbersWordsDir);
    $jsonFiles = array_filter($files, function($f) { return strpos($f, '.json') !== false; });
    echo "   JSON files count: " . count($jsonFiles) . "\n";
    echo "   Numbers-100000.json exists: " . (file_exists($numbersWordsDir . '/Numbers-100000.json') ? "YES" : "NO") . "\n";
}
echo "\n";

// Check specific word file
$wordFile = $numbersWordsDir . '/Numbers-100000.json';
echo "8. Specific word file check:\n";
echo "   Path: $wordFile\n";
echo "   Exists: " . (file_exists($wordFile) ? "YES" : "NO") . "\n";
if (file_exists($wordFile)) {
    echo "   Readable: " . (is_readable($wordFile) ? "YES" : "NO") . "\n";
    echo "   File size: " . filesize($wordFile) . " bytes\n";
    
    $content = file_get_contents($wordFile);
    if ($content !== false) {
        $data = json_decode($content, true);
        if ($data) {
            echo "   JSON valid: YES\n";
            echo "   Word: " . ($data['word'] ?? 'N/A') . "\n";
            echo "   Verses count: " . (isset($data['verses']) ? count($data['verses']) : 0) . "\n";
            if (isset($data['verses']) && count($data['verses']) > 0) {
                echo "   First verse reference: " . ($data['verses'][0]['reference'] ?? 'N/A') . "\n";
            }
        } else {
            echo "   JSON valid: NO\n";
            echo "   JSON error: " . json_last_error_msg() . "\n";
        }
    } else {
        echo "   Cannot read file content\n";
    }
}
echo "\n";

// PHP environment info
echo "9. PHP Environment:\n";
echo "   PHP Version: " . phpversion() . "\n";
echo "   Current working directory: " . getcwd() . "\n";
echo "   Script directory: " . __DIR__ . "\n";
echo "   File system case sensitive: " . (file_exists(__DIR__ . '/INDEX.php') === file_exists(__DIR__ . '/index.php') ? "NO" : "YES") . "\n";

echo "\n";
echo "Diagnostic complete.\n";
?>