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
    const listId = type === 'letters' ? 'lettersList' : 'wordsList';
    const msgId = type + 'NoResults';
    const itemType = type === 'letters' ? 'letters' : 'words';
    
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
  
  if (zoomInBtn) {
    zoomInBtn.addEventListener('click', () => {
      updateZoom(currentZoom + zoomStep);
    });
  }
  
  if (zoomOutBtn) {
    zoomOutBtn.addEventListener('click', () => {
      updateZoom(currentZoom - zoomStep);
    });
  }
  
  if (zoomResetBtn) {
    zoomResetBtn.addEventListener('click', () => {
      updateZoom(16); // Reset to default 16px
    });
  }

  // Add event listeners for search inputs
  const letterSearch = document.getElementById('letterSearch');
  const wordSearch = document.getElementById('wordSearch');
  
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
});
