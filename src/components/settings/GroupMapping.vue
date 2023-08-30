<template>
  <button class="group-mapping-add" type="button" @click="$emit('add')">
    {{ t(appName, 'Add group mapping') }}
  </button>
  <div v-for="(mapping, mappingIdx) in groupMapping" :key="mapping">
    <input type="text" class="foreign-group" v-model="mapping.foreign" />
    <select class="local-group" :name="mapping.foreign ? inputNamePrefix + '['+mapping.foreign+']' : ''">
      <option v-for="group in groups" :key="group" :value="group" :selected="mapping.local === group">
        {{ group }}
      </option>
    </select>
    <span class="group-mapping-remove" @click="$emit('remove', mappingIdx)">x</span>
  </div>
</template>

<script>
export default {
  props: ['groups', 'groupMapping', 'inputNamePrefix']
}
</script>

<style scoped>
  .group-mapping-add {
    width: 100%;
    display: block;
  }
  .foreign-group, .local-group {
    width: 133px;
  }
  .group-mapping-remove {
    cursor: pointer;
    font-weight: bold;
  }
</style>
