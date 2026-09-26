        </div> <!-- End of .content-container -->

        <!-- Professional Footer -->
        <footer class="mt-auto py-3 px-4 border-top bg-white">
            <div class="d-flex flex-column flex-md-row align-items-center justify-content-between gap-2 text-muted small">
                <div>
                    <strong><?= APP_NAME ?></strong> &bull; Version <?= APP_VERSION ?>
                    <span class="ms-2 d-none d-sm-inline">&copy; <?= date('Y') ?> All Rights Reserved.</span>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span><i class="fa-solid fa-shield-halved text-success me-1"></i> RBAC Enforced</span>
                    <a href="<?= BASE_URL ?>setup.php" class="text-decoration-none text-muted" title="Database Tools"><i class="fa-solid fa-wrench"></i></a>
                </div>
            </div>
        </footer>
    </div> <!-- End of .app-main -->
</div> <!-- End of .app-wrapper -->

<?php include __DIR__ . '/scripts.php'; ?>
</body>
</html>
