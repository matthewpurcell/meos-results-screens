<template>

	<div id="greenScreen">

		<div id="graphicsContainer">

			<table id="headingTable">
				<tr>
					<td><span class="latestPunches">Latest Punches</span><span class="location">{{ resultsResponse.radioInfo.radioId ? 'Control ' + resultsResponse.radioInfo.radioId : 'Finish' }}</span></td>
				</tr>
			</table>

			<table class="punchTable" id="punchTableRight">

				<tbody is="transition-group" name="punchTableBody" @enter="enter" @beforeEnter="beforeEnter" @leave="leave" :css="false">

					<tr v-for="result in resultsResponse.latestPunches.slice(0, 5)" class="punchRow" :key="result.competitorId">

						<td class="rank"><div>{{ result.rank }}</div></td>
						<td class="className"><div>{{ result.clsName }}</div></td>
						<td class="name"><div>{{ result.name }}</div></td>
						<td class="club"><div>{{ result.club }}</div></td>
						<td class="time"><div>{{ result.time | formatAbsoluteTime }}</div></td>
						<td class="diff"><div>{{ result.diff | formatAbsoluteDiff }}</div></td>

					</tr>

				</tbody>

			</table>

			<table class="punchTable" id="punchTableLeft">

				<tbody is="transition-group" name="punchTableBody" @enter="enter" @beforeEnter="beforeEnter" @leave="leave" :css="false">

					<tr v-for="result in resultsResponse.latestPunches.slice(5, 10).reverse()" class="punchRow" :key="result.competitorId">

						<td class="rank"><div>{{ result.rank }}</div></td>
						<td class="className"><div>{{ result.clsName }}</div></td>
						<td class="name"><div>{{ result.name }}</div></td>
						<td class="club"><div>{{ result.club }}</div></td>
						<td class="time"><div>{{ result.time | formatAbsoluteTime }}</div></td>
						<td class="diff"><div>{{ result.diff | formatAbsoluteDiff }}</div></td>

					</tr>

				</tbody>

			</table>

		</div>

	</div>

</template>

<style scoped>

	#greenScreen {
		width: 1920px;
		height: 1080px;
		background-color: green;
		position: relative;
	}

	#graphicsContainer {
		font-family: Roboto;
		/*background-color: red;*/
		position: absolute;
		bottom: 100px;
		left: 90px;
		width: 1740px;
		height: 300px;
		overflow: hidden;
	}

	table#headingTable {
		position: absolute;
		top: 0;
		right: 0;
		width: 845px;
		background-color: #578a84;
		color: white;
		text-transform: uppercase;
		height: 50px;
	}

	table#headingTable td {
		padding-left: 10px;
		padding-top: 3px;
	}

	table#headingTable td .latestPunches {
		font-size: 28px;
		font-weight: 500;
	}

	table#headingTable td .location {
		margin-left: 20px;
		font-size: 18px;
		font-weight: 300;
	}

	table#punchTableRight {
		position: absolute;
		right: 0;
		bottom: 0;
	}

	table#punchTableLeft{
		position: absolute;
		left: 0;
		bottom: 0;
	}

	table.punchTable {
		font-size: 26px;		
		border-collapse: separate;
		border-spacing: 0 4px;
		text-transform: uppercase;
	}

	table.punchTable tr td div {
		height: 45px;
		background-color: white;
	}

	table.punchTable tr td:first-child div {
		border-top-left-radius: 10px;
		border-bottom-left-radius: 10px;
	}

	table.punchTable tr td:last-child div {
		border-top-right-radius: 10px;
		border-bottom-right-radius: 10px;
	}

	table.punchTable td {
		padding: 0;
	}

	table.punchTable td div {
		box-sizing: border-box;
		max-height: 45px;
		overflow: hidden;
		padding-top: 7px;
	}

	table.punchTable td.rank div {
		width: 50px;
		padding-right: 3px;
		text-align: center;
		background-color: #e65c00;
		color: white;
		font-weight: 500;
		border-left: 3px solid #e65c00;
	}

	table.punchTable td.className div {
		width: 100px;
		padding-left: 10px;
		text-align: center;
		font-weight: 300;
	}

	table.punchTable td.name div {
		width: 420px;
		padding-left: 10px;
		font-weight: 500;
	}

	table.punchTable td.club div {
		width: 70px;
		font-size: 18px;
		text-align: center;
		padding-top: 11px !important;
	}

	table.punchTable td.time div {
		width: 120px;
		text-align: right;
		padding-right: 7px;
		font-weight: 500;
		background-color: #e65c00;
		color: white;
		border-right: 3px solid #e65c00;
	}

	table.punchTable td.diff div {
		width: 85px;
		text-align: right;
		padding-right: 10px;
		font-weight: 300;
		font-size: 18px;
		background-color: #578a84;
		color: white;
		padding-top: 11px !important;
	}

</style>

<script>

	import meosResultsApi from '@/meos-results-api'
	import Velocity from 'velocity-animate'

	export default {

		data() {
			return {
				resultsResponse: [],
				radioId: this.$route.params.radioId,
				ptb: 7,
				mh: 45,
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

				// Get the new results
				if (this.radioId) {
					this.resultsResponse = await meosResultsApi.getLatestPunchesForRadio(this.radioId);
				}
				else {
					this.resultsResponse = await meosResultsApi.getLatestPunches();
				}

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

			// Calculates the difference between two times
			calculateDiffToLeader(secs) {

				// Determine the difference - all times are in seconds (not 10ths of seconds)
				const diff = (secs - this.resultsResponse.radioResults[0].radioTime);

				// Return the diff
				return diff;

			},

			// Displays the distance info (if available) for a particular radio
			formatDistance(d) {

				// Convert the distance in meters into km for display, rounded to 1dp
				var distanceInKm = parseFloat(d / 1000).toFixed(1);

				// Return the distance
				return distanceInKm;

			},

			beforeEnter(el) {
				let divs = el.querySelectorAll("div");
				for (let i = 0; i < divs.length; i++) {
					divs[i].style.maxHeight = "0px";
					divs[i].style.paddingTop = "0px";
					divs[i].style.paddingBottom = "0px";
				}
			},

			enter(el, done) {
				let divs = el.querySelectorAll("div");
				Velocity(
					divs,
					{ maxHeight: this.mh, paddingTop: this.ptb, paddingBottom: this.ptb },
					{ duration: 300, complete: done }
				);
			},
			
			leave(el, done) {
				let divs = el.querySelectorAll("div");
				Velocity(
					divs,
					{ maxHeight: "0px", paddingTop: "0px", paddingBottom: "0px" },
					{ duration: 300, complete: done }
				);
			},

		}

	}

</script>