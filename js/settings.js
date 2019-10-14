jQuery(function ($) {
  var appName = 'sociallogin';
  var showError = function(text) {
    OC.Notification.showTemporary('<div style="font-weight:bold;color:red">'+text+'<div>', {isHTML: true});
  };
  $('#sociallogin_settings').submit(function (e) {
    e.preventDefault();
    $.post(this.action, $(this).serialize())
      .success(function (data) {
        if (data) {
          if (data.success) {
            OC.Notification.showTemporary(t(appName, 'Settings for social login successfully saved'));
          } else {
            showError(data.message);
          }
        }
      })
      .error(function () {
        showError(t(appName, 'Some error occurred while saving settings'));
      });
  });

  $('#disable_registration').change(function () {
    if (this.checked) {
      $('#prevent_create_email_exists').attr('disabled', true);
    } else {
      $('#prevent_create_email_exists').attr('disabled', false);
    }
  }).change();

  initProviderType('openid');
  initProviderType('custom_oidc');
  initProviderType('custom_oauth2');

  function initProviderType(providerType){
    createDelegate(providerType);
    createAdd(providerType);
  }

  function createDelegate(providerType){
    $('#'+providerType+'_providers').delegate('.'+providerType+'-remove', 'click', function () {
      var $provider = $(this).parents('.provider-settings');
      var providerTitle = $provider.find('[name$="[title]"]').val();
      var needConfirm = $provider.find('input').filter(function () {return this.value}).length > 0;
      if (needConfirm) {
        OC.dialogs.confirm(
          t(appName, 'Do you realy want to remove {providerTitle} provider ?', {'providerTitle': providerTitle}),
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
    }).delegate('.group-mapping-add', 'click', function () {
      var $provider = $(this).parents('.provider-settings');
      var $tpl = $provider.find('.group-mapping-tpl');
      $provider.append('<div>'+$tpl.html()+'</div>');
    }).delegate('.group-mapping-remove', 'click', function () {
      $(this).parent().remove();
    }).delegate('.foreign-group', 'input', function () {
      var $this = $(this);
      var newName = this.value ? $this.data('name-tpl')+'['+this.value+']' : '';
      $this.next('.local-group').attr('name', newName)
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
