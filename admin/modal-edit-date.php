<?php
function new_seo_locker_edit_date_modal()
{ ?>
    <!-- 🎨 Modal oculto por defecto -->
    <div id="edit-date-modal" class="seo-locker-modal" style="display: none;">
        <div class="seo-locker-modal-content">
            <span class="seo-locker-modal-close" onclick="closeEditModal()">&times;</span>
            <h2>Editar fecha de expiración</h2>

            <!-- 🔥 Form POST tradicional -->
            <form method="POST" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="edit-date-form">
                <input type="hidden" name="action" value="seocontentlocker_update_expire_date">
                <input type="hidden" name="id" id="edit-lead-id">

                <!-- 🔥 Mantener parámetros de contexto -->
                <input type="hidden" name="page" value="<?php echo esc_attr(SLUG); ?>">
                <input type="hidden" name="orderby" value="<?php echo esc_attr($_GET['orderby'] ?? 'created_at'); ?>">
                <input type="hidden" name="order" value="<?php echo esc_attr($_GET['order'] ?? 'desc'); ?>">
                <?php if (!empty($_GET['s'])): ?>
                    <input type="hidden" name="s" value="<?php echo esc_attr($_GET['s']); ?>">
                <?php endif; ?>
                <?php if (!empty($_GET['paged'])): ?>
                    <input type="hidden" name="paged" value="<?php echo esc_attr($_GET['paged']); ?>">
                <?php endif; ?>

                <p>
                    <strong>Lead:</strong> <span id="edit-lead-email"></span>
                </p>

                <p>
                    <strong>Fecha actual:</strong> <span id="current-expire-date-display"></span>
                </p>

                <p>
                    <label for="new-expire-date"><strong>Nueva fecha de expiración:</strong></label><br>
                    <input type="text" name="new_expire_date" id="new-expire-date"
                        required style="width: 100%; padding: 8px; margin-top: 5px;">
                </p>

                <div class="modal-actions" style="margin-top: 20px; text-align: right;">
                    <button type="button" class="button" onclick="closeEditModal()">Cancelar</button>
                    <button type="submit" class="button button-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <?php }
