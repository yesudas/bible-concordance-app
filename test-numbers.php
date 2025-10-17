<?php
// Test script to replicate the exact issue
include 'counter.php';

$language = $_GET['lang'] ?? 'தமிழ்';
$bible = $_GET['bible'] ?? 'TERV1998';
$letter = $_GET['letter'] ?? 'Numbers';
$word = $_GET['word'] ?? '100000';

function getVerses($language, $bible, $letter, $word) {
    $verses = [];
    
    // First, get the exact filename from the letter's words data
    $letterFile = '';
    $possibleLetterPaths = [
        __DIR__ . '/data/' . $language . '/' . $bible . '/letters/' . $letter . '.json',
        __DIR__ . '/data/' . $language . '/' . $bible . '/letters/' . strtolower($letter) . '.json',
        __DIR__ . '/data/' . $language . '/' . $bible . '/letters/' . strtoupper($letter) . '.json'
    ];
    
    foreach ($possibleLetterPaths as $path) {
        if (file_exists($path)) {
            $letterFile = $path;
            break;
        }
    }
    
    if ($letterFile) {
        $letterData = json_decode(file_get_contents($letterFile), true);
        if (isset($letterData['words'])) {
            // Find the exact word and get its filename
            $exactFileName = '';
            foreach ($letterData['words'] as $wordItem) {
                if (strtolower($wordItem['word']) === strtolower($word)) {
                    $exactFileName = $wordItem['file'];
                    break;
                }
            }
            
            if ($exactFileName) {
                // Use the exact filename from the data
                // Try original case first, then lowercase, then uppercase
                $possibleWordPaths = [
                    __DIR__ . '/data/' . $language . '/' . $bible . '/words/' . $letter . '/' . $exactFileName,
                    __DIR__ . '/data/' . $language . '/' . $bible . '/words/' . strtolower($letter) . '/' . $exactFileName,
                    __DIR__ . '/data/' . $language . '/' . $bible . '/words/' . strtoupper($letter) . '/' . $exactFileName
                ];
                
                $wordFilePath = '';
                foreach ($possibleWordPaths as $path) {
                    if (file_exists($path)) {
                        $wordFilePath = $path;
                        break;
                    }
                }
                
                if ($wordFilePath && file_exists($wordFilePath)) {
                    $wordData = json_decode(file_get_contents($wordFilePath), true);
                    if (isset($wordData['verses'])) {
                        $verses = $wordData['verses'];
                    }
                }
            }
        }
    }
    
    return $verses;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Numbers Test - Bible Concordance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container my-4">
        <h1>Numbers Concordance Test</h1>
        
        <div class="card">
            <div class="card-header">
                <h5>Parameters</h5>
            </div>
            <div class="card-body">
                <p><strong>Language:</strong> <?php echo htmlspecialchars($language); ?></p>
                <p><strong>Bible:</strong> <?php echo htmlspecialchars($bible); ?></p>
                <p><strong>Letter:</strong> <?php echo htmlspecialchars($letter); ?></p>
                <p><strong>Word:</strong> <?php echo htmlspecialchars($word); ?></p>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h5>Path Tests</h5>
            </div>
            <div class="card-body">
                <?php
                $letterFile = __DIR__ . '/data/' . $language . '/' . $bible . '/letters/' . $letter . '.json';
                echo "<p><strong>Letter file path:</strong> " . htmlspecialchars($letterFile) . "</p>";
                echo "<p><strong>Letter file exists:</strong> " . (file_exists($letterFile) ? "YES" : "NO") . "</p>";
                
                if (file_exists($letterFile)) {
                    $letterData = json_decode(file_get_contents($letterFile), true);
                    if ($letterData && isset($letterData['words'])) {
                        $found = false;
                        foreach ($letterData['words'] as $wordItem) {
                            if ($wordItem['word'] === $word) {
                                $found = true;
                                echo "<p><strong>Word found in letter data:</strong> YES</p>";
                                echo "<p><strong>Word file:</strong> " . htmlspecialchars($wordItem['file']) . "</p>";
                                
                                // Show all possible paths being tried
                                $possibleWordPaths = [
                                    __DIR__ . '/data/' . $language . '/' . $bible . '/words/' . $letter . '/' . $wordItem['file'],
                                    __DIR__ . '/data/' . $language . '/' . $bible . '/words/' . strtolower($letter) . '/' . $wordItem['file'],
                                    __DIR__ . '/data/' . $language . '/' . $bible . '/words/' . strtoupper($letter) . '/' . $wordItem['file']
                                ];
                                
                                echo "<p><strong>Trying paths:</strong></p>";
                                echo "<ol>";
                                foreach ($possibleWordPaths as $i => $path) {
                                    $exists = file_exists($path) ? "EXISTS" : "NOT FOUND";
                                    echo "<li>" . htmlspecialchars($path) . " - <strong>$exists</strong></li>";
                                }
                                echo "</ol>";
                                break;
                            }
                        }
                        if (!$found) {
                            echo "<p><strong>Word found in letter data:</strong> NO</p>";
                        }
                    }
                }
                ?>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h5>Verses Result</h5>
            </div>
            <div class="card-body">
                <?php
                $verses = getVerses($language, $bible, $letter, $word);
                if (!empty($verses)) {
                    echo "<p><strong>Found " . count($verses) . " verses:</strong></p>";
                    echo "<ol>";
                    foreach ($verses as $verse) {
                        echo "<li>" . htmlspecialchars($verse['verse']) . " - <strong>" . htmlspecialchars($verse['reference']) . "</strong></li>";
                    }
                    echo "</ol>";
                } else {
                    echo "<p class='text-danger'><strong>No verses found!</strong></p>";
                    echo "<p>This indicates the issue with the Numbers concordance.</p>";
                }
                ?>
            </div>
        </div>
        
        <div class="mt-3">
            <a href="index.php?lang=<?php echo urlencode($language); ?>&bible=<?php echo urlencode($bible); ?>&letter=<?php echo urlencode($letter); ?>&word=<?php echo urlencode($word); ?>" class="btn btn-primary">Test with Main App</a>
            <a href="diagnostic.php" class="btn btn-secondary">View Full Diagnostic</a>
        </div>
    </div>
</body>
</html>