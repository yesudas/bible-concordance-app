<?php
// Bible Concordance Web Application
include 'counter.php';

$version = "2025.01";

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
            if ($dir !== '.' && $dir !== '..' && is_dir($dataDir . '/' . $dir)) {
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
            if ($dir !== '.' && $dir !== '..' && is_dir($dataDir . '/' . $dir)) {
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
            if ($dir !== '.' && $dir !== '..' && is_dir($langDir . '/' . $dir)) {
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
                $wordFilePath = __DIR__ . '/data/' . $language . '/' . $bible . '/words/' . strtolower($letter) . '/' . $exactFileName;
                
                // Try both lowercase and uppercase letter directory
                if (!file_exists($wordFilePath)) {
                    $wordFilePath = __DIR__ . '/data/' . $language . '/' . $bible . '/words/' . strtoupper($letter) . '/' . $exactFileName;
                }
                
                if (file_exists($wordFilePath)) {
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
    $url = 'index.php';
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
    
    // For Tamil text, we need to handle Unicode properly
    // Tamil doesn't use traditional word boundaries, so we'll use a more flexible approach
    // Match the word with optional 's' suffix for English and handle Tamil characters
    if (preg_match('/[\x{0B80}-\x{0BFF}]/u', $word)) {
        // Tamil Unicode range - use simple text matching without word boundaries
        $pattern = '/(' . $escapedWord . ')/ui';
    } else {
        // English text - use word boundaries with optional 's'
        $pattern = '/\b(' . $escapedWord . 's?)\b/i';
    }
    
    // First escape HTML, then apply highlighting
    $escapedText = htmlspecialchars($text);
    $highlightedText = preg_replace($pattern, '<span style="color: deeppink; font-weight: bold;">$1</span>', $escapedText);
    
    return $highlightedText;
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
</head>
<body>
    <!-- Header -->
    <header class="bg-primary text-white">
        <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
            <div class="container">
                <a class="navbar-brand fw-bold" href="index.php">Bible Concordance</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="index.php">Home</a>
                        </li>
                    <li class="nav-item">
                            <a class="nav-link" href="https://wordofgod.in/good-news-collections/" target="_blank">Good News Collections</a> </li>
                    <li class="nav-item">
                            <a class="nav-link" href="https://wordofgod.in/bibledictionary/" target="_blank">Bible Dictionaries</a> </li>
                    <li class="nav-item">
                            <a class="nav-link" href="https://wordofgod.in/bible-wallpapers/" target="_blank">Bible Wallpapers</a></li>
                    <li class="nav-item">
                            <a class="nav-link" href="https://wordofgod.in/bible-app-modules/" target="_blank">Bible App Modules</a></li>
                    <li class="nav-item">
                            <a class="nav-link" href="https://wordofgod.in/" target="_blank">Free Christian Resources</a></li>
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
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
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
                    <button id="installAppBtn" class="btn btn-primary top-button"> <i class="bi bi-phone"></i> Install as App</button>
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
                    <div class="row">
                        <?php foreach ($pageData['bibles'] as $bibleItem): ?>
                        <div class="col-md-6 col-lg-4 mb-3">
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
                        <?php foreach ($pageData['letters'] as $letterItem): ?>
                        <div class="col-md-6 col-lg-3 mb-3">
                            <div class="card card-clickable h-100" onclick="window.location.href='<?php echo buildUrl(['lang' => $language, 'bible' => $bible, 'letter' => $letterItem['letter']]); ?>'">
                                <div class="card-body text-center">
                                    <h5 class="card-title"><?php echo htmlspecialchars($letterItem['letter']); ?></h5>
                                    <p class="card-text text-muted"><?php echo number_format($letterItem['wordsCount']); ?> words</p>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

        <?php elseif (!$word): ?>
            <!-- Words View -->
            <div class="row">
                <div class="col-12">
                    <h1 class="mb-4">Words starting with "<?php echo htmlspecialchars($letter); ?>"</h1>
                    <div class="row">
                        <?php foreach ($pageData['words'] as $wordItem): ?>
                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="card card-clickable h-100" onclick="window.location.href='<?php echo buildUrl(['lang' => $language, 'bible' => $bible, 'letter' => $letter, 'word' => $wordItem['word']]); ?>'">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($wordItem['word']); ?></h5>
                                    <p class="card-text text-muted"><?php echo $wordItem['versesCount']; ?> verses</p>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
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
                                <ol class="list-unstyled mb-0">
                                    <?php foreach ($pageData['verses'] as $index => $verseItem): ?>
                                    <li class="mb-2">
                                        <span class="fw-bold text-primary"><?php echo $index + 1; ?>.</span> 
                                        <?php echo highlightWord($verseItem['verse'], $word); ?>
                                        <?php if (isset($verseItem['reference']) && !empty($verseItem['reference'])): ?>
                                            - <span class="text-primary fw-bold"><?php echo htmlspecialchars($verseItem['reference']); ?></span>
                                        <?php endif; ?>
                                    </li>
                                    <?php endforeach; ?>
                                </ol>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <h4 class="alert-heading">No verses found</h4>
                            <p>There are no verses available for this word in the selected Bible.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <!-- Footer -->
    <footer class="bg-light text-center py-4 mt-5">
        <div class="container">
            <p class="mb-0 text-muted">No Copyright, Freely Copy and Distribute (as per Matthew 10:8)</p>
            <p class="mb-0 text-muted">
                <a href="https://wordofgod.in/good-news-collections/" target="_blank" class="text-decoration-none">Good News Collections</a> | 
                <a href="https://wordofgod.in/bibledictionary/" target="_blank" class="text-decoration-none">Bible Dictionaries</a> | 
                <a href="https://wordofgod.in/bible-wallpapers/" target="_blank" class="text-decoration-none">Bible Wallpapers</a> | 
                <a href="https://wordofgod.in/bible-app-modules/" target="_blank" class="text-decoration-none">Bible App Modules</a> | 
                <a href="https://wordofgod.in" target="_blank" class="text-decoration-none">Free Christian Resources</a> | 
                Visitors: <?= $visitors2 ?>
            </p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>