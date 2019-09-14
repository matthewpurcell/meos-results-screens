<template>

	<div id="greenScreen">

		<template v-for="(cls, index) in resultsResponse.cmpResults">

			<div class="clsResultsContainer" :id="cls.clsName" :key="cls.clsId" v-bind:class="showResultsContainer(index)">

				<table class="clsResults">
					<tr class="resultHeader">
						<td colspan="4"><div class="classInfo"><span class="className">{{ cls.clsName }}</span><span class="courseDistance">{{ cls.course }} • {{ formatDistance(cls.length) }} km</span></div><div class="resultsInfo">Standings at Finish</div></td>
					</tr>
					<tr v-for="result in cls.clsResults.slice(0,1)" :key="result.competitorId" class="resultRow firstPlace">
						<td class="rank">{{ result.rank }}</td>
						<td class="name">{{ result.name }}</td>
						<td class="club">{{ result.club }}</td>
						<td class="time">{{ result.time | formatAbsoluteTime }}</td>
					</tr>
				</table>

				<div class="clsResultsTableContainer" v-bind:ref="cls.clsName">

					<table class="clsResults">

						<tr v-for="result in cls.clsResults.slice(1)" :key="result.competitorId" class="resultRow">

							<td class="rank">{{ result.rank }}</td>
							<td class="name">{{ result.name }}</td>
							<td class="club">{{ result.club }}</td>
							<td class="time">{{ result.diff | formatAbsoluteDiff }}</td>

						</tr>

						<!-- these are the padding rows to take it to a multiple of 10, so the scroll looks nice -->
						<tr class="paddingRow" v-for="n in calculatePaddingRows(cls.clsResults.length)" :key="n">
							<td></td>
							<td></td>
							<td></td>
							<td></td>
						</tr>

					</table>

				</div>

			</div>

		</template>

	</div>

</template>

<style scoped>

	#greenScreen {
		width: 1920px;
		height: 1080px;
		background-color: green;
		position: relative;
	}

	.clsResultsContainer {
		position: absolute;
		opacity: 0;
		width: 750px;
		height: 545px;
		left: 0;
		right: 0;
		top: 0;
		bottom: 0;
		margin: auto;
		/*background-color: red;*/
		transition: opacity 1.0s linear;
	}

	.clsResultsContainer.show {
		opacity: 1;
	}

	.clsResultsTableContainer {
		position: absolute;
		top: 98px;
		width: 750px;
		height: 445px;
		overflow-y: scroll;		
	}

	.clsResultsTableContainer::-webkit-scrollbar {
		width: 0px;  /* Remove scrollbar space */
		background: transparent;  /* Optional: just make scrollbar invisible */
	}

	table.clsResults {
		font-family: Roboto;
		font-size: 26px;		
		border-collapse: separate;
		border-spacing: 0 4px;
		text-transform: uppercase;
	}

	table.clsResults tr {
		height: 45px;
		background-color: white;
	}

	table.clsResults tr.resultHeader {
		background-color: #578a84;
		color: white;
	}

	table.clsResults tr.resultHeader td {
		padding: 0 10px;
	}

	table.clsResults tr.resultHeader td .classInfo {
		float: left;
		font-weight: 500;
		margin-top: 2px;
	}

	table.clsResults tr.resultHeader td .classInfo .courseDistance {
		font-weight: 300;
		padding-left: 20px;
		font-size: 18px;
	}

	table.clsResults tr.resultHeader td .resultsInfo {
		float: right;
		padding-left: 30px;
		font-weight: 300;
		margin-top: 2px;
	}

	table.clsResults tr.paddingRow {
		background-color: transparent;
	}

	table.clsResults tr.resultRow td.rank {
		width: 50px;
		padding-right: 3px;
		text-align: center;
		background-color: #e65c00;
		color: white;
		font-weight: 500;
		border-left: 3px solid #e65c00;
	}

	table.clsResults tr.resultRow.firstPlace td.rank {
		background-color: #CFB53B;
		border-left: 3px solid #CFB53B;
	}

	table.clsResults tr.resultRow td.name {
		width: 450px;
		padding-left: 10px;
	}

	table.clsResults tr.resultRow td.club {
		width: 100px;
		text-align: center;
		font-weight: 300;
	}

	table.clsResults tr.resultRow td.time {
		width: 150px;
		text-align: right;
		padding-right: 7px;
		font-weight: 500;
		background-color: #e65c00;
		color: white;
		border-right: 3px solid #e65c00;
	}

	table.clsResults tr.resultRow.firstPlace td.time {
		background-color: #CFB53B;
		border-right: 3px solid #CFB53B;
	}

</style>

<script>

	import meosResultsApi from '@/meos-results-api'

	export default {

		data() {
			return {
				resultsResponse: [],
				currentClassIndex: 0, // index from resultsResponse of the current class being displayed
				currentClassPage: 1, // the current page of that class being displayed
			}
		},

		created () {

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

			
			// Scroll/transition the results
			setInterval(() => {

				// DEBUG - stop after one scroll
				// clearInterval(transitionInterval);

				// Get a reference to the class object for the current class
				const clsObj = this.resultsResponse.cmpResults[this.currentClassIndex];

				// Check how many pages are in this class
				const totalPages = (Math.ceil(clsObj.clsResults.length / 10) * 10) / 10;
				
				// Check if we still have pages to go?
				if (this.currentClassPage < totalPages) {

					// Scroll to the next page
					this.scrollNextResultsPage(clsObj.clsName);

					// Increment currentClassPage
					this.currentClassPage += 1;

				}

				// We're at the end of the pages for this class
				else {

					// Reset currentClassPage
					this.currentClassPage = 1;

					// Increment currentClassIndex
					this.currentClassIndex += 1;

					// Check if we have reached the end of the results
					if (this.currentClassIndex >= this.resultsResponse.cmpResults.length - 1) {

						// Rewind back to the first class
						this.currentClassIndex = 0;

						// Reset the scroll on all classes
						for (var i = 0; i <= this.resultsResponse.cmpResults.length; i++) {
							const clsName = this.resultsResponse.cmpResults[i].clsName;
							this.$refs[clsName][0].scrollTop = 0;
						}
						
					}

					// Check if the next class has results to show
					while (this.resultsResponse.cmpResults[this.currentClassIndex].clsResults == 0) {

						// If not, increment currentClassIndex and check again
						this.currentClassIndex += 1;

					}

				}

			}, 5000)

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
				this.resultsResponse = await meosResultsApi.getOverallStandings();

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

			// Calculates how many padding table rows we need to take it up to a multiple of 10
			calculatePaddingRows(resultRowCount) {

				return (Math.ceil(resultRowCount / 10) * 10) - resultRowCount;

			},

			scrollNextResultsPage(clsName) {

				//t = current time
				//b = start value
				//c = change in value
				//d = duration
				var easeInOutQuad = function (t, b, c, d) {
					t /= d/2;
					if (t < 1) return c/2*t*t + b;
					t--;
					return -c/2 * (t*(t-2) - 1) + b;
				};

				var element = this.$refs[clsName][0];
				var to = 441;
				var duration = 600;

				var start = element.scrollTop,
					change = start + to,
					currentTime = 0,
					increment = 20;

				var animateScroll = function(){        
					currentTime += increment;
					var val = easeInOutQuad(currentTime, start, change, duration);
					element.scrollTop = val;
					if(currentTime < duration) {
						setTimeout(animateScroll, increment);
					}
				};
				
				animateScroll();

			},

			showResultsContainer(clsIndex) {

				if (clsIndex == this.currentClassIndex)
					return 'show';

				return '';

			},

		}

	}

</script>