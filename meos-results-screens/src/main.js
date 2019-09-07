import Vue from 'vue'
import App from './App.vue'
import router from './router'
import { library } from '@fortawesome/fontawesome-svg-core'
import { faBroadcastTower, faRunning, faEllipsisH } from '@fortawesome/free-solid-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import MarqueeText from 'vue-marquee-text-component' 

library.add(faBroadcastTower, faRunning, faEllipsisH)

Vue.component('font-awesome-icon', FontAwesomeIcon)

Vue.component('marquee-text', MarqueeText)

Vue.config.productionTip = false

new Vue({
  el: '#app',
  router,
  render: h => h(App)
})
