document.addEventListener("DOMContentLoaded", function() {
    const modal = document.getElementById("toggleColumnsModal");
    const toggleBtn = document.getElementById("toggleColumnsBtn");
    const closeBtn = document.getElementById("toggleClose");
    const table = document.getElementById("ipTable");

    // Modal handling
    toggleBtn.addEventListener("click", () => modal.classList.add("show"));
    closeBtn.addEventListener("click", () => modal.classList.remove("show"));
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

    // Save/Restore functionality
    document.getElementById('saveBtn').addEventListener('click', () => location.reload());
    document.getElementById('restoreBtn').addEventListener('click', () => {
        localStorage.clear();
        location.reload();
    });

    function toggleColumn(index, show) {
        table.querySelectorAll(`tr > :nth-child(${index + 1})`).forEach(cell => {
            cell.style.display = show ? '' : 'none';
        });
    }

    // Initialize column visibility
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
});

// Loading overlay handling
document.querySelector('.filter-form').addEventListener('submit', function() {
    document.getElementById('loadingOverlay').style.display = 'block';
});

window.addEventListener('load', function() {
    document.getElementById('loadingOverlay').style.display = 'none';
});
