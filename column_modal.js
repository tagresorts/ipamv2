document.addEventListener("DOMContentLoaded", function() {
<<<<<<< HEAD
=======
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
                graphsSection.style.display = "none";
                ipListSection.style.display = "block";
                toggleViewButton.textContent = "Show Graphs";
                localStorage.setItem("currentView", "ip");
            } else {
                graphsSection.style.display = "block";
                ipListSection.style.display = "none";
                toggleViewButton.textContent = "Show IP List";
                localStorage.setItem("currentView", "graphs");
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
>>>>>>> fix-pagination-graph-display
    const modal = document.getElementById("toggleColumnsModal");
    const toggleBtn = document.getElementById("toggleColumnsBtn");
    const closeBtn = document.getElementById("toggleClose");
    const table = document.getElementById("ipTable");

    // Modal handling
<<<<<<< HEAD
    toggleBtn.addEventListener("click", () => modal.classList.add("show"));
    closeBtn.addEventListener("click", () => modal.classList.remove("show"));
=======
    if (toggleBtn) {
        toggleBtn.addEventListener("click", () => modal.classList.add("show"));
    }
    if (closeBtn) {
        closeBtn.addEventListener("click", () => modal.classList.remove("show"));
    }
>>>>>>> fix-pagination-graph-display
    window.addEventListener("click", (e) => {
        if (e.target === modal) {
            modal.classList.remove("show");
        }
    });

    // Column toggling
    document.querySelectorAll('.toggle-container input').forEach(checkbox => {
        const colIndex = parseInt(checkbox.dataset.col);
        checkbox.checked = localStorage.getItem(`col${colIndex}`) !== 'hidden';
        checkbox.addEventListener('change', () => {
            toggleColumn(colIndex, checkbox.checked);
            localStorage.setItem(`col${colIndex}`, checkbox.checked ? 'visible' : 'hidden');
        });
    });

<<<<<<< HEAD
    // Save/Restore functionality
    document.getElementById('saveBtn').addEventListener('click', () => location.reload());
    document.getElementById('restoreBtn').addEventListener('click', () => {
        localStorage.clear();
        location.reload();
    });
=======
    // Save/Restore functionality – force view state to "ip" so that reloads remain on the IP list.
    if (document.getElementById('saveBtn')) {
        document.getElementById('saveBtn').addEventListener('click', () => {
            localStorage.setItem("currentView", "ip");
            location.reload();
        });
    }
    if (document.getElementById('restoreBtn')) {
        document.getElementById('restoreBtn').addEventListener('click', () => {
            localStorage.clear();
            localStorage.setItem("currentView", "ip");
            location.reload();
        });
    }
>>>>>>> fix-pagination-graph-display

    function toggleColumn(index, show) {
        table.querySelectorAll(`tr > :nth-child(${index + 1})`).forEach(cell => {
            cell.style.display = show ? '' : 'none';
        });
    }

<<<<<<< HEAD
    // Initialize column visibility
=======
    // Initialize column visibility on page load
>>>>>>> fix-pagination-graph-display
    document.querySelectorAll('.toggle-container input').forEach(checkbox => {
        const colIndex = parseInt(checkbox.dataset.col);
        toggleColumn(colIndex, checkbox.checked);
    });

    // Draggable columns
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
<<<<<<< HEAD
});

// Loading overlay handling
document.querySelector('.filter-form').addEventListener('submit', function() {
    document.getElementById('loadingOverlay').style.display = 'block';
});

window.addEventListener('load', function() {
    document.getElementById('loadingOverlay').style.display = 'none';
=======

    // Loading overlay handling for filter form submission.
    if (document.querySelector('.filter-form')) {
        document.querySelector('.filter-form').addEventListener('submit', function() {
            localStorage.setItem("currentView", "ip");
            document.getElementById('loadingOverlay').style.display = 'block';
        });
    }

    window.addEventListener('load', function() {
        document.getElementById('loadingOverlay').style.display = 'none';
    });
>>>>>>> fix-pagination-graph-display
});
