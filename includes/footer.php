<?php
// includes/footer.php
require_once __DIR__ . '/../config/paths.php';
require_once __DIR__ . '/functions.php';
?>
        </div>
    </main>

    <div class="modal fade confirm-modal" id="confirmActionModal" tabindex="-1" aria-labelledby="confirmActionTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="confirm-modal-icon" aria-hidden="true">
                        <?php echo vet_icon('trash'); ?>
                    </div>
                    <div>
                        <h2 class="confirm-modal-title" id="confirmActionTitle">
                            <?php echo htmlspecialchars('Confirm action', ENT_QUOTES, 'UTF-8'); ?>
                        </h2>
                        <p class="confirm-modal-message" id="confirmActionMessage">
                            <?php echo htmlspecialchars('Please confirm before continuing.', ENT_QUOTES, 'UTF-8'); ?>
                        </p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <?php echo htmlspecialchars('Keep Record', ENT_QUOTES, 'UTF-8'); ?>
                    </button>
                    <button type="button" class="btn btn-danger" id="confirmActionSubmit">
                        <?php echo htmlspecialchars('Confirm', ENT_QUOTES, 'UTF-8'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo htmlspecialchars(BASE_URL . 'assets/js/main.js', ENT_QUOTES, 'UTF-8'); ?>"></script>
</body>
</html>
