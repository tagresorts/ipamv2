
document.addEventListener("DOMContentLoaded", function() {
    // --- VIEW TOGGLE LOGIC ---
    const toggleViewButton = document.getElementById("toggleViewButton");
    const graphsSection = document.getElementById("dashboardGraphs");
    const ipListSection = document.getElementById("ipListSection");

    // Check localStorage for current view; default to graphs.
    const currentView = localStorage.getItem("currentView") || "graphs";
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
            if (graphsSection.style.display === "block") {
                // Switching from Graphs view to IP List view (no reload needed)
                graphsSection.style.display = "none";
                ipListSection.style.display = "block";
                toggleViewButton.textContent = "Show Graphs";
                localStorage.setItem("currentView", "ip");
            } else {
                // Switching from IP List view to Graphs view: force full page reload
                localStorage.setItem("currentView", "graphs");
                // Append a timestamp to force a fresh reload
                const currentUrl = window.location.href.split('?')[0];
                const params = new URLSearchParams(window.location.search);
                params.set('t', new Date().getTime());
                window.location.href = currentUrl + '?' + params.toString();
            }
        });
    }

    // Attach event listeners to pagination links to preserve the IP view.
    document.querySelectorAll('.pagination a').forEach(link => {
        link.addEventListener('click', function() {
            if (localStorage.getItem("currentView") === "ip") {
                localStorage.setItem("currentView", "ip");
            }
        });
    });

    // --- COLUMN MODAL & TABLE COLUMN TOGGLING ---
    const modal = document.getElementById("toggleColumnsModal");
    const toggleBtn = document.getElementById("toggleColumnsBtn");
    const closeBtn = document.getElementById("toggleClose");
    const table = document.getElementById("ipTable");

    // Modal handling with safe checks.
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

    // Column toggling: Restore saved visibility and update on change.
    document.querySelectorAll('.toggle-container input').forEach(checkbox => {
        const colIndex = parseInt(checkbox.dataset.col);
        checkbox.checked = localStorage.getItem(`col${colIndex}`) !== 'hidden';
        checkbox.addEventListener('change', () => {
            toggleColumn(colIndex, checkbox.checked);
            localStorage.setItem(`col${colIndex}`, checkbox.checked ? 'visible' : 'hidden');
        });
    });

    // Save/Restore functionality – force view state to "ip" so that reloads remain on the IP list.
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

    // Draggable columns implementation.
    let dragged;
    document.querySelectorAll('th').forEach(header => {
        header.draggable = true;
        header.addEventListener('dragstart', e => dragged = e.target);
        header.addEventListener('dragover', e => e.preventDefault());
        header.addEventListener('drop', e => {
            e.preventDefault();
            if (dragged !== e.target) {
                const rows = table.rows;
                Array.from(rows).forEach(row => {
                    const cells = row.cells;
                    const indexFrom = Array.from(cells).indexOf(dragged);
                    const indexTo = Array.from(cells).indexOf(e.target);
                    row.insertBefore(cells[indexFrom], cells[indexTo]);
                });
            }
        });
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
