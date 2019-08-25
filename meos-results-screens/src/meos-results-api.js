import axios from 'axios'

const client = axios.create( {
	baseURL: process.env.BASE_MEOS_MOP_URL,
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
    return this.execute('get', '/results-api.php?cmp=2')
  }
}