import Vue from 'vue'
import Router from 'vue-router'
import ResultsTable from '@/components/ResultsScreen/ResultsTable'
import SplitControl from '@/components/BroadcastGraphics/SplitControl'
import OverallStandings from '@/components/BroadcastGraphics/OverallStandings'
import LatestPunches from '@/components/BroadcastGraphics/LatestPunches'
Vue.use(Router)

export default new Router({
  routes: [
    {
      path: '/ResultsScreen',
      name: 'ResultsTable',
      component: ResultsTable
    },
    {
      path: '/ResultsScreen/:page',
      name: 'ResultsTablePage',
      component: ResultsTable
    },
    {
      path: '/SplitControl/:competitorId/:radioId',
      name: 'SplitControl',
      component: SplitControl
    },
    {
      path: '/OverallStandings',
      name: 'OverallStandings',
      component: OverallStandings
    },
    {
      path: '/LatestPunches/:radioId?',
      name: 'LatestPunches',
      component: LatestPunches
    }
  ]
})