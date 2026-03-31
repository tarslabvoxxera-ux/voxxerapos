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

<h3 style="text-align:center; font-size:28px; font-weight:800; margin:30px 0 24px; letter-spacing:-0.5px; color:#111424;">
    <?= lang('Common.welcome_message') ?>
</h3>

<div id="office_module_list" style="
    display: flex;
    flex-direction: row;
    flex-wrap: wrap;
    gap: 24px;
    padding: 0 40px 40px;
    width: 100%;
    max-width: 1300px;
    margin: 0 auto;
    justify-content: center;
    box-sizing: border-box;
">
    <?php foreach ($allowed_modules as $module) { ?>
        <a href="<?= base_url($module->module_id) ?>"
           title="<?= lang("Module.$module->module_id" . '_desc') ?>"
           style="
               display: flex;
               flex-direction: row;
               align-items: center;
               gap: 16px;
               width: 260px;
               min-width: 220px;
               padding: 20px 24px;
               background: rgba(255,255,255,0.9);
               border-radius: 20px;
               border: 1px solid rgba(0,0,0,0.06);
               box-shadow: 0 6px 24px rgba(0,0,0,0.05);
               text-decoration: none;
               color: #111424;
               transition: all 0.3s ease;
               flex-shrink: 0;
               box-sizing: border-box;
           "
           onmouseover="this.style.transform='translateY(-5px)';this.style.boxShadow='0 16px 40px rgba(46,91,255,0.15)';this.style.borderColor='rgba(46,91,255,0.2)';"
           onmouseout="this.style.transform='';this.style.boxShadow='0 6px 24px rgba(0,0,0,0.05)';this.style.borderColor='rgba(0,0,0,0.06)';"
        >
            <img src="<?= base_url("images/menubar/$module->module_id.svg") ?>"
                 alt="<?= lang("Module.$module->module_id") ?>"
                 style="width:52px;height:52px;flex-shrink:0;filter:invert(36%) sepia(43%) saturate(5436%) hue-rotate(222deg) brightness(101%) contrast(105%);">
            <span style="display:flex;flex-direction:column;gap:4px;">
                <strong style="font-size:16px;font-weight:800;line-height:1.2;color:#111424;">
                    <?= lang("Module.$module->module_id") ?>
                </strong>
                <small style="font-size:12px;color:#64748B;font-weight:500;line-height:1.3;">
                    <?= lang("Module.$module->module_id" . '_desc') ?>
                </small>
            </span>
        </a>
    <?php } ?>
</div>

<!-- Re-open container/row so footer.php can close them cleanly -->
    <div class="container">
        <div class="row">

<?= view('partial/footer') ?>

