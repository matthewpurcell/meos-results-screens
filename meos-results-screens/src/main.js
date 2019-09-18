import Vue from 'vue'
import App from './App.vue'
import router from './router'
import BootstrapVue from 'bootstrap-vue'
import { library } from '@fortawesome/fontawesome-svg-core'
import { faBroadcastTower, faRunning, faEllipsisH } from '@fortawesome/free-solid-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import MarqueeText from 'vue-marquee-text-component'

library.add(faBroadcastTower, faRunning, faEllipsisH)

Vue.component('font-awesome-icon', FontAwesomeIcon)

Vue.component('marquee-text', MarqueeText)

Vue.use(BootstrapVue)

Vue.config.productionTip = false

router.beforeEach((to, from, next) => {
  document.title = to.meta.title
  next()
})

new Vue({
  el: '#app',
  router,
  render: h => h(App)
})