<?php
/**
 * @var array $allowed_modules
 */
?>

<?= view('partial/header') ?>

<script type="text/javascript">
    dialog_support.init("a.modal-dlg");
</script>

<!-- Break out of Bootstrap container/row so the module grid can be full-width -->
        </div><!-- close .row -->
    </div><!-- close .container -->

<h3 style="text-align:center; font-size:28px; font-weight:800; margin:30px 0 20px; letter-spacing:-0.5px; color:#111424;">
    <?= lang('Common.welcome_message') ?>
</h3>

<div id="home_module_list" style="
    display: flex !important;
    flex-direction: row !important;
    flex-wrap: wrap !important;
    gap: 28px !important;
    padding: 20px 40px !important;
    width: 100% !important;
    max-width: 1300px !important;
    margin: 0 auto 40px !important;
    justify-content: center !important;
    box-sizing: border-box !important;
">
    <?php foreach($allowed_modules as $module) { ?>
        <a href="<?= base_url($module->module_id) ?>" class="module_item"
           title="<?= lang("Module.$module->module_id" . '_desc') ?>"
           style="
               display: flex !important;
               flex-direction: row !important;
               align-items: center !important;
               gap: 16px !important;
               width: 260px !important;
               min-width: 220px !important;
               max-width: 300px !important;
               height: auto !important;
               padding: 20px 24px !important;
               background: rgba(255,255,255,0.9) !important;
               border-radius: 20px !important;
               border: 1px solid rgba(0,0,0,0.06) !important;
               box-shadow: 0 6px 24px rgba(0,0,0,0.05) !important;
               text-decoration: none !important;
               color: #111424 !important;
               transition: all 0.3s ease !important;
               flex-shrink: 0 !important;
           "
           onmouseover="this.style.transform='translateY(-5px)';this.style.boxShadow='0 16px 40px rgba(46,91,255,0.15)';"
           onmouseout="this.style.transform='';this.style.boxShadow='0 6px 24px rgba(0,0,0,0.05)';"
        >
            <img src="<?= base_url("images/menubar/$module->module_id.svg") ?>"
                 alt="<?= lang("Module.$module->module_id") ?>"
                 style="width:48px;height:48px;filter:invert(36%) sepia(43%) saturate(5436%) hue-rotate(222deg) brightness(101%) contrast(105%);flex-shrink:0;">
            <span style="display:flex;flex-direction:column;gap:3px;">
                <strong style="font-size:16px;font-weight:800;line-height:1.2;color:#111424;"><?= lang("Module.$module->module_id") ?></strong>
                <small style="font-size:12px;color:#64748B;font-weight:500;line-height:1.3;"><?= lang("Module.$module->module_id" . '_desc') ?></small>
            </span>
        </a>
    <?php } ?>
</div>

<!-- Re-open container/row so footer.php can close them cleanly -->
    <div class="container">
        <div class="row">

<?= view('partial/footer') ?>

