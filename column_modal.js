document.addEventListener("DOMContentLoaded", function() {
    // --- VIEW TOGGLE LOGIC ---
    const toggleViewButton = document.getElementById("toggleViewButton");
    const graphsSection = document.getElementById("dashboardGraphs");
    const ipListSection = document.getElementById("ipListSection");

    // Use a variable to track the current view, defaulting to "graphs"
    let currentView = localStorage.getItem("currentView") || "graphs";

    // Set initial state based on currentView
    if (currentView === "ip") {
        if (graphsSection) graphsSection.style.display = "none";
        if (ipListSection) ipListSection.style.display = "block";
        if (toggleViewButton) toggleViewButton.textContent = "Show Graphs";
    } else {
        if (graphsSection) graphsSection.style.display = "block";
        if (ipListSection) ipListSection.style.display = "none";
        if (toggleViewButton) toggleViewButton.textContent = "Show IP List";
    }

    if (toggleViewButton) {
        toggleViewButton.addEventListener("click", function() {
            console.log("Current view before toggle:", currentView); // Debug log
            if (currentView === "graphs") {
                // Toggle to IP List view
                if (graphsSection) graphsSection.style.display = "none";
                if (ipListSection) ipListSection.style.display = "block";
                toggleViewButton.textContent = "Show Graphs";
                currentView = "ip";
                localStorage.setItem("currentView", currentView);
            } else {
                // Toggle to Graphs view and refresh page after 1 second
                if (ipListSection) ipListSection.style.display = "none";
                if (graphsSection) graphsSection.style.display = "block";
                toggleViewButton.textContent = "Show IP List";
                currentView = "graphs";
                localStorage.setItem("currentView", currentView);
                setTimeout(function() {
                    window.location.reload();
                }, 100);
            }
            console.log("Current view after toggle:", currentView); // Debug log
        });
    }

    // Preserve view state on pagination links.
    document.querySelectorAll('.pagination a').forEach(link => {
        link.addEventListener('click', function() {
            if (currentView === "ip") {
                localStorage.setItem("currentView", "ip");
            }
        });
    });

    // --- COLUMN MODAL & TABLE COLUMN TOGGLING ---
    const modal = document.getElementById("toggleColumnsModal");
    const toggleBtn = document.getElementById("toggleColumnsBtn");
    const closeBtn = document.getElementById("toggleClose");
    const table = document.getElementById("ipTable");

    if (toggleBtn) {
        toggleBtn.addEventListener("click", () => modal.classList.add("show"));
    }
    if (closeBtn) {
        closeBtn.addEventListener("click", () => modal.classList.remove("show"));
    }
    window.addEventListener("click", (e) => {
        if (e.target === modal) {
            modal.classList.remove("show");
        }
    });

    // Column toggling: restore saved visibility and update on change.
    document.querySelectorAll('.toggle-container input').forEach(checkbox => {
        const colIndex = parseInt(checkbox.dataset.col);
        checkbox.checked = localStorage.getItem(`col${colIndex}`) !== 'hidden';
        checkbox.addEventListener('change', () => {
            toggleColumn(colIndex, checkbox.checked);
            localStorage.setItem(`col${colIndex}`, checkbox.checked ? 'visible' : 'hidden');
        });
    });

    // Save/Restore functionality – force IP view state so reloads remain on the IP list.
    const saveBtn = document.getElementById('saveBtn');
    const restoreBtn = document.getElementById('restoreBtn');
    if (saveBtn) {
        saveBtn.addEventListener('click', () => {
            localStorage.setItem("currentView", "ip");
            const currentUrl = window.location.href.split('?')[0];
            const params = new URLSearchParams(window.location.search);
            params.set('t', new Date().getTime());
            window.location.href = currentUrl + '?' + params.toString();
        });
    }
    if (restoreBtn) {
        restoreBtn.addEventListener('click', () => {
            localStorage.clear();
            localStorage.setItem("currentView", "ip");
            const currentUrl = window.location.href.split('?')[0];
            const params = new URLSearchParams(window.location.search);
            params.set('t', new Date().getTime());
            window.location.href = currentUrl + '?' + params.toString();
        });
    }

    // Helper function to toggle column visibility.
    function toggleColumn(index, show) {
        table.querySelectorAll(`tr > :nth-child(${index + 1})`).forEach(cell => {
            cell.style.display = show ? '' : 'none';
        });
    }

    // Initialize column visibility on page load.
    document.querySelectorAll('.toggle-container input').forEach(checkbox => {
        const colIndex = parseInt(checkbox.dataset.col);
        toggleColumn(colIndex, checkbox.checked);
    });

    // Loading overlay handling for filter form submission.
    const filterForm = document.querySelector('.filter-form');
    if (filterForm) {
        filterForm.addEventListener('submit', function() {
            localStorage.setItem("currentView", "ip");
            document.getElementById('loadingOverlay').style.display = 'block';
        });
    }

    window.addEventListener('load', function() {
        document.getElementById('loadingOverlay').style.display = 'none';
    });
});
