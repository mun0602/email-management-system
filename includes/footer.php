    </main>
    
    <footer class="bg-light mt-5 py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h6 class="text-primary">
                        <i class="bi bi-envelope-fill"></i>
                        <?= APP_NAME ?>
                    </h6>
                    <p class="text-muted small mb-0">
                        Hệ thống quản lý và phân phối email tự động
                    </p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="text-muted small mb-0">
                        Phiên bản <?= APP_VERSION ?> | 
                        <i class="bi bi-clock"></i>
                        <?= date('d/m/Y H:i') ?>
                    </p>
                    <p class="text-muted small mb-0">
                        Phát triển bởi <strong>Email Management Team</strong>
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Loading Spinner -->
    <div id="loadingSpinner" class="loading-overlay" style="display: none;">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Đang tải...</span>
        </div>
    </div>

    <!-- Confirm Modal -->
    <div class="modal fade" id="confirmModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-question-circle text-warning"></i>
                        Xác nhận
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p id="confirmMessage">Bạn có chắc chắn muốn thực hiện hành động này?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x"></i> Hủy
                    </button>
                    <button type="button" class="btn btn-danger" id="confirmAction">
                        <i class="bi bi-check"></i> Xác nhận
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.0.0/dist/chart.umd.js"></script>
    <script src="/assets/js/script.js"></script>
</body>
</html>