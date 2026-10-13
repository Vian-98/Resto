<?php
/**
 * Footer Template - Minimalist & Comfortable
 */
declare(strict_types=1);
?>
        </div>
    </main>

    <footer class="app-footer">
        <div class="app-container d-flex justify-content-between align-items-center">
            <span>RestoApp &bull; Sistem Pemesanan Restoran</span>
            <span class="text-secondary">Uji Kompetensi BNSP</span>
        </div>
    </footer>

    <!-- Bootstrap 5 Bundle JS CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Flatpickr JS (Offline Local) -->
    <script src="assets/vendor/flatpickr/flatpickr.min.js"></script>
    <script src="assets/vendor/flatpickr/id.js"></script>

    <!-- Client-side Quick Filter -->
    <script>
    function setupTableSearch(inputId, tableId) {
        const input = document.getElementById(inputId);
        const table = document.getElementById(tableId);
        if (!input || !table) return;

        input.addEventListener('keyup', function() {
            const filter = this.value.toLowerCase().trim();
            const rows = table.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            });
        });
    }
    </script>
</body>
</html>
