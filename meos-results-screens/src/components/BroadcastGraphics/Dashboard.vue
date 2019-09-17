<template>

	<div id="dashboard">

		<b-navbar toggleable="md" type="dark" variant="dark">
			<b-navbar-toggle target="nav_collapse"></b-navbar-toggle>
			<b-navbar-brand>Oceania 2019 Broadcast Graphics Control</b-navbar-brand>
			<b-navbar-nav class="ml-auto">
				<button class="btn btn-outline-success launch-output" type="button" v-on:click="launchOutputWindow()">Launch Output Window</button>
			</b-navbar-nav>
		</b-navbar>

		<div class="container mt-4">

			<div>
				<b-alert show variant="info"><strong>Current Event:</strong> Oceania Long Distance</b-alert>
			</div>

			<div>
				<b-card no-body>
					<b-tabs pills card>
						<b-tab title="Latest Punches" active><p>I'm the first tab</p></b-tab>
						<b-tab title="Lower 3rd / Radio Split"><p>I'm the second tab</p></b-tab>
						<b-tab title="Overall Results"><p>I'm the tab with the very, very long title</p></b-tab>
					</b-tabs>
				</b-card>
			</div>

			<button type="button" v-on:click="showSplitControl()">Split Control</button>

			<button type="button" v-on:click="showOverallResults()">Overall Results</button>

			<button type="button" v-on:click="showLatestPunches()">Latest Punches</button>

		</div>

	</div>	

</template>

<!-- https://stackoverflow.com/questions/49653931/scope-bootstrap-css-in-vue -->
<style scoped lang="scss">
#dashboard /deep/ {
  @import "~bootstrap/dist/css/bootstrap.min";
}
</style>

<style scoped>

	#dashboard {
		font-family: sans-serif;
	}

</style>

<script>

	import meosResultsApi from '@/meos-results-api'

	export default {

		data() {
			return {
				resultsResponse: [],
				outputWindow: null,
			}
		},

		created () {

			
		
		},

		mounted() {
			
			// Refresh the results from the API
			this.refreshResults();

			// Update the display
			const updateLoop = () => {
				const nowMs = +new Date()
				const updateIntervalMs = 1000;
				const delay = Math.floor(nowMs / 1000) * 1000 - nowMs + updateIntervalMs

				setTimeout(() => {
					this.refreshResults()
					updateLoop()

				}, delay)
			}

			// Start the update loop
			updateLoop()

		},

		filters: {

			// Format the time for display
			formatAbsoluteDiff: function(t) {
				
				if (t) {

					// Convert into seconds
					t = t / 10;

					// Format the time
					var m, s;
					m = (Math.floor(Math.abs(t/60)).toString());
					s = (Math.floor(Math.abs(t%60)).toString().padStart(2, '0'));

					// If negative, they are faster
					if (t < 0) {
						return `-${m}:${s}`;
					}

					// If positive, they are slower
					else if (t > 0) {
						return `+${m}:${s}`;
					}

				}

				return null;

			},

			// Format the time for display
			formatAbsoluteTime: function(t) {
				
				if (t) {

					// Convert into seconds
					t = t / 10;

					// Format the time
					var m, s;
					m = (Math.floor(Math.abs(t/60)).toString());
					s = (Math.floor(Math.abs(t%60)).toString().padStart(2, '0'));

					return `${m}:${s}`;							

				}

				return null;

			},

		},

		methods: {

			async refreshResults () {

				// this.resultsResponse = await meosResultsApi.getLatestPunches();

			},

			// Calculates the competitors elapsed time
			calculateElapsedTime(competitorStartTime) {

				// Check that we have a time
				if (competitorStartTime) {

					// Time of day in 10ths of seconds
					const { now } = this;
					const currentTimeSecs = (now.getSeconds() + (60 * now.getMinutes()) + (60 * 60 * now.getHours())) * 10;

					// Calculate elapsed running time, we need to do this as all the radio times are relative to that competitor's running time,
					// not absolute time
					const elapsedRunningTime = currentTimeSecs - competitorStartTime;

					// Return the time
					return elapsedRunningTime;

				}

				return null;

			},

			// Displays the distance info (if available) for a particular radio
			formatDistance(d) {

				// Convert the distance in meters into km for display, rounded to 1dp
				var distanceInKm = parseFloat(d / 1000).toFixed(1);

				// Return the distance
				return distanceInKm;

			},

			// Launches the output window
			launchOutputWindow() {

				// Open the output window and get a reference
				this.outputWindow = window.open("/static/output.html");

			},

			showSplitControl() {

				// Check that we have an output window
				if (this.outputWindow) {

					// Send a message to the output window
					var payload = {"command": "CHANGE-GRAPHIC", "graphicName": "showSplitControl"};
					var payloadJson = JSON.stringify(payload);
					this.outputWindow.postMessage(payloadJson, "*");

				}

			},

			showLatestPunches() {

				// Send a message to the output window
				var payload = {"command": "CHANGE-GRAPHIC", "graphicName": "showLatestPunches"};
				var payloadJson = JSON.stringify(payload);
				this.outputWindow.postMessage(payloadJson, "*");

			},

			showOverallResults() {

				// Send a message to the output window
				var payload = {"command": "CHANGE-GRAPHIC", "graphicName": "showOverallResults"};
				var payloadJson = JSON.stringify(payload);
				this.outputWindow.postMessage(payloadJson, "*");

			},

		}

	}

</script>