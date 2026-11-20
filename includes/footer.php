<?php
/**
 * Warehouse Management System - Common Footer
 */

defined('APP_INIT') or define('APP_INIT', true);
?>
        </main>
        
        <footer class="app-footer no-print">
            <div>&copy; <?= date('Y'); ?> <strong><?= e(APP_NAME); ?></strong>. All rights reserved.</div>
            <div>Professional Warehouse Inventory &amp; Movement Tracking</div>
        </footer>
    </div>
</div>

<!-- Main Application Scripts -->
<script src="<?= url('/assets/js/app.js'); ?>"></script>
</body>
</html>
