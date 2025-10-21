<?php
// Missing Verses Log Viewer
$version = "2025.07";



// Handle delete actions
$message = '';
$messageType = '';

// Handle POST requests
if (!empty($_POST) && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'delete_entry') {
        $deleteId = $_POST['delete_id'] ?? '';
        if ($deleteId && deleteLogEntry($deleteId)) {
            $message = 'Log entry deleted successfully.';
            $messageType = 'success';
        } else {
            $message = 'Failed to delete log entry. ID: ' . $deleteId;
            $messageType = 'danger';
        }
    } elseif ($action === 'delete_all') {
        if (deleteAllLogEntries()) {
            $message = 'All log entries deleted successfully.';
            $messageType = 'success';
        } else {
            $message = 'Failed to delete all log entries.';
            $messageType = 'danger';
        }
    } elseif ($action === 'delete_filtered') {
        $idsToDelete = $_POST['delete_ids'] ?? [];
        if (!empty($idsToDelete)) {
            $deletedCount = deleteFilteredEntries($idsToDelete);
            if ($deletedCount > 0) {
                $message = "Successfully deleted {$deletedCount} filtered log " . ($deletedCount === 1 ? 'entry' : 'entries') . ".";
                $messageType = 'success';
            } else {
                $message = 'Failed to delete filtered entries.';
                $messageType = 'danger';
            }
        } else {
            $message = 'No entries selected for deletion.';
            $messageType = 'warning';
        }
    }
    
    // Redirect to avoid form resubmission on refresh
    if (!empty($message)) {
        $redirectUrl = 'log.php?';
        $params = [];
        if (!empty($_GET['filter_language'])) $params['filter_language'] = $_GET['filter_language'];
        if (!empty($_GET['filter_bible'])) $params['filter_bible'] = $_GET['filter_bible'];
        if (!empty($_GET['filter_word'])) $params['filter_word'] = $_GET['filter_word'];
        if (!empty($_GET['filter_user_agent'])) $params['filter_user_agent'] = $_GET['filter_user_agent'];
        if (!empty($_GET['sort'])) $params['sort'] = $_GET['sort'];
        if (!empty($_GET['order'])) $params['order'] = $_GET['order'];
        $params['msg'] = urlencode($message);
        $params['msg_type'] = $messageType;
        
        $redirectUrl .= http_build_query($params);
        header("Location: $redirectUrl");
        exit;
    }
}

// Get message from URL parameters (after redirect)
if (isset($_GET['msg'])) {
    $message = $_GET['msg'];
    $messageType = $_GET['msg_type'] ?? 'info';
}



// Delete functions
function deleteLogEntry($idToDelete) {
    $logFile = __DIR__ . '/logs/missing_verses.log';
    if (!file_exists($logFile)) return false;
    
    $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $newLines = [];
    $found = false;
    
    // Check if this is a temp ID (for entries without real IDs)
    $isTempId = strpos($idToDelete, 'temp_') === 0;
    
    foreach ($lines as $line) {
        $entry = json_decode($line, true);
        if ($entry) {
            if ($isTempId) {
                // For temp IDs, match by generating the same temp ID
                $tempId = 'temp_' . md5(json_encode($entry));
                if ($tempId === $idToDelete) {
                    $found = true;
                    continue; // Skip this line (delete it)
                }
            } else {
                // For real IDs, match by the ID field
                if (isset($entry['id']) && $entry['id'] === $idToDelete) {
                    $found = true;
                    continue; // Skip this line (delete it)
                }
            }
        }
        $newLines[] = $line;
    }
    
    if (!$found) return false;
    
    // Rewrite the file
    $content = implode("\n", $newLines) . (empty($newLines) ? '' : "\n");
    return file_put_contents($logFile, $content, LOCK_EX) !== false;
}

function deleteAllLogEntries() {
    $logFile = __DIR__ . '/logs/missing_verses.log';
    return file_put_contents($logFile, '', LOCK_EX) !== false;
}

