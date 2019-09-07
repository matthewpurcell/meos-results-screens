import Vue from 'vue'
import Router from 'vue-router'
import ResultsTable from '@/components/ResultsTable'

Vue.use(Router)

export default new Router({
  routes: [
  	{
      path: '/ResultsScreens',
      name: 'ResultsTable',
      component: ResultsTable
    },
    {
      path: '/ResultsScreens/:page',
      name: 'ResultsTablePage',
      component: ResultsTable
    }
  ]
})