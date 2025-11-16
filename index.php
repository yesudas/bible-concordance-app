<?php
// Bible Concordance Web Application
include 'counter.php';

$version = "2025.08";


// Get URL parameters
$language = $_GET['lang'] ?? '';
$bible = $_GET['bible'] ?? '';
$letter = $_GET['letter'] ?? '';
$word = $_GET['word'] ?? '';

// Helper functions
function getLanguages() {
    $languages = [];
    $dataDir = __DIR__ . '/data';
    if (is_dir($dataDir)) {
        $dirs = scandir($dataDir);
        foreach ($dirs as $dir) {
            if ($dir !== '.' && $dir !== '..' && is_dir($dataDir . '/' . $dir) && !str_starts_with($dir, 'HIDE-')) {
                $languages[] = $dir;
            }
        }
    }
    return $languages;
}

function getLanguagesWithBibleCount() {
    $languagesWithCount = [];
    $dataDir = __DIR__ . '/data';
    if (is_dir($dataDir)) {
        $dirs = scandir($dataDir);
        foreach ($dirs as $dir) {
            if ($dir !== '.' && $dir !== '..' && is_dir($dataDir . '/' . $dir) && !str_starts_with($dir, 'HIDE-')) {
                $bibleCount = count(getBibles($dir));
                $languagesWithCount[] = [
                    'name' => $dir,
                    'bibleCount' => $bibleCount
                ];
            }
        }
    }
    return $languagesWithCount;
}

function getBibles($language) {
    $bibles = [];
    $langDir = __DIR__ . '/data/' . $language;
    if (is_dir($langDir)) {
        $dirs = scandir($langDir);
        foreach ($dirs as $dir) {
            if ($dir !== '.' && $dir !== '..' && is_dir($langDir . '/' . $dir) && !str_starts_with($dir, 'HIDE-')) {
                $concordanceFile = $langDir . '/' . $dir . '/Concordance.json';
                if (file_exists($concordanceFile)) {
                    $concordanceData = json_decode(file_get_contents($concordanceFile), true);
                    $bibles[] = [
                        'id' => $dir,
                        'name' => $concordanceData['bibleInfo']['commonName'] ?? $dir,
                        'totalUniqueWords' => $concordanceData['bibleInfo']['totalUniqueWords'] ?? 0,
                        'totalReferences' => $concordanceData['bibleInfo']['totalReferences'] ?? 0
                    ];
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
            $letters = $concordanceData['letters'];
            
            // Remove duplicates based on case-insensitive comparison
            // This fixes the issue where Linux shows both uppercase and lowercase letters
            // Prefer uppercase letters when available
            $uniqueLetters = [];
            $seenLetters = [];
            
            foreach ($letters as $letterItem) {
                $letterLower = strtolower($letterItem['letter']);
                if (!in_array($letterLower, $seenLetters)) {
                    $uniqueLetters[] = $letterItem;
                    $seenLetters[] = $letterLower;
                } else {
                    // If we've seen this letter before, check if current one is uppercase
                    // and replace the previous one if it was lowercase
                    $existingIndex = array_search($letterLower, $seenLetters);
                    if ($existingIndex !== false && ctype_upper($letterItem['letter'][0]) && ctype_lower($uniqueLetters[$existingIndex]['letter'][0])) {
                        $uniqueLetters[$existingIndex] = $letterItem;
                    }
                }
            }
            
            $letters = $uniqueLetters;
        }
    }
    return $letters;
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
            $words = $letterData['words'];
            
            // Remove duplicates based on case-insensitive comparison
            // This fixes the issue where Linux shows both uppercase and lowercase words
            // Prefer uppercase words when available (for proper names, etc.)
            $uniqueWords = [];
            $seenWords = [];
            
            foreach ($words as $wordItem) {
                $wordLower = strtolower($wordItem['word']);
                if (!in_array($wordLower, $seenWords)) {
                    $uniqueWords[] = $wordItem;
                    $seenWords[] = $wordLower;
                } else {
                    // If we've seen this word before, check if current one starts with uppercase
                    // and replace the previous one if it was lowercase (for proper names)
                    $existingIndex = array_search($wordLower, $seenWords);
                    if ($existingIndex !== false && ctype_upper($wordItem['word'][0]) && ctype_lower($uniqueWords[$existingIndex]['word'][0])) {
                        $uniqueWords[$existingIndex] = $wordItem;
                    }
                }
            }
            
            $words = $uniqueWords;
        }
    }
    return $words;
}