function deleteFilteredEntries($idsToDelete) {
    $logFile = __DIR__ . '/logs/missing_verses.log';
    if (!file_exists($logFile)) return 0;
    
    $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $newLines = [];
    $deletedCount = 0;
    
    foreach ($lines as $line) {
        $entry = json_decode($line, true);
        if ($entry) {
            // Generate the entry ID (same logic as in the display)
            $entryId = $entry['id'] ?? 'temp_' . md5(json_encode($entry));
            
            // If this entry is in the delete list, skip it
            if (in_array($entryId, $idsToDelete)) {
                $deletedCount++;
                continue; // Skip this line (delete it)
            }
        }
        $newLines[] = $line;
    }
    
    if ($deletedCount === 0) return 0;
    
    // Rewrite the file
    $content = implode("\n", $newLines) . (empty($newLines) ? '' : "\n");
    if (file_put_contents($logFile, $content, LOCK_EX) !== false) {
        return $deletedCount;
    }
    
    return 0;
}

// Get filter parameters
$filterLanguage = $_GET['filter_language'] ?? '';
$filterBible = $_GET['filter_bible'] ?? '';
$filterWord = $_GET['filter_word'] ?? '';
$filterUserAgent = $_GET['filter_user_agent'] ?? '';
$sortBy = $_GET['sort'] ?? 'timestamp';
$sortOrder = $_GET['order'] ?? 'desc';

// Read log file
$logFile = __DIR__ . '/logs/missing_verses.log';
$logs = [];

if (file_exists($logFile)) {
    $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $entry = json_decode($line, true);
        if ($entry) {
            $logs[] = $entry;
        }
    }
}

// Apply filters
$filteredLogs = array_filter($logs, function($log) use ($filterLanguage, $filterBible, $filterWord, $filterUserAgent) {
    if ($filterLanguage && stripos($log['language'], $filterLanguage) === false) return false;
    if ($filterBible && stripos($log['bible'], $filterBible) === false) return false;
    if ($filterWord && stripos($log['word'], $filterWord) === false) return false;
    if ($filterUserAgent && stripos($log['user_agent'] ?? '', $filterUserAgent) === false) return false;
    return true;
});

// Sort logs
usort($filteredLogs, function($a, $b) use ($sortBy, $sortOrder) {
    $aVal = $a[$sortBy] ?? '';
    $bVal = $b[$sortBy] ?? '';
    
    $result = strcasecmp($aVal, $bVal);
    return $sortOrder === 'desc' ? -$result : $result;
});

// Get unique values for filter dropdowns
$languages = array_unique(array_column($logs, 'language'));
$bibles = array_unique(array_column($logs, 'bible'));
$userAgents = array_unique(array_column($logs, 'user_agent'));
sort($languages);
sort($bibles);
sort($userAgents);

