<div id="kuapayContainer">
</div>
<div class="buttons">
  <div class="right"><a id="button-confirm" class="button"><span><?php echo $button_confirm; ?></span></a></div>
</div>
<link href="/externals/Kuapay/library/js/Kuapay/kuapay.css" rel="stylesheet" type="text/css">
<script type="text/Javascript" src="/externals/Kuapay/library/js/Kuapay/kuapay.js"></script>
<script type="text/Javascript">
try {
    var kuapay = new KuapayPOS({
        container: "#kuapayContainer",
        submit_button: '#button-confirm',
        url_bill: 'index.php?route=payment/kuapay/bill',
        url_status: 'index.php?route=payment/kuapay/status',
        url_success: 'index.php?route=checkout/success',
        url_locale: '/externals/Kuapay/library/js/Kuapay/locale',
        locale: '<?php echo $locale_code; ?>'
    });
    kuapay.init();
} catch (e) {
    alert('<?php echo $error_could_not_initialize_kuapay; ?>');
}
</script>