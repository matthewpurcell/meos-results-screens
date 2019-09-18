<template>
  <div id="app">
  	<router-view></router-view>
    <!--<router-view :key="$route.fullPath"></router-view>--> <!-- needed to force a reload when the url changes (e.g. adding params) -->
  </div>
</template>

<style>

	body {
		margin: 0;
		padding: 0;
	}

</style>

<script>
	
</script>