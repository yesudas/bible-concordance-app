if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('./sw.js')
        .then(() => console.log("Service Worker Registered"))
        .catch(err => console.log("Service Worker Failed:", err));
}

// Search functionality for Letters View
function filterLetters() {
    const searchInput = document.getElementById('letterSearch');
    if (!searchInput) return;
    
    const filter = searchInput.value.toLowerCase().trim();
    const letterItems = document.querySelectorAll('.letter-item');
    
    let visibleCount = 0;
    letterItems.forEach(item => {
        const letterText = (item.getAttribute('data-letter') || '').toLowerCase();
        const itemText = item.textContent.toLowerCase();
        
        if (filter === '' || letterText.includes(filter) || itemText.includes(filter)) {
            // Show the item using multiple approaches
            item.style.setProperty('display', 'block', 'important');
            item.classList.remove('d-none', 'visually-hidden');
            item.classList.add('d-block');
            item.hidden = false;
            visibleCount++;
        } else {
            // Hide the item using multiple approaches
            item.style.setProperty('display', 'none', 'important');
            item.classList.add('d-none', 'visually-hidden');
            item.classList.remove('d-block');
            item.hidden = true;
        }
    });
    
    // Show "No results" message if no items are visible
    showNoResultsMessage('letters', visibleCount, filter);
}

// Search functionality for Bibles View
function filterBibles() {
    const searchInput = document.getElementById('bibleSearch');
    if (!searchInput) return;
    
    const filter = searchInput.value.toLowerCase().trim();
    const bibleItems = document.querySelectorAll('.bible-item');
    
    let visibleCount = 0;
    bibleItems.forEach(item => {
        const bibleName = (item.getAttribute('data-bible-name') || '').toLowerCase();
        const bibleId = (item.getAttribute('data-bible-id') || '').toLowerCase();
        const itemText = item.textContent.toLowerCase();
        
        if (filter === '' || bibleName.includes(filter) || bibleId.includes(filter) || itemText.includes(filter)) {
            // Show the item using multiple approaches
            item.style.setProperty('display', 'block', 'important');
            item.classList.remove('d-none', 'visually-hidden');
            item.classList.add('d-block');
            item.hidden = false;
            visibleCount++;
        } else {
            // Hide the item using multiple approaches
            item.style.setProperty('display', 'none', 'important');
            item.classList.add('d-none', 'visually-hidden');
            item.classList.remove('d-block');
            item.hidden = true;
        }
    });
    
    // Show "No results" message if no items are visible
    showNoResultsMessage('bibles', visibleCount, filter);
}

// Search functionality for Words View
function filterWords() {
    const searchInput = document.getElementById('wordSearch');
    if (!searchInput) return;
    
    const filter = searchInput.value.toLowerCase().trim();
    const wordItems = document.querySelectorAll('.word-item');
    
    let visibleCount = 0;
    wordItems.forEach(item => {
        const wordText = (item.getAttribute('data-word') || '').toLowerCase();
        const itemText = item.textContent.toLowerCase();
        
        if (filter === '' || wordText.includes(filter) || itemText.includes(filter)) {
            // Show the item using multiple approaches
            item.style.setProperty('display', 'block', 'important');
            item.classList.remove('d-none', 'visually-hidden');
            item.classList.add('d-block');
            item.hidden = false;
            visibleCount++;
        } else {
            // Hide the item using multiple approaches
            item.style.setProperty('display', 'none', 'important');
            item.classList.add('d-none', 'visually-hidden');
            item.classList.remove('d-block');
            item.hidden = true;
        }
    });
    
    // Show "No results" message if no items are visible
    showNoResultsMessage('words', visibleCount, filter);
}

// Helper function to show/hide no results message
function showNoResultsMessage(type, visibleCount, filter) {
    let listId, itemType;
    
    if (type === 'letters') {
        listId = 'lettersList';
        itemType = 'letters';
    } else if (type === 'words') {
        listId = 'wordsList';
        itemType = 'words';
    } else if (type === 'bibles') {
        listId = 'biblesList';
        itemType = 'Bibles';
    }
    
    const msgId = type + 'NoResults';
    const list = document.getElementById(listId);
    if (!list) return;
    
    let noResultsMsg = document.getElementById(msgId);
    
    if (visibleCount === 0 && filter !== '') {
        if (!noResultsMsg) {
            noResultsMsg = document.createElement('div');
            noResultsMsg.id = msgId;
            noResultsMsg.className = 'alert alert-info mt-3';
            noResultsMsg.innerHTML = `<i class="bi bi-search"></i> No ${itemType} found matching your search.`;
            list.parentNode.appendChild(noResultsMsg);
        }
        noResultsMsg.style.display = 'block';
    } else {
        if (noResultsMsg) {
            noResultsMsg.style.display = 'none';
        }
    }
}

// Zoom functionality
let currentZoom = 16; // Default font size in px
const minZoom = 12;
const maxZoom = 40;
const zoomStep = 2;