function getVerses($language, $bible, $letter, $word) {
    $verses = [];
    
    // First, get the exact filename from the letter's words data
    // This is much more reliable than guessing the file path
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

function buildUrl($params = []) {
    // For home page, use clean URL
    if (empty($params)) {
        return './';
    }
    
    $url = './';
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

function highlightWord($text, $word) {
    if (empty($word)) return htmlspecialchars($text);
    
    // Escape the word for regex and make it case-insensitive
    $escapedWord = preg_quote($word, '/');
    
    // Check for various scripts that don't use traditional word boundaries
    // Tamil: U+0B80-U+0BFF
    // Malayalam: U+0D00-U+0D7F
    // Devanagari (Hindi): U+0900-U+097F
    // Telugu: U+0C00-U+0C7F
    // Kannada: U+0C80-U+0CFF
    // Bengali: U+0980-U+09FF
    // Gujarati: U+0A80-U+0AFF
    // Odia: U+0B00-U+0B7F
    // Punjabi (Gurmukhi): U+0A00-U+0A7F
    // Sinhala: U+0D80-U+0DFF
    // Hebrew: U+0590-U+05FF (including Hebrew and Hebrew Extended blocks)
    // Greek: U+0370-U+03FF (including Greek and Coptic blocks)
    // Arabic: U+0600-U+06FF (Arabic block)
    if (preg_match('/[\x{0370}-\x{03FF}\x{0590}-\x{05FF}\x{0600}-\x{06FF}\x{0900}-\x{097F}\x{0980}-\x{09FF}\x{0A00}-\x{0A7F}\x{0A80}-\x{0AFF}\x{0B00}-\x{0B7F}\x{0B80}-\x{0BFF}\x{0C00}-\x{0C7F}\x{0C80}-\x{0CFF}\x{0D00}-\x{0D7F}\x{0D80}-\x{0DFF}]/u', $word)) {
        // Non-Latin scripts - use simple text matching without word boundaries
        $pattern = '/(' . $escapedWord . ')/ui';
    } else {
        // Latin/English text - use word boundaries with optional 's'
        $pattern = '/\b(' . $escapedWord . 's?)\b/i';
    }
    
    // First escape HTML, then apply highlighting
    $escapedText = htmlspecialchars($text);
    $highlightedText = preg_replace($pattern, '<span style="color: deeppink; font-weight: bold;">$1</span>', $escapedText);
    
    return $highlightedText;
}

function styleStrongNumbers($text) {
    // Style Strong's numbers (H123, G123 format) with blueviolet color and pointer cursor
    // This function is designed to work with already highlighted text
    
    // First, handle Strong's numbers that are outside any existing spans
    // Simple pattern to match Strong's numbers not already styled
    $pattern = '/\b([HG]\d+)\b/';
    $styledText = preg_replace($pattern, '<span style="color: blueviolet; font-size: 70%;">$1</span>', $text);
    
    // Then, handle Strong's numbers that might be inside highlighted word spans
    // We need to preserve the highlighting while adding Strong's number styling
    $styledText = preg_replace_callback(
        '/<span style="color: deeppink; font-weight: bold;">([^<]*?)<span style="color: blueviolet; font-size: 70%;">([HG]\d+)<\/span>([^<]*?)<\/span>/',
        function($matches) {
            $beforeStrong = $matches[1];
            $strongNumber = $matches[2];
            $afterStrong = $matches[3];
            
            // If the entire content is just the Strong's number, keep highlighting dominant
            if (empty(trim($beforeStrong)) && empty(trim($afterStrong))) {
                return '<span style="color: deeppink; font-weight: bold;">' . $strongNumber . '</span>';
            } else {
                // Mixed content: preserve word highlighting and add Strong's styling to the number only
                return '<span style="color: deeppink; font-weight: bold;">' . $beforeStrong . 
                       '<span style="color: blueviolet; font-size: 70%;">' . $strongNumber . '</span>' . 
                       $afterStrong . '</span>';
            }
        },
        $styledText
    );
    
    return $styledText;
}

function formatIndianNumber($number) {
    // Convert to string and reverse for easier processing
    $numStr = (string)$number;
    $reversed = strrev($numStr);
    $result = '';
    
    // First 3 digits
    $result .= substr($reversed, 0, 3);
    
    // Add comma and then every 2 digits
    for ($i = 3; $i < strlen($reversed); $i += 2) {
        if ($i < strlen($reversed)) {
            $result .= ',';
            $result .= substr($reversed, $i, 2);
        }
    }
    
    // Reverse back to get the correct order
    return strrev($result);
}

// Log missing verses for analytics
function logMissingVerse($language, $bible, $word, $letter) {
    $logFile = __DIR__ . '/logs/missing_verses.log';
    
    // Create logs directory if it doesn't exist
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    // Prepare log entry
    $currentUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . 
                  "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    $timestamp = date('Y-m-d H:i:s');
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    
    $logEntry = [
        'id' => uniqid('log_', true), // Unique identifier with timestamp precision
        'timestamp' => $timestamp,
        'language' => $language,
        'bible' => $bible,
        'word' => $word,
        'letter' => $letter,
        'url' => $currentUrl,
        'ip' => $ip,
        'user_agent' => $userAgent
    ];
    
    // Convert to JSON and append to log file
    $jsonEntry = json_encode($logEntry) . "\n";
    file_put_contents($logFile, $jsonEntry, FILE_APPEND | LOCK_EX);
}

// Prepare page data
$pageData = [
    'languages' => getLanguages(),
    'languagesWithCount' => getLanguagesWithBibleCount(),
    'bibles' => $language ? getBibles($language) : [],
    'letters' => ($language && $bible) ? getLetters($language, $bible) : [],
    'words' => ($language && $bible && $letter) ? getWords($language, $bible, $letter) : [],
    'verses' => ($language && $bible && $letter && $word) ? getVerses($language, $bible, $letter, $word) : []
];

// Build breadcrumb
$breadcrumb = [];
if ($language) {
    $breadcrumb[] = ['text' => $language, 'url' => buildUrl(['lang' => $language])];
    if ($bible) {
        $bibleData = array_filter($pageData['bibles'], function($b) use ($bible) { return $b['id'] === $bible; });
        $bibleName = !empty($bibleData) ? reset($bibleData)['name'] : $bible;
        $breadcrumb[] = ['text' => $bibleName, 'url' => buildUrl(['lang' => $language, 'bible' => $bible])];
        if ($letter) {
            $breadcrumb[] = ['text' => "Letter: $letter", 'url' => buildUrl(['lang' => $language, 'bible' => $bible, 'letter' => $letter])];
            if ($word) {
                $breadcrumb[] = ['text' => "Word: $word", 'url' => ''];
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/styles.css?v=<?php echo $version; ?>">
    <title>Bible Concordance<?php echo $word ? " - $word" : ($letter ? " - Letter $letter" : ($bible ? " - $bible" : ($language ? " - $language" : ""))); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="manifest.json?v=<?php echo $version; ?>">
    <meta name="theme-color" content="#2196f3">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Bible Concordance">

    <script async src="https://www.googletagmanager.com/gtag/js?id=G-8ZYHRZG9B8"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', 'G-8ZYHRZG9B8');
    </script>
    
</head>
<body>
    <!-- Header -->
    <header class="bg-primary text-white">
        <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
            <div class="container">
                <a class="navbar-brand fw-bold" href="./">Bible Concordance</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="./">Home</a>
                        </li>
                    <li class="nav-item">
                            <a class="nav-link" href="https://wordofgod.in/good-news-collections/" target="_blank"><i class="bi bi-box-seam me-1"></i>Good News Collections</a> </li>
                    <li class="nav-item">
                            <a class="nav-link" href="https://wordofgod.in/bibledictionary/" target="_blank"><i class="bi bi-collection me-1"></i>Bible Dictionaries</a> </li>
                    <li class="nav-item">
                            <a class="nav-link" href="https://wordofgod.in/bible-wallpapers/" target="_blank"><i class="bi bi-card-image me-1"></i>Bible Wallpapers</a></li>
                    <li class="nav-item">
                            <a class="nav-link" href="https://wordofgod.in/bible-app-modules/" target="_blank"><i class="bi bi-phone me-1"></i>Bible App Modules</a></li>
                    <li class="nav-item">
                            <a class="nav-link" href="https://wordofgod.in/" target="_blank"><i class="bi bi-gift me-1"></i>Free Christian Resources</a></li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <!-- Main Content -->
    <main class="container my-4">
        <!-- Breadcrumb -->
        <?php if (!empty($breadcrumb)): ?>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="./">Home</a></li>
                <?php foreach ($breadcrumb as $index => $crumb): ?>
                    <?php if ($crumb['url'] && $index < count($breadcrumb) - 1): ?>
                        <li class="breadcrumb-item"><a href="<?php echo htmlspecialchars($crumb['url']); ?>"><?php echo htmlspecialchars($crumb['text']); ?></a></li>
                    <?php else: ?>
                        <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($crumb['text']); ?></li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ol>
        </nav>
        <?php endif; ?>
            <div class="row">
                <div class="col-12">
                    <?php 
                    // Determine back URL based on current navigation level
                    $backUrl = '';
                    if ($word) {
                        // From word view, go back to letter view
                        $backUrl = buildUrl(['lang' => $language, 'bible' => $bible, 'letter' => $letter]);
                    } elseif ($letter) {
                        // From letter view, go back to bible view
                        $backUrl = buildUrl(['lang' => $language, 'bible' => $bible]);
                    } elseif ($bible) {
                        // From bible view, go back to language view
                        $backUrl = buildUrl(['lang' => $language]);
                    } elseif ($language) {
                        // From language view, go back to home
                        $backUrl = './';
                    }
                    ?>
                    <?php if ($backUrl): ?>
                        <button onclick="window.location.href='<?php echo htmlspecialchars($backUrl); ?>'" class="btn btn-secondary top-button me-2">
                            <i class="bi bi-arrow-left"></i> Back
                        </button>
                    <?php endif; ?>
                    <button id="installAppBtn" class="btn btn-primary top-button me-2"> <i class="bi bi-phone"></i> Install as App</button>
                    
                    <!-- Zoom Controls -->
                    <div class="btn-group top-button" role="group" aria-label="Zoom controls">
                        <button type="button" id="zoomOutBtn" class="btn btn-outline-secondary" title="Zoom Out">
                            <i class="bi bi-zoom-out"></i>
                        </button>
                        <button type="button" id="zoomResetBtn" class="btn btn-outline-secondary" title="Reset Zoom">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </button>
                        <button type="button" id="zoomInBtn" class="btn btn-outline-secondary" title="Zoom In">
                            <i class="bi bi-zoom-in"></i>
                        </button>
                    </div>
                </div>
            </div>

        <?php if (!$language): ?>
            <!-- Languages View -->
            <div class="row">
                <div class="col-12">
                    <h1 class="mb-4">Select a Language</h1>
                    <div class="row">
                        <?php foreach ($pageData['languagesWithCount'] as $langData): ?>
                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="card card-clickable h-100" onclick="window.location.href='<?php echo buildUrl(['lang' => $langData['name']]); ?>'">
                                <div class="card-body text-center">
                                    <h5 class="card-title"><?php echo htmlspecialchars($langData['name']); ?></h5>
                                    <p class="card-text text-muted">
                                        <?php echo $langData['bibleCount']; ?> Bible<?php echo $langData['bibleCount'] != 1 ? 's' : ''; ?> available
                                    </p>
                                    <small class="text-primary">Click to view Bibles</small>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

        <?php elseif (!$bible): ?>
            <!-- Bibles View -->
            <div class="row">
                <div class="col-12">
                    <h1 class="mb-4">Select a Bible - <?php echo htmlspecialchars($language); ?></h1>
                    
                    <!-- Search Box for Bibles -->
                    <div class="row mb-3">
                        <div class="col-md-8 col-lg-6">
                            <input type="text" id="bibleSearch" class="form-control" placeholder="Type here to search Bibles..."
                                   oninput="if(typeof filterBibles === 'function') filterBibles();" 
                                   onkeyup="if(typeof filterBibles === 'function') filterBibles();">
                            <small class="text-muted">Search by Bible name or abbreviation</small>
                        </div>
                    </div>
                    
                    <div class="row" id="biblesList">
                        <?php foreach ($pageData['bibles'] as $bibleItem): ?>
                        <div class="col-md-6 col-lg-4 mb-3 bible-item" 
                             data-bible-name="<?php echo strtolower(htmlspecialchars($bibleItem['name'])); ?>"
                             data-bible-id="<?php echo strtolower(htmlspecialchars($bibleItem['id'])); ?>">
                            <div class="card card-clickable h-100" onclick="window.location.href='<?php echo buildUrl(['lang' => $language, 'bible' => $bibleItem['id']]); ?>'">
                                <div class="card-body text-center">
                                    <h5 class="card-title"><?php echo htmlspecialchars($bibleItem['name']); ?></h5>
                                    <small class="text-primary">Click to view Letters</small>
                                    <hr>
                                    <div class="row text-center">
                                        <div class="col-6">
                                            <small class="text-muted d-block">Unique Words</small>
                                            <strong class="text-primary"><?php echo formatIndianNumber($bibleItem['totalUniqueWords']); ?></strong>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted d-block">Total References</small>
                                            <strong class="text-success"><?php echo formatIndianNumber($bibleItem['totalReferences']); ?></strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

        <?php elseif (!$letter): ?>
            <!-- Letters View -->
            <div class="row">
                <div class="col-12">
                    <h1 class="mb-4">Select a Letter</h1>
                    <div class="row">
                        <div class="col-md-8 col-lg-6">
                            <!-- Search Box for Letters -->
                            <div class="mb-3">
                                <input type="text" id="letterSearch" class="form-control" placeholder="Type here to search letters..." 
                                       oninput="if(typeof filterLetters === 'function') filterLetters();" 
                                       onkeyup="if(typeof filterLetters === 'function') filterLetters();">>
                                <small class="text-muted">Search by letter name</small>
                            </div>
                            
                            <div class="list-group" id="lettersList">
                                <?php foreach ($pageData['letters'] as $letterItem): ?>
                                <a href="<?php echo buildUrl(['lang' => $language, 'bible' => $bible, 'letter' => $letterItem['letter']]); ?>" 
                                   class="list-group-item list-group-item-action d-flex justify-content-between align-items-center letter-item"
                                   data-letter="<?php echo strtolower(htmlspecialchars($letterItem['letter'])); ?>">
                                    <span class="fw-bold text-primary">
                                        <?php echo htmlspecialchars($language === 'English' ? ucfirst($letterItem['letter']) : $letterItem['letter']); ?> 
                                        <span class="text-muted fw-normal">(<?php echo number_format($letterItem['wordsCount']); ?> verses)</span>
                                    </span>
                                    <i class="bi bi-chevron-right text-muted"></i>
                                </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        <?php elseif (!$word): ?>
            <!-- Words View -->
            <div class="row">
                <div class="col-12">
                    <h1 class="mb-4">Words starting with "<?php echo htmlspecialchars($letter); ?>"</h1>
                    <div class="row">
                        <div class="col-md-10 col-lg-8">
                            <!-- Search Box for Words -->
                            <div class="mb-3">
                                <input type="text" id="wordSearch" class="form-control" placeholder="Type here to search words..."
                                       oninput="if(typeof filterWords === 'function') filterWords();" 
                                       onkeyup="if(typeof filterWords === 'function') filterWords();">>
                                <small class="text-muted">Search by word name</small>
                            </div>
                            
                            <div class="list-group" id="wordsList">
                                <?php foreach ($pageData['words'] as $wordItem): ?>
                                <a href="<?php echo buildUrl(['lang' => $language, 'bible' => $bible, 'letter' => $letter, 'word' => $wordItem['word']]); ?>" 
                                   class="list-group-item list-group-item-action d-flex justify-content-between align-items-center word-item"
                                   data-word="<?php echo strtolower(htmlspecialchars($wordItem['word'])); ?>">
                                    <span class="fw-bold text-primary">
                                        <?php echo htmlspecialchars($language === 'English' ? ucfirst($wordItem['word']) : $wordItem['word']); ?> 
                                        <span class="text-muted fw-normal">(<?php echo $wordItem['versesCount']; ?> verses)</span>
                                    </span>
                                    <i class="bi bi-chevron-right text-muted"></i>
                                </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <!-- Verses View -->
            <div class="row">
                <div class="col-12">
                    <h1 class="mb-4">Verses for "<?php echo htmlspecialchars($word); ?>"</h1>
                    <?php if (!empty($pageData['verses'])): ?>
                        <div class="card">
                            <div class="card-body">
                                <!-- Copy to Clipboard Button -->
                                <div class="text-center mb-3">
                                    <button type="button" id="copyVersesBtn" class="btn btn-outline-primary btn-sm" title="Copy all verses to clipboard">
                                        <i class="bi bi-clipboard me-1"></i> Copy Verses to Clipboard
                                    </button>
                                </div>
                                <hr class="mb-3">
                                
                                <ol class="list-unstyled mb-0" id="versesList">
                                    <?php foreach ($pageData['verses'] as $index => $verseItem): ?>
                                    <li class="mb-2">
                                        <span class="fw-bold text-primary"><?php echo $index + 1; ?>.</span> 
                                        <?php echo styleStrongNumbers(highlightWord($verseItem['verse'], $word)); ?>
                                        <?php if (isset($verseItem['reference']) && !empty($verseItem['reference'])): ?>
                                            - <span class="text-primary fw-bold"><?php echo htmlspecialchars($verseItem['reference']); ?></span>
                                        <?php endif; ?>
                                    </li>
                                    <?php endforeach; ?>
                                </ol>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php
                        // Log this missing verse case
                        logMissingVerse($language, $bible, $word, $letter);
                        ?>
                        <div class="alert alert-info">
                            <h4 class="alert-heading">No verses found</h4>
                            <p>There are no verses available for this word in the selected Bible.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <?php
    // Show copyright notice for Bible versions (Letters, Words, and Verses views)
    if ($language && $bible && ($letter || $word) && false) {
        $copyrightFile = "copyright/{$language}/{$bible}.json";
        if (file_exists($copyrightFile)) {
            // Read copyright JSON data
            $copyrightData = json_decode(file_get_contents($copyrightFile), true);
            $bibleName = $copyrightData['bibleName'] ?? $bible;
            
            echo '<div class="container mb-4">';
            echo '<div class="alert alert-light border text-center">';
            echo '<small class="text-muted">';
            echo '<strong>' . htmlspecialchars($bibleName) . '</strong><br>';
            echo 'This Bible version is used with appropriate permissions.<br>';
            echo '<a href="#" onclick="showCopyright(\'' . htmlspecialchars($language) . '\', \'' . htmlspecialchars($bible) . '\')" class="text-primary text-decoration-none">View full copyright information</a>';
            echo '</small>';
            echo '</div>';
            echo '</div>';
        }
    }
    ?>

    <!-- Footer -->
    <footer class="bg-light text-center py-4 mt-5">
        <div class="container">
            <!-- Contact Section -->
            <div class="row mb-3">
                <div class="col-12">
                    <h6 class="text-primary mb-2"><i class="bi bi-envelope-heart me-1"></i>Contact Us</h6>
                    <p class="mb-1 text-muted">
                        <a href="mailto:wordofgod@wordofgod.in" class="text-decoration-none text-muted">
                            <i class="bi bi-envelope me-1"></i>wordofgod@wordofgod.in
                        </a>
                    </p>
                    <p class="mb-1 text-muted">
                        <span href="https://wa.me/917676505599" target="_blank" class="text-decoration-none text-success">
                            <i class="bi bi-whatsapp me-1"></i>+91 7676505599
                        </span>
                    </p>
                    <p class="mb-2 text-muted">
                        <a href="https://www.wordofgod.in" target="_blank" class="text-decoration-none text-primary">
                            <i class="bi bi-globe me-1"></i>www.WordOfGod.in
                        </a>
                    </p>
                </div>
            </div>
            
            <!-- Resources Links -->
            <p class="mb-2 text-muted">
                <a href="https://wordofgod.in/good-news-collections/" target="_blank" class="text-decoration-none"><i class="bi bi-box-seam me-1"></i>Good News Collections</a> | 
                <a href="https://wordofgod.in/bibledictionary/" target="_blank" class="text-decoration-none"><i class="bi bi-collection me-1"></i>Bible Dictionaries</a> | 
                <a href="https://wordofgod.in/bible-wallpapers/" target="_blank" class="text-decoration-none"><i class="bi bi-card-image me-1"></i>Bible Wallpapers</a> | 
                <a href="https://wordofgod.in/bible-app-modules/" target="_blank" class="text-decoration-none"><i class="bi bi-phone me-1"></i>Bible App Modules</a> | 
                <a href="https://wordofgod.in/" target="_blank" class="text-decoration-none"><i class="bi bi-gift me-1"></i>Free Christian Resources</a> | 
                <span class="text-primary"><i class="bi bi-emoji-heart-eyes me-1"></i>Visitors: <?= $visitors2 ?></span>
            </p>
                    
            <div style="position: absolute; left: -9999px; width: 1px; height: 1px; overflow: hidden; opacity: 0; pointer-events: none;" aria-hidden="true">
                <a href="./bot.php" tabindex="-1">.</a>
            </div>
            
            <!-- Copyright -->
            <p class="mb-0 text-muted">No Copyright, Freely Copy and Distribute (as per Matthew 10:8)</p>
        </div>
    </footer>

    <!-- Copyright Modal -->
    <div class="modal fade" id="copyrightModal" tabindex="-1" aria-labelledby="copyrightModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="copyrightModalLabel">
                        <i class="bi bi-shield-check me-2"></i>Copyright Information
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="copyrightContent">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 text-muted">Loading copyright information...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/script.js?v=<?php echo $version; ?>"></script>
</body>
</html>