function buildQueryString($params) {
    $current = $_GET;
    foreach ($params as $key => $value) {
        if ($value === '' || $value === null) {
            unset($current[$key]);
        } else {
            $current[$key] = $value;
        }
    }
    return empty($current) ? '' : '?' . http_build_query($current);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Missing Verses Log - Bible Concordance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/styles.css?v=<?php echo $version; ?>">
</head>
<body>
    <!-- Header -->
    <header class="bg-primary text-white">
        <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
            <div class="container">
                <a class="navbar-brand fw-bold" href="./">Bible Concordance</a>
                <span class="navbar-text">Missing Verses Log</span>
                <div class="ms-auto">
                    <button onclick="refreshPage()" class="btn btn-outline-light btn-sm me-2" title="Refresh Page">
                        <i class="bi bi-arrow-clockwise me-1"></i>Refresh
                    </button>
                    <a href="./" class="btn btn-light btn-sm">
                        <i class="bi bi-house me-1"></i>Back to Main App
                    </a>
                </div>
            </div>
        </nav>
    </header>

    <!-- Main Content -->
    <main class="container my-4">
        <!-- Message Display -->
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                <i class="bi bi-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-12">
                <h1 class="mb-4">
                    <i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>
                    Missing Verses Log
                </h1>
                
                <!-- Statistics -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h3 class="text-primary"><?php echo count($logs); ?></h3>
                                <small class="text-muted">Total Cases</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h3 class="text-success"><?php echo count($filteredLogs); ?></h3>
                                <small class="text-muted">Filtered Results</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h3 class="text-info"><?php echo count($languages); ?></h3>
                                <small class="text-muted">Languages</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h3 class="text-warning"><?php echo count($bibles); ?></h3>
                                <small class="text-muted">Bible Versions</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-funnel me-2"></i>Filters & Sorting
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label for="filter_language" class="form-label">Language</label>
                                <select class="form-select" id="filter_language" name="filter_language">
                                    <option value="">All Languages</option>
                                    <?php foreach ($languages as $lang): ?>
                                        <option value="<?php echo htmlspecialchars($lang); ?>" 
                                                <?php echo $filterLanguage === $lang ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($lang); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="filter_bible" class="form-label">Bible Version</label>
                                <select class="form-select" id="filter_bible" name="filter_bible">
                                    <option value="">All Bibles</option>
                                    <?php foreach ($bibles as $bible): ?>
                                        <option value="<?php echo htmlspecialchars($bible); ?>" 
                                                <?php echo $filterBible === $bible ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($bible); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="filter_word" class="form-label">Word (partial match)</label>
                                <input type="text" class="form-control" id="filter_word" name="filter_word" 
                                       value="<?php echo htmlspecialchars($filterWord); ?>" 
                                       placeholder="Enter word to search...">
                            </div>
                            <div class="col-md-2">
                                <label for="filter_user_agent" class="form-label">User Agent</label>
                                <select class="form-select" id="filter_user_agent" name="filter_user_agent">
                                    <option value="">All User Agents</option>
                                    <?php foreach ($userAgents as $ua): ?>
                                        <option value="<?php echo htmlspecialchars($ua); ?>" 
                                                <?php echo $filterUserAgent === $ua ? 'selected' : ''; ?>>
                                            <?php 
                                            // Display shortened version for better readability
                                            $displayUA = strlen($ua) > 30 ? substr($ua, 0, 30) . '...' : $ua;
                                            echo htmlspecialchars($displayUA); 
                                            ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="sort" class="form-label">Sort By</label>
                                <div class="input-group">
                                    <select class="form-select" id="sort" name="sort">
                                        <option value="timestamp" <?php echo $sortBy === 'timestamp' ? 'selected' : ''; ?>>Date</option>
                                        <option value="language" <?php echo $sortBy === 'language' ? 'selected' : ''; ?>>Language</option>
                                        <option value="bible" <?php echo $sortBy === 'bible' ? 'selected' : ''; ?>>Bible</option>
                                        <option value="word" <?php echo $sortBy === 'word' ? 'selected' : ''; ?>>Word</option>
                                    </select>
                                    <select class="form-select" name="order" style="max-width: 100px;">
                                        <option value="desc" <?php echo $sortOrder === 'desc' ? 'selected' : ''; ?>>↓ Desc</option>
                                        <option value="asc" <?php echo $sortOrder === 'asc' ? 'selected' : ''; ?>>↑ Asc</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-search me-1"></i>Apply Filters
                                </button>
                                <a href="log.php" class="btn btn-secondary">
                                    <i class="bi bi-arrow-clockwise me-1"></i>Clear Filters
                                </a>
                                <?php if (!empty($filteredLogs)): ?>
                                <button type="button" class="btn btn-warning ms-2" onclick="confirmDeleteFiltered()">
                                    <i class="bi bi-funnel-fill me-1"></i>Delete Filtered Entries (<?php echo count($filteredLogs); ?>)
                                </button>
                                <?php endif; ?>
                                <?php if (!empty($logs)): ?>
                                <button type="button" class="btn btn-danger ms-2" onclick="confirmDeleteAll()">
                                    <i class="bi bi-trash3 me-1"></i>Delete All Entries (<?php echo count($logs); ?>)
                                </button>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Results Table -->
                <?php if (empty($filteredLogs)): ?>
                    <div class="alert alert-info">
                        <h4 class="alert-heading">No records found</h4>
                        <p class="mb-0">
                            <?php if (empty($logs)): ?>
                                No missing verses have been logged yet.
                            <?php else: ?>
                                No records match your current filters. Try adjusting the filter criteria.
                            <?php endif; ?>
                        </p>
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="bi bi-table me-2"></i>Log Entries 
                                <span class="badge bg-primary"><?php echo count($filteredLogs); ?></span>
                            </h5>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>
                                            <a href="<?php echo buildQueryString(['sort' => 'timestamp', 'order' => $sortBy === 'timestamp' && $sortOrder === 'desc' ? 'asc' : 'desc']); ?>" 
                                               class="text-white text-decoration-none">
                                                Date/Time 
                                                <?php if ($sortBy === 'timestamp'): ?>
                                                    <i class="bi bi-chevron-<?php echo $sortOrder === 'desc' ? 'down' : 'up'; ?>"></i>
                                                <?php endif; ?>
                                            </a>
                                        </th>
                                        <th>
                                            <a href="<?php echo buildQueryString(['sort' => 'language', 'order' => $sortBy === 'language' && $sortOrder === 'desc' ? 'asc' : 'desc']); ?>" 
                                               class="text-white text-decoration-none">
                                                Language 
                                                <?php if ($sortBy === 'language'): ?>
                                                    <i class="bi bi-chevron-<?php echo $sortOrder === 'desc' ? 'down' : 'up'; ?>"></i>
                                                <?php endif; ?>
                                            </a>
                                        </th>
                                        <th>
                                            <a href="<?php echo buildQueryString(['sort' => 'bible', 'order' => $sortBy === 'bible' && $sortOrder === 'desc' ? 'asc' : 'desc']); ?>" 
                                               class="text-white text-decoration-none">
                                                Bible 
                                                <?php if ($sortBy === 'bible'): ?>
                                                    <i class="bi bi-chevron-<?php echo $sortOrder === 'desc' ? 'down' : 'up'; ?>"></i>
                                                <?php endif; ?>
                                            </a>
                                        </th>
                                        <th>
                                            <a href="<?php echo buildQueryString(['sort' => 'word', 'order' => $sortBy === 'word' && $sortOrder === 'desc' ? 'asc' : 'desc']); ?>" 
                                               class="text-white text-decoration-none">
                                                Word 
                                                <?php if ($sortBy === 'word'): ?>
                                                    <i class="bi bi-chevron-<?php echo $sortOrder === 'desc' ? 'down' : 'up'; ?>"></i>
                                                <?php endif; ?>
                                            </a>
                                        </th>
                                        <th>Letter</th>
                                        <th>User Agent</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($filteredLogs as $log): 
                                        // Generate entry ID once for consistency
                                        $entryId = $log['id'] ?? 'temp_' . md5(json_encode($log));
                                    ?>
                                    <tr>
                                        <td>
                                            <small class="text-muted font-monospace">
                                                <?php 
                                                // Display a shortened version of the ID for better readability
                                                echo substr($entryId, 0, 12) . '...';
                                                ?>
                                            </small>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <?php echo date('M j, Y', strtotime($log['timestamp'])); ?><br>
                                                <?php echo date('g:i A', strtotime($log['timestamp'])); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?php echo htmlspecialchars($log['language']); ?></span>
                                        </td>
                                        <td>
                                            <code class="text-primary"><?php echo htmlspecialchars($log['bible']); ?></code>
                                        </td>
                                        <td>
                                            <strong class="text-dark"><?php echo htmlspecialchars($log['word']); ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary"><?php echo htmlspecialchars($log['letter']); ?></span>
                                        </td>
                                        <td>
                                            <small class="text-muted" title="<?php echo htmlspecialchars($log['user_agent'] ?? 'Unknown'); ?>">
                                                <?php 
                                                $ua = $log['user_agent'] ?? 'Unknown';
                                                // Show shortened version with tooltip
                                                echo htmlspecialchars(strlen($ua) > 40 ? substr($ua, 0, 40) . '...' : $ua); 
                                                ?>
                                            </small>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="<?php echo htmlspecialchars($log['url']); ?>" 
                                                   class="btn btn-sm btn-outline-primary" target="_blank" title="Visit URL">
                                                    <i class="bi bi-box-arrow-up-right"></i>
                                                </a>
                                                <form method="post" style="display: inline;">
                                                    <input type="hidden" name="action" value="delete_entry">
                                                    <input type="hidden" name="delete_id" value="<?php echo htmlspecialchars($entryId); ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" 
                                                            onclick="return confirm('Are you sure you want to delete the log entry for word &quot;<?php echo htmlspecialchars($log['word']); ?>&quot;?<br/>This action cannot be undone.')"
                                                            title="Delete Entry (ID: <?php echo substr($entryId, 0, 8); ?>)">
                                                        <i class="bi bi-trash3"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-light text-center py-3 mt-5">
        <div class="container">
            <p class="mb-0 text-muted">
                <small>
                    <i class="bi bi-shield-check me-1"></i>
                    Missing Verses Analytics - Bible Concordance
                </small>
            </p>
        </div>
    </footer>

    <!-- Hidden Form for Delete All Action -->
    <form id="deleteAllForm" method="post" style="display: none;">
        <input type="hidden" name="action" value="delete_all">
    </form>

    <!-- Hidden Form for Delete Filtered Action -->
    <form id="deleteFilteredForm" method="post" style="display: none;">
        <input type="hidden" name="action" value="delete_filtered">
        <div id="filteredIdsContainer"></div>
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    // Refresh page without POST data
    function refreshPage() {
        // Build URL with current filters
        const params = new URLSearchParams();
        <?php if ($filterLanguage): ?>
        params.set('filter_language', '<?php echo addslashes($filterLanguage); ?>');
        <?php endif; ?>
        <?php if ($filterBible): ?>
        params.set('filter_bible', '<?php echo addslashes($filterBible); ?>');
        <?php endif; ?>
        <?php if ($filterWord): ?>
        params.set('filter_word', '<?php echo addslashes($filterWord); ?>');
        <?php endif; ?>
        <?php if ($filterUserAgent): ?>
        params.set('filter_user_agent', '<?php echo addslashes($filterUserAgent); ?>');
        <?php endif; ?>
        <?php if ($sortBy !== 'timestamp'): ?>
        params.set('sort', '<?php echo addslashes($sortBy); ?>');
        <?php endif; ?>
        <?php if ($sortOrder !== 'desc'): ?>
        params.set('order', '<?php echo addslashes($sortOrder); ?>');
        <?php endif; ?>
        
        const queryString = params.toString();
        window.location.href = 'log.php' + (queryString ? '?' + queryString : '');
    }
    
    // Array of filtered entry IDs
    const filteredEntryIds = <?php echo json_encode(array_map(function($log) {
        return $log['id'] ?? 'temp_' . md5(json_encode($log));
    }, $filteredLogs)); ?>;
    
    // Confirm delete all
    function confirmDeleteAll() {
        const totalEntries = <?php echo count($logs); ?>;
        if (confirm(`Are you sure you want to delete ALL ${totalEntries} log entries?\n\nThis action cannot be undone and will permanently remove all missing verses data.`)) {
            if (confirm('FINAL WARNING: This will delete ALL log data permanently. Continue?')) {
                document.getElementById('deleteAllForm').submit();
            }
        }
    }

    // Confirm delete filtered entries
    function confirmDeleteFiltered() {
        const filteredCount = filteredEntryIds.length;
        const totalCount = <?php echo count($logs); ?>;
        
        if (filteredCount === 0) {
            alert('No filtered entries to delete.');
            return;
        }
        
        const filterInfo = [];
        <?php if ($filterLanguage): ?>
        filterInfo.push('Language: <?php echo htmlspecialchars($filterLanguage); ?>');
        <?php endif; ?>
        <?php if ($filterBible): ?>
        filterInfo.push('Bible: <?php echo htmlspecialchars($filterBible); ?>');
        <?php endif; ?>
        <?php if ($filterWord): ?>
        filterInfo.push('Word: <?php echo htmlspecialchars($filterWord); ?>');
        <?php endif; ?>
        <?php if ($filterUserAgent): ?>
        filterInfo.push('User Agent: <?php echo htmlspecialchars(strlen($filterUserAgent) > 30 ? substr($filterUserAgent, 0, 30) . '...' : $filterUserAgent); ?>');
        <?php endif; ?>
        
        const filterText = filterInfo.length > 0 ? '\n\nActive Filters:\n' + filterInfo.join('\n') : '';
        
        if (confirm(`Are you sure you want to delete ${filteredCount} filtered log ${filteredCount === 1 ? 'entry' : 'entries'}?${filterText}\n\nThis will keep the remaining ${totalCount - filteredCount} entries.\nThis action cannot be undone.`)) {
            // Add all filtered IDs to the form
            const container = document.getElementById('filteredIdsContainer');
            container.innerHTML = '';
            filteredEntryIds.forEach(id => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'delete_ids[]';
                input.value = id;
                container.appendChild(input);
            });
            
            document.getElementById('deleteFilteredForm').submit();
        }
    }
    </script>
</body>
</html>