function updateZoom(newSize) {
    currentZoom = Math.max(minZoom, Math.min(maxZoom, newSize));
    document.body.style.fontSize = currentZoom + 'px';
    
    // Save zoom level to localStorage
    localStorage.setItem('bibleConcordanceZoom', currentZoom);
    
    // Update button states
    updateZoomButtons();
    
    // Debug log for mobile troubleshooting
    console.log('Zoom updated to:', currentZoom + 'px');
}

function updateZoomButtons() {
    const zoomInBtn = document.getElementById('zoomInBtn');
    const zoomOutBtn = document.getElementById('zoomOutBtn');
    
    if (zoomInBtn) {
        zoomInBtn.disabled = currentZoom >= maxZoom;
        zoomInBtn.title = currentZoom >= maxZoom ? 'Maximum zoom reached' : 'Zoom In';
    }
    
    if (zoomOutBtn) {
        zoomOutBtn.disabled = currentZoom <= minZoom;
        zoomOutBtn.title = currentZoom <= minZoom ? 'Minimum zoom reached' : 'Zoom Out';
    }
}

function initializeZoom() {
    // Load saved zoom level from localStorage
    const savedZoom = localStorage.getItem('bibleConcordanceZoom');
    if (savedZoom) {
        currentZoom = parseInt(savedZoom);
        updateZoom(currentZoom);
    } else {
        updateZoomButtons();
    }
}

document.addEventListener("DOMContentLoaded", () => {
  // Initialize zoom functionality
  initializeZoom();
  
  // Zoom control event listeners
  const zoomInBtn = document.getElementById('zoomInBtn');
  const zoomOutBtn = document.getElementById('zoomOutBtn');
  const zoomResetBtn = document.getElementById('zoomResetBtn');
  
  // Debug: Log if zoom buttons are found
  console.log('Zoom buttons found:', {
    zoomInBtn: !!zoomInBtn,
    zoomOutBtn: !!zoomOutBtn,
    zoomResetBtn: !!zoomResetBtn
  });
  
  // Helper function to handle zoom button events
  function handleZoomAction(action, actionName) {
    return (e) => {
      e.preventDefault();
      e.stopPropagation();
      console.log(`${actionName} triggered`);
      action();
    };
  }
  
  if (zoomInBtn) {
    // Add multiple event types for better mobile support
    ['click', 'touchstart'].forEach(eventType => {
      zoomInBtn.addEventListener(eventType, handleZoomAction(() => {
        updateZoom(currentZoom + zoomStep);
      }, 'Zoom In'), { passive: false });
    });
  }
  
  if (zoomOutBtn) {
    ['click', 'touchstart'].forEach(eventType => {
      zoomOutBtn.addEventListener(eventType, handleZoomAction(() => {
        updateZoom(currentZoom - zoomStep);
      }, 'Zoom Out'), { passive: false });
    });
  }
  
  if (zoomResetBtn) {
    ['click', 'touchstart'].forEach(eventType => {
      zoomResetBtn.addEventListener(eventType, handleZoomAction(() => {
        updateZoom(16); // Reset to default 16px
      }, 'Zoom Reset'), { passive: false });
    });
  }

  // Add event listeners for search inputs
  const bibleSearch = document.getElementById('bibleSearch');
  const letterSearch = document.getElementById('letterSearch');
  const wordSearch = document.getElementById('wordSearch');
  
  if (bibleSearch) {
    bibleSearch.addEventListener('input', filterBibles);
    bibleSearch.addEventListener('keyup', filterBibles);
  }
  
  if (letterSearch) {
    letterSearch.addEventListener('input', filterLetters);
    letterSearch.addEventListener('keyup', filterLetters);
  }
  
  if (wordSearch) {
    wordSearch.addEventListener('input', filterWords);
    wordSearch.addEventListener('keyup', filterWords);
  }
  
  // PWA functionality
  const installAppBtn = document.getElementById("installAppBtn");
  let deferredPrompt;

  // Hide install button initially
  if (installAppBtn) {
    installAppBtn.style.display = "none";
  }

  window.addEventListener("beforeinstallprompt", (e) => {
    console.log("beforeinstallprompt event fired");
    e.preventDefault();
    deferredPrompt = e;
    
    try {
        const urlParams = new URLSearchParams(window.location.search);
        // Show install button if not launched from installed app
        if (urlParams.get('f') !== 'app' && installAppBtn) {
            installAppBtn.style.display = "inline-block";
            console.log("Install button shown");
        }
    } catch (e) {
        console.error("Error managing install button visibility:", e);
    }
  });

  // Check if app is already installed
  window.addEventListener('appinstalled', () => {
    console.log('PWA was installed');
    if (installAppBtn) {
      installAppBtn.style.display = "none";
    }
  });

  if (installAppBtn) {
    installAppBtn.addEventListener("click", async () => {
      if (deferredPrompt) {
        deferredPrompt.prompt();
        const choice = await deferredPrompt.userChoice;
        console.log("User choice:", choice.outcome);
        if (choice.outcome === 'accepted') {
          installAppBtn.style.display = "none";
        }
        deferredPrompt = null;
      } else {
        console.log("No deferred prompt available");
      }
    });
  }

  // Copy verses button functionality
  const copyVersesBtn = document.getElementById('copyVersesBtn');
  if (copyVersesBtn) {
    copyVersesBtn.addEventListener('click', copyVersesToClipboard);
  }
});

// Copyright modal functionality
function showCopyright(language, bible) {
    const modal = new bootstrap.Modal(document.getElementById('copyrightModal'));
    const contentDiv = document.getElementById('copyrightContent');
    
    // Show loading spinner
    contentDiv.innerHTML = `
        <div class="text-center">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2 text-muted">Loading copyright information...</p>
        </div>
    `;
    
    modal.show();
    
    // Fetch copyright data
    fetch(`copyright/${language}/${bible}.json`)
        .then(response => response.json())
        .then(data => {
            let content = `
                <div class="text-center mb-4">
                    <h4 class="text-primary fw-bold">${data.bibleName}</h4>
                    <p class="text-muted mb-0">Language: <strong>${data.language}</strong></p>
                </div>
            `;
            
            if (data.copyright && Array.isArray(data.copyright)) {
                data.copyright.forEach((item, index) => {
                    content += `<p class="mb-3">${item.paragraph}</p>`;
                });
            } else {
                content += '<p class="text-muted">No copyright information available.</p>';
            }
            
            contentDiv.innerHTML = content;
        })
        .catch(error => {
            console.error('Error fetching copyright:', error);
            contentDiv.innerHTML = `
                <div class="alert alert-danger">
                    <h5 class="alert-heading"><i class="bi bi-exclamation-triangle me-2"></i>Error</h5>
                    <p class="mb-0">Could not load copyright information. Please try again later.</p>
                </div>
            `;
        });
}

// Copy verses to clipboard functionality
function copyVersesToClipboard() {
    const versesList = document.getElementById('versesList');
    if (!versesList) return;
    
    // Get current word and Bible version from page
    const urlParams = new URLSearchParams(window.location.search);
    const currentWord = urlParams.get('word') || '';
    const currentBible = urlParams.get('bible') || '';
    
    // Start with header
    let versesText = `Verses for "${currentWord}" from ${currentBible} bible\n\n`;
    
    const listItems = versesList.querySelectorAll('li');
    
    listItems.forEach((item, index) => {
        // Extract text content, removing HTML tags but preserving structure
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = item.innerHTML;
        
        // Remove the numbering span at the beginning
        const numberSpan = tempDiv.querySelector('.fw-bold.text-primary');
        if (numberSpan) {
            numberSpan.remove();
        }
        
        // Get the verse text and reference separately
        let verseText = '';
        let reference = '';
        
        // Look for reference span (it should be the last fw-bold text-primary span)
        const referenceSpans = tempDiv.querySelectorAll('.text-primary.fw-bold');
        if (referenceSpans.length > 0) {
            const referenceSpan = referenceSpans[referenceSpans.length - 1];
            reference = referenceSpan.textContent.trim();
            referenceSpan.remove();
        }
        
        // Get the remaining text (verse content) and clean up whitespace
        verseText = tempDiv.textContent.trim();
        
        // Remove leading dash if present
        if (verseText.startsWith('- ')) {
            verseText = verseText.substring(2).trim();
        }
        
        // Clean up excessive whitespace - replace multiple spaces/tabs/newlines with single space
        verseText = verseText.replace(/\s+/g, ' ').trim();
        
        // Format: "verse text (reference)"
        let formattedVerse = `${index + 1}. ${verseText}`;
        if (reference) {
            formattedVerse += ` (${reference})`;
        }
        
        versesText += `${formattedVerse}\n\n`;
    });
    
    // Add website link at the end
    versesText += `https://wordofgod.in/bible-concordance/`;
    
    // Copy to clipboard
    if (navigator.clipboard && window.isSecureContext) {
        // Modern clipboard API
        navigator.clipboard.writeText(versesText).then(() => {
            showCopyFeedback('success');
        }).catch(err => {
            console.error('Failed to copy verses: ', err);
            showCopyFeedback('error');
        });
    } else {
        // Fallback for older browsers
        const textArea = document.createElement('textarea');
        textArea.value = versesText;
        textArea.style.position = 'fixed';
        textArea.style.left = '-999999px';
        textArea.style.top = '-999999px';
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        
        try {
            document.execCommand('copy');
            showCopyFeedback('success');
        } catch (err) {
            console.error('Failed to copy verses: ', err);
            showCopyFeedback('error');
        } finally {
            textArea.remove();
        }
    }
}

// Show copy feedback to user
function showCopyFeedback(type) {
    const btn = document.getElementById('copyVersesBtn');
    if (!btn) return;
    
    const originalContent = btn.innerHTML;
    
    if (type === 'success') {
        btn.innerHTML = '<i class="bi bi-check-circle me-1"></i> Copied!';
        btn.className = 'btn btn-success btn-sm';
    } else {
        btn.innerHTML = '<i class="bi bi-exclamation-circle me-1"></i> Copy Failed';
        btn.className = 'btn btn-danger btn-sm';
    }
    
    // Reset button after 2 seconds
    setTimeout(() => {
        btn.innerHTML = originalContent;
        btn.className = 'btn btn-outline-primary btn-sm';
    }, 2000);
}
