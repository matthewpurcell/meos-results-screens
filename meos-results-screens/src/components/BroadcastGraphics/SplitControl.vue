<template>

	<div id="greenScreen">

		<div id="graphicsContainer">

			<table id="splitResults" v-bind:class="{ show : showSplits }">

				<tr v-for="result in resultsToDisplay" v-if="result != null" v-bind:key="result.competitorId" v-bind:class="{'highlightCompetitor' : result.competitorId == competitorId}">
					<td class="rank">{{ result.rank }} </td>
					<td class="name">{{ result.name }}</td>
					<td class="club">{{ result.club }}</td>
					<td class="time" v-if="result.diff != null && result.diff != 0">{{ result.diff | formatAbsoluteDiff }}</td> <!-- punched, not the leader -->
					<td class="time" v-else-if="result.diff == 0">{{ result.radioTime | formatAbsoluteTime }}</td> <!-- punched, the leader -->
					<td class="time" v-else-if="calculateDiffToLeader(calculateElapsedTime(resultsResponse.competitor.startTime)) == 0">0:00</td> <!-- not punched, still going. this gets around the filter not correctly printing 0:00 -->
					<td class="time" v-else>{{ calculateDiffToLeader(calculateElapsedTime(resultsResponse.competitor.startTime)) | formatAbsoluteDiff }}</td> <!-- not punched, still going -->
				</tr>

				<tr v-else>
					<td class="rank"></td>
					<td class="name"></td>
					<td class="club"></td>
					<td class="time"></td>
				</tr>

			</table>

			<table id="radioInfo" v-bind:class="{ show : showSplits }">
				<tr>
					<td>
						<span class="className">{{ resultsResponse.competitor.clsName }}</span>
						<span class="radioDetails">{{ resultsResponse.radioInfo.radioName }} &mdash; {{ formatDistance(resultsResponse.radioInfo.distance) }} km {{ (resultsResponse.radioInfo.radioName == "Finish" ? '' : '(' + resultsResponse.radioInfo.percentage + '%)') }}</span>
					</td>
				</tr>
			</table>

			<table id="runnerInfo" v-bind:class="{ show : showSplits == false }">				
				<tr>
					<td class="name">{{ resultsResponse.competitor.name }}</td>
					<td class="class">{{ resultsResponse.competitor.clsName }}</td>
					<td class="time">{{ calculateElapsedTime(resultsResponse.competitor.startTime) | formatAbsoluteTime }}</td>
				</tr>
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
		/*background-color: red;*/
		position: absolute;
		bottom: 100px;
		left: 100px;
		width: 640px;
		height: 198px;
		overflow: hidden;
	}



	#splitResults, #radioInfo, #runnerInfo {
		font-family: Roboto;
		font-size: 26px;		
		border-collapse: separate;
		border-spacing: 0 4px;
	}



	#splitResults {
		position: absolute;
		top: 198px;
		transition: top 1.0s ease-in-out;
	}

	#splitResults.show {
		top: 0px;
	}

	#splitResults tr {
		height: 45px;
		background-color: white;
	}

	#splitResults tr td.rank {
		width: 60px;
		padding-right: 3px;
		text-align: center;
		background-color: #e65c00;
		color: white;
		font-weight: 500;
		border-left: 3px solid #e65c00;
	}

	#splitResults tr td.name {
		width: 360px;
		text-transform: uppercase;
		font-weight: 600;
		padding: 0 10px;
	}

	#splitResults tr td.club {
		width: 100px;
		text-align: center;
		/*background-color: red;*/
		font-weight: 300;
	}

	#splitResults tr td.time {
		width: 110px;
		text-align: right;
		padding-right: 7px;
		font-weight: 500;
		background-color: #e65c00;
		color: white;
		border-right: 3px solid #e65c00;
	}



	#radioInfo {
		width: 640px;
		position: absolute;
		bottom: -5px;
		opacity: 0;
		transition: opacity 1.0s linear;
	}

	#radioInfo.show {
		opacity: 1;
	}

	#radioInfo tr {
		height: 45px;
	}

	#radioInfo tr td {
		height: 45px;
		text-transform: uppercase;
		padding: 0 10px;
		padding-top: 2px;
		background-color: #578a84;
		color: white;
		border-right: 3px solid #578a84;
		border-left: 3px solid #578a84;
		background-image: url('/images/orienteering-australia.png');
		background-size: 175px auto;
		background-position: center right;
		background-repeat: no-repeat;
	}

	#radioInfo tr td .className {
		font-weight: 600;
	}

	#radioInfo tr td .radioDetails {
		padding-left: 15px;
		font-weight: 400;
	}



	#runnerInfo {
		width: 640px;
		position: absolute;
		bottom: -5px;
		opacity: 0;
		transition: opacity 1.0s linear;
	}

	#runnerInfo.show {
		opacity: 1;
	}

	#runnerInfo tr {
		height: 45px;
	}

	#runnerInfo tr td.name {
		width: 300px;
		text-transform: uppercase;
		font-weight: 600;
		padding: 0 10px;
		background-color: white;
	}

	#runnerInfo tr td.class {
		width: 100px;
		text-align: right;
		padding-right: 20px;
		background-color: white;
	}

	#runnerInfo tr td.time {
		width: 100px;
		text-align: right;
		padding-right: 7px;
		font-weight: 500;
		background-color: #e65c00;
		color: white;
		border-right: 3px solid #e65c00;
	}

</style>

<script>

	import meosResultsApi from '@/meos-results-api'

	export default {

		data() {
			return {
				now: new Date(),
				competitorId: this.$route.params.competitorId,
				radioId: this.$route.params.radioId,
				resultsResponse: [],
				resultsToDisplay: [], // three element array
				refreshTimer: '',
				showSplits: false
			}
		},

		created () {

			// Refresh the results from the API
			this.refreshResults()

			// Update the now time every second
			setInterval(() => this.now = new Date(), 1000)

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

			updateLoop()

			setTimeout(() => {
				this.showSplits = true;
			}, 1000)

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
				this.resultsResponse = await meosResultsApi.getSplitResults(this.competitorId, this.radioId);

				// Refresh the data to display
				this.resultsToDisplay = await this.determineDataToDisplay();

			},

			// Finds the two nearest times to the competitor
			determineDataToDisplay() {

				// Create a variable to return
				var resultsToReturn = [null,null,null];

				// Store the array length
				const radioResultsLength = this.resultsResponse.radioResults.length;

				// Set a variable to track whether the competitor has punched this radio
				var competitorHasPunched = false;

				// Check whether the competitor has punched in the radioResults array
				for (var i = 0; i < radioResultsLength; i++) {
					if (this.resultsResponse.radioResults[i].competitorId == this.competitorId) {
						competitorHasPunched = true;
						break;
					}
				}

				// The competitor has punched the radio
				if (competitorHasPunched) {

					// Check if the competitor is FIRST
					if (this.resultsResponse.radioResults[0].competitorId == this.competitorId) {

						// Set the competitor to first place in resultsToReturn
						resultsToReturn[0] = this.resultsResponse.radioResults[0];

						// ...and set second and third places accordingly
						if (radioResultsLength >= 2)
							resultsToReturn[1] = this.resultsResponse.radioResults[1];

						if (radioResultsLength >= 3)
							resultsToReturn[2] = this.resultsResponse.radioResults[2];

					}

					// Check if the competitor is LAST
					else if (this.resultsResponse.radioResults[radioResultsLength - 1].competitorId == this.competitorId) {

						// Check if we have two other competitors
						if (radioResultsLength >= 3) {

							// Set the competitor to last place in resultsToReturn
							resultsToReturn[2] = this.resultsResponse.radioResults[radioResultsLength - 1];

							// Set second last and third last places accordingly
							resultsToReturn[1] = this.resultsResponse.radioResults[radioResultsLength - 2];
							resultsToReturn[0] = this.resultsResponse.radioResults[radioResultsLength - 3];

						}

						// Otherwise, we only have one other competitor (if the competitor was the only competitor they would be caught by the
						// FIRST condition above)
						else {

							// Set the competitor to last place (i.e. second place) in resultsToReturn
							resultsToReturn[1] = this.resultsResponse.radioResults[radioResultsLength - 1];

							// Set second last place (i.e. first place, in this situation) accordingly
							resultsToReturn[0] = this.resultsResponse.radioResults[radioResultsLength - 2];

							// Leave index 2 empty, as there are only two competitors

						}

					}

					// Otherwise, the competitor is within the field somewhere
					else {

						// Locate the competitor in the radioResults array
						var competitorIndex = -1;

						for (var k = 0; k < radioResultsLength; k++) {
							if (this.resultsResponse.radioResults[k].competitorId == this.competitorId) {
								competitorIndex = k;
								break;
							}
						}

						// Check we do inceed have the index
						if (competitorIndex != -1) {

							// Set the resultsToReturn array
							// We're safe doing this, as the conditions above would capture if we risk going outside the bounds
							// of the radioResults array
							resultsToReturn[0] = this.resultsResponse.radioResults[competitorIndex - 1];
							resultsToReturn[1] = this.resultsResponse.radioResults[competitorIndex];
							resultsToReturn[2] = this.resultsResponse.radioResults[competitorIndex + 1];

						}

					}

				}

				// The competitor has not punched the radio (they are still running towards that control)
				else {

					// Calculate their elapsed running time
					const elapsedRunningTime = this.calculateElapsedTime(this.resultsResponse.competitor.startTime);

					// Check we have a time
					if (elapsedRunningTime != null) {

						// Create a dummy radioResults object for this competitor
						const radioResultsObjForCompetitor = {
							"competitorId": this.resultsResponse.competitor.competitorId,
							"name": this.resultsResponse.competitor.name,
							"club": this.resultsResponse.competitor.club,
							"radioTime": null,
							"diff": null,
							"rank": null
						};

						// Is this the one and only competitor so far (i.e. no one else has gone through the radio yet)?
						if (radioResultsLength == 0) {

							// Set the competitor to first place in resultsToReturn
							resultsToReturn[0] = radioResultsObjForCompetitor;

						}

						// Otherwise, others have gone through the radio so we need to figure out where to put this competitor
						else {

							// Store the index of the immediate competitor we are beating
							var beatingCompetitorAtIndex = -1;

							// Loop through all competitors who have punched the radio
							for (var j = 0; j < radioResultsLength; j++) {
								if (elapsedRunningTime < this.resultsResponse.radioResults[j].radioTime) {
									beatingCompetitorAtIndex = j;
									break;
								}
							}

							// Check if the competitor is FIRST
							if (beatingCompetitorAtIndex == 0) {

								// Set the competitor to first place in resultsToReturn
								resultsToReturn[0] = radioResultsObjForCompetitor;

								// ...and set second and third places accordingly
								if (radioResultsLength >= 2)
									resultsToReturn[1] = this.resultsResponse.radioResults[0];

								if (radioResultsLength >= 3)
									resultsToReturn[2] = this.resultsResponse.radioResults[1];

							}

							// Check if the competitor is LAST
							else if (beatingCompetitorAtIndex == -1) {

								// Check if we have two other competitors
								if (radioResultsLength >= 2) {

									// Set the competitor to last place in resultsToReturn
									resultsToReturn[2] = radioResultsObjForCompetitor;

									// Set second last and third last places accordingly
									resultsToReturn[1] = this.resultsResponse.radioResults[radioResultsLength - 1];
									resultsToReturn[0] = this.resultsResponse.radioResults[radioResultsLength - 2];

								}

								// Otherwise, we only have one other competitor (if the competitor was the only competitor they would be caught by the
								// FIRST condition above)
								else {

									// Set the competitor to last place (i.e. second place) in resultsToReturn
									resultsToReturn[1] = radioResultsObjForCompetitor;

									// Set second last place (i.e. first place, in this situation) accordingly
									resultsToReturn[0] = this.resultsResponse.radioResults[radioResultsLength - 2];

									// Leave index 2 empty, as there are only two competitors

								}

							}

							// Otherwise, the competitor is within the field somewhere
							else {

								// Set the resultsToReturn array
								// We're safe doing this, as the conditions above would capture if we risk going outside the bounds
								// of the radioResults array
								resultsToReturn[0] = this.resultsResponse.radioResults[beatingCompetitorAtIndex - 1];
								resultsToReturn[1] = radioResultsObjForCompetitor;
								resultsToReturn[2] = this.resultsResponse.radioResults[beatingCompetitorAtIndex];

							}

						}

					}

				}

				// Return
				return resultsToReturn;

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

					// console.log(elapsedRunningTime);

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

		}

	}

</script>