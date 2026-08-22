<template>
  <component :is="layout">
      <router-view></router-view>
  </component>
</template>

<script>
import { useAuthStore } from './stores/auth'

const default_layout = "default";
export default {
  computed: {
    layout() {
      if (this.$route.name === 'ho-so') {
        const vaiTro = useAuthStore().user?.vai_tro
        if (vaiTro === 'admin' || vaiTro === 'giang_vien') return 'admin-layout'
      }
      return (this.$route.meta.layout || default_layout) + "-layout";
    },
  },
};
</script>
<style>
</style>
