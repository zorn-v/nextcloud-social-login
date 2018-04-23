jQuery(function ($) {
  var appName = 'sociallogin';
  $('#sociallogin_settings').submit(function (e) {
    e.preventDefault();
    $.post(this.action, $(this).serialize())
      .success(function (data) {
        if (data && data.success) {
          OC.Notification.showTemporary(t(appName, 'Settings for social login successfully saved'));
        }
      });
  });

  initProviderType('openid');
  initProviderType('custom_oauth');

  function initProviderType(providerType){
    createDelegate(providerType);
    createAdd(providerType);
  }

  function createDelegate(providerType){
    $('#'+providerType+'_providers').delegate('.'+providerType+'-remove', 'click', function () {
      var $provider = $(this).parents('.provider-settings');
      var needConfirm = $provider.find('input').filter(function () {return this.value}).length > 0;
      if (needConfirm) {
        OCdialogs.confirm(
          t(appName, 'Do you realy want to remove this '+providerType+' provider ?'),
          t(appName, 'Confirm remove'),
          function (confirmed) {
            if (!confirmed) {
              return;
            }
            $provider.remove();
          },
          true
        );
      } else {
        $provider.remove();
      }
    });
  }
  function createAdd(providerType){
    $('#'+providerType+'_add').click(function () {
      var $tpl = $('#'+providerType+'_provider_tpl');
      var newId = $tpl.data('new-id');
      $tpl.data('new-id', newId+1);
      var html = $tpl.html().replace(/{{provider_id}}/g, newId);
      $('#'+providerType+'_providers').append('<div class="provider-settings">'+html+'</div>');
    })
  }
});
