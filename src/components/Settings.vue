<template>
  <form @submit.prevent="saveSettings">
    <div v-for="(enabled, name) in options" :key="name">
      <input type="hidden" :name="'options['+name+']'" :value="enabled ? 1 : 0" />
      <input :id="'opt_'+name" type="checkbox" class="checkbox" v-model="options[name]" />
      <label :for="'opt_'+name">{{ optionsTitles[name] || name }}</label>
    </div>
    <button>{{ t(appName, 'Save') }}</button>
    <hr/>
    <div v-for="(provData, provType) in providerTypes" :key="provType">
      <h2>
        {{ provData.title }}
        <button type="button">
          <div class="icon-add" @click="providerAdd(provType)"></div>
        </button>
      </h2>
      <div v-for="(provider, k) in custom_providers[provType]" :key="provider" :ref="'prov_'+provType+'_'+k" class="provider-settings">
        <div class="provider-remove" @click="providerRemove(provType, k)">x</div>
        <label v-for="(fieldData, fieldName) in provData.fields" :key="fieldName">
          {{ fieldData.title }}<br/>
          <input
            v-model="provider[fieldName]"
            :type="fieldData.type"
            :name="'custom_providers['+provType+']['+k+']['+fieldName+']'"
            :readonly="fieldName === 'name' && !provider.isNew"
            :required="fieldData.required"
          />
          <br/>
        </label>
        <label>
          {{ t(appName, 'Button style') }}<br/>
          <select :name="'custom_providers['+provType+']['+k+'][style]'">
            <option value="">{{ t(appName, 'None') }}</option>
            <option v-for="(styleTitle, style) in styleClass" :key="style" :value="style" :selected="provider.style === style">
              {{ styleTitle }}
            </option>
          </select>
        </label>
        <br/>
        <label>
          {{ t(appName, 'Default group') }}<br/>
          <select :name="'custom_providers['+provType+']['+k+'][defaultGroup]'">
            <option value="">{{ t(appName, 'None') }}</option>
            <option v-for="group in groups" :key="group" :value="group" :selected="provider.defaultGroup === group">
              {{ group }}
            </option>
          </select>
        </label>
        <br/>
        <template v-if="provData.hasGroupMapping">
          <button class="group-mapping-add" type="button" @click="provider.groupMapping.push({foreign: '', local: ''})">
            {{ t(appName, 'Add group mapping') }}
          </button>
          <div v-for="(mapping, mappingIdx) in provider.groupMapping" :key="mapping">
            <input type="text" class="foreign-group" v-model="mapping.foreign" />
            <select class="local-group" :name="mapping.foreign ? 'custom_providers['+provType+']['+k+'][groupMapping]['+mapping.foreign+']' : ''">
              <option v-for="group in groups" :key="group" :value="group" :selected="mapping.local === group">
                {{ group }}
              </option>
            </select>
            <span class="group-mapping-remove" @click="provider.groupMapping.splice(mappingIdx, 1)">x</span>
          </div>
        </template>
      </div>
    </div>
    <hr/><br/>
    <div class="provider-settings" v-for="(provider, name) in providers" :key="name">
      <h2 class="provider-title">
        <img :src="imagePath(name.toLowerCase())" /> {{ name[0].toUpperCase() + name.substring(1) }}
      </h2>
      <label>
        {{ t(appName, 'App id') }}<br/>
        <input type="text" :name="'providers['+name+'][appid]'" v-model="provider.appid"/>
      </label>
      <br/>
      <template v-if="name !== 'PlexTv'">
        <label>
          {{ t(appName, 'Secret') }}<br/>
          <input type="password" :name="'providers['+name+'][secret]'" v-model="provider.secret"/>
        </label>
        <br/>
      </template>
      <label>
        {{ t(appName, 'Default group') }}<br/>
        <select :name="'providers['+name+'][defaultGroup]'" v-model="provider.defaultGroup">
          <option value="">{{ t(appName, 'None') }}</option>
          <option v-for="group in groups" :key="group" :value="group">
            {{ group }}
          </option>
        </select>
      </label>
      <template v-if="['google', 'yandex', 'mailru'].includes(name)">
        <br/>
        <label>
          {{ t(appName, 'Allow login only from specified domain') }}<br/>
          <input type="text" :name="'providers['+name+'][auth_params][hd]'"
            :value="provider.auth_params ? provider.auth_params.hd : ''"
            @input="provider.auth_params = provider.auth_params || {}; provider.auth_params.hd = $event.target.value"
          />
        </label>
      </template>
      <template v-if="name === 'GitHub'">
        <br/>
        <label>
          {{ t(appName, 'Allow login only for specified organizations') }}<br/>
          <input type="text" :name="'providers['+name+'][orgs]'" v-model="provider.orgs"/>
        </label>
      </template>
      <template v-if="name === 'BitBucket'">
        <br/>
        <label>
          {{ t(appName, 'Allow login only for specified workspace') }}<br/>
          <input type="text" :name="'providers['+name+'][workspace]'" v-model="provider.workspace"/>
        </label>
      </template>
      <template v-if="name === 'discord'">
        <br/>
        <label>
          {{ t(appName, 'Allow login only for specified guilds') }}<br/>
          <input type="text" :name="'providers['+name+'][guilds]'" v-model="provider.guilds"/>
        </label>
      </template>
    </div>
    <br/>

    <button>{{ t(appName, 'Save') }}</button>
  </form>
</template>

<script>
import { imagePath } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import optionsTitles from './settings/options-titles'
import providerTypes from './settings/provider-types'
import styleClass from './settings/style-class'
import { appName, showError, showInfo } from '../common'

export default {
  data: function () {
    var settingsEl = document.getElementById('sociallogin')
    var data = JSON.parse(settingsEl.dataset.settings)

    data.optionsTitles = optionsTitles
    data.providerTypes = providerTypes
    data.styleClass = styleClass

    if (!data.custom_providers) {
      data.custom_providers = {}
    }

    for (var provType in providerTypes) {
      if (!data.custom_providers[provType]) {
        data.custom_providers[provType] = []
      }
      if (providerTypes[provType].hasGroupMapping) {
        for (var k = 0; k < data.custom_providers[provType].length; ++k) {
          var groupMappingArr = []
          var groupMapping = data.custom_providers[provType][k].groupMapping
          if (groupMapping) {
            for (var foreignGroup in groupMapping) {
              groupMappingArr.push({foreign: foreignGroup, local: groupMapping[foreignGroup]})
            }
          }
          data.custom_providers[provType][k].groupMapping = groupMappingArr
        }
      }
    }
    data.appName = appName
    return data
  },
  mounted: function () {
    var disableReg = document.getElementById('opt_disable_registration')
    if (!disableReg) {
      return
    }
    disableReg.onchange = function () {
      document.getElementById('opt_prevent_create_email_exists').disabled = this.checked
    }
    disableReg.onchange()
  },
  methods: {
    test(e) {console.log(e)},
    imagePath: function (file) {
      return imagePath(appName, file)
    },
    saveSettings: function (e) {
      var vm = this
      axios.post(this.action_url, new FormData(e.target))
        .then(function (res) {
          if (res.data.success) {
            for (var provType in vm.custom_providers) {
              for (var i = 0; i < vm.custom_providers[provType].length; ++i) {
                vm.custom_providers[provType][i].isNew = false
              }
            }
            showInfo(vm.t(appName, 'Settings for social login successfully saved'))
          } else {
            showError(res.data.message)
          }
        })
        .catch(function () {
          showError(vm.t(appName, 'Some error occurred while saving settings'))
        })
    },
    providerAdd: function (provType) {
      this.custom_providers[provType].push({isNew: true, groupMapping: []})
    },
    providerRemove: function (provType, k) {
      var providerEl = this.$refs['prov_' + provType + '_' + k][0]
      var providerTitle = providerEl.querySelector('[name$="[title]"]').value
      var needConfirm = function () {
        var inputs = providerEl.querySelectorAll('input')
        for (var i = 0; i < inputs.length; ++i) {
          if (inputs[i].value) {
            return true
          }
        }
        return false
      }
      if (needConfirm()) {
        const vm = this
        OC.dialogs.confirm(
          this.t(appName, 'Do you really want to remove {providerTitle} provider ?', {'providerTitle': providerTitle}),
          this.t(appName, 'Confirm remove'),
          function (confirmed) {
            if (!confirmed) {
              return;
            }
            vm.custom_providers[provType].splice(k, 1)
          },
          true
        )
      } else {
        this.custom_providers[provType].splice(k, 1)
      }
    }
  }
}
</script>

<style scoped>
  input, select {
    width: 285px;
  }
  .provider-settings {
    display: inline-block;
    vertical-align: top;
    margin-right: 15px;
    margin-bottom: 20px;
  }
  .provider-settings .provider-remove {
    float: right;
    cursor: pointer;
    font-weight: bold;
    font-size: 16px;
    width: 20px;
    text-align: center;
  }
  .provider-settings .group-mapping-remove {
    cursor: pointer;
    font-weight: bold;
  }
  input[readonly] {
    background-color: #ebebeb;
    color: rgba(0, 0, 0, 0.4);
  }
  .section h2.provider-title {
    margin-bottom: 10px;
  }
  .provider-title img {
    width: 20px;
    height: 20px;
    margin-bottom: -2px;
  }
  .group-mapping-add {
    width: 100%;
  }
  .foreign-group, .local-group {
    width: 133px;
  }
</style>
