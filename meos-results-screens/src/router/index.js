import Vue from 'vue'
import Router from 'vue-router'
import ResultsTable from '@/components/ResultsScreen/ResultsTable'
import SplitControl from '@/components/BroadcastGraphics/SplitControl'
import OverallStandings from '@/components/BroadcastGraphics/OverallStandings'
import LatestPunches from '@/components/BroadcastGraphics/LatestPunches'
import Dashboard from '@/components/BroadcastGraphics/Dashboard'
Vue.use(Router)

export default new Router({
  routes: [
    {
      path: '/ResultsScreen',
      name: 'ResultsTable',
      component: ResultsTable,
      meta: {
      	title: "Results Screen"
      }
    },
    {
      path: '/ResultsScreen/:page',
      name: 'ResultsTablePage',
      component: ResultsTable,
      meta: {
      	title: "Results Screen"
      }
    },
    {
      path: '/SplitControl/:competitorId/:radioId',
      name: 'SplitControl',
      component: SplitControl,
      meta: {
      	title: "Graphics - Split Control"
      }
    },
    {
      path: '/OverallStandings',
      name: 'OverallStandings',
      component: OverallStandings,
      meta: {
      	title: "Graphics - Overall Standings"
      }
    },
    {
      path: '/LatestPunches/:radioId?',
      name: 'LatestPunches',
      component: LatestPunches,
      meta: {
      	title: "Graphics - Latest Punches"
      }
    },
    {
      path: '/Dashboard',
      name: 'Dashboard',
      component: Dashboard,
      meta: {
      	title: "Graphics - Dashboard"
      }
    },
  ]
})