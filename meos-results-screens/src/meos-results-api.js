import axios from 'axios'

const client = axios.create( {
	baseURL: 'https://meos.codecadets.com/mop/',
	json: true
})

export default {
  async execute (method, resource, data) {
    return client({
      method,
      url: resource,
      data,
      headers: {}
    }).then(req => {
      return req.data
    })
  },
  getResults () {
    return this.execute('get', '/results-api.php?cmp=1&cls=1,2,3')
  }
}