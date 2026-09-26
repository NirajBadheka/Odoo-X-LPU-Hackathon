    </main>

    <footer class="px-4 py-3 text-center text-muted-ss small border-top bg-white">
        &copy; <?= date('Y') ?> StockSense — Real-Time Inventory Management System. Built for the Odoo Hackathon.
        <span class="mx-2">·</span><a href="<?= e(BASE_URL) ?>index.php" class="text-muted-ss">Home</a>
        <span class="mx-2">·</span><a href="<?= e(BASE_URL) ?>modules/move_history/index.php" class="text-muted-ss">Stock Ledger</a>
    </footer>
</div>

<script src="<?= e(BASE_URL) ?>assets/vendor/bootstrap/bootstrap.bundle.min.js"></script>
<script src="<?= e(BASE_URL) ?>assets/vendor/sweetalert2/sweetalert2.min.js"></script>
<script src="<?= e(BASE_URL) ?>assets/vendor/chartjs/chart.umd.js"></script>
<script src="<?= e(BASE_URL) ?>assets/js/main.js"></script>
<script src="<?= e(BASE_URL) ?>assets/js/global_search.js"></script>
</body>
</html>
