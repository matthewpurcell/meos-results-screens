<template>

	<div>

		<div v-for="cls in resultsResponse.cmpResults" v-bind:key="cls.clsId">

			<table>

				<tbody>

					<tr class="headingRow">
						<th class="className" colspan="5">{{ cls.clsName }}</th>
						<th colspan="3" v-for="n in cls.radioCount" v-bind:key="n"><font-awesome-icon icon="broadcast-tower" /><sub>{{ n }}</sub></th>
					</tr>
				
					<tr v-for="result in cls.clsResults" v-bind:key="result.id">

						<td v-if="result.status == 0" class="col-overallRank"><font-awesome-icon v-bind:icon="statusZero(result)" /></td>
						<td v-else-if="result.status == 1" class="col-overallRank">{{ result.finishRank }}</td>
						<td v-else-if="result.status == 3" class="col-overallRank">MP</td>
						<td v-else-if="result.status == 4" class="col-overallRank">DNF</td>
						<td v-else-if="result.status == 5" class="col-overallRank">DQ</td>
						<td v-else-if="result.status == 6" class="col-overallRank">OT</td>
						<td v-else-if="result.status == 7" class="col-overallRank">DNS</td>
						<td v-else-if="result.status == 8" class="col-overallRank">CNL</td>
						<td v-else-if="result.status == 9" class="col-overallRank">NP</td>
						<td v-else class="col-overallRank"></td>

						<td class="col-competitor">{{ result.competitor }}</td>
						<td class="col-club">GS A</td>

						<td v-if="result.finishTime == null" class="col-elapsedTime">{{ formatAbsoluteTime(calculateElapsedTime(result.startTime)) }}</td>
						<td v-else class="col-elapsedTime">{{ result.finishTime }}</td>

						<td class="col-elapsedDiff">{{ result.finishDiff }}</td>

						<!-- we need to use i (index) rather than n (value) so we start at zero-->
						<template v-for="(n, i) in cls.radioCount">

							<td class="col-radioTime" v-bind:key="result.id + '_' + i + '_time'">{{ result.radios[i].time }}</td>

							<!-- if no radio punch then print no brackets-->
							<td class="col-radioRank" v-if="result.radios[i].time == null" v-bind:key="result.id + '_' + i + '_rank'"></td>
							<td class="col-radioRank" v-else v-bind:key="result.id + '_' + i + '_rank'">({{ result.radios[i].rank }})</td>

							<td class="col-radioDiff" v-bind:key="result.id + '_radio' + i + '_diff'">{{ result.radios[i].diff }}</td>

						</template>

					</tr>

				</tbody>

			</table>

		</div>

	</div>

</template>



<style>

@import url('https://fonts.googleapis.com/css?family=Roboto');

body {
	background-color: #333;
	color: #eee;
	font-family: Roboto;
	padding: 0;
	margin: 0;
}

table {
	border-collapse: collapse;
}

table tr td {
	/*border: 1px solid #777;*/
	vertical-align: bottom;
}

tr.headingRow {
	height: 38px; /* IMPORTANT: Heading row height */
}

th {
	border-bottom: 1px solid #777;
	vertical-align: bottom;
	padding-bottom: 8px;
}

th.className {
	text-align: left;
	font-size: 24px;
	text-transform: uppercase;
}

td {
	height: 22px; /* IMPORTANT: Result row height */
	border-bottom: 1px solid #777;
}

td.col-overallRank {
	text-align: center;
	width: 40px; /* IMPORTANT: Overall rank column width */
	font-size: 13px;
	padding-bottom: 4px;
}

td.col-competitor {
	width: 180px; /* IMPORTANT: Competitor column width */
	max-width: 180px;
	white-space: nowrap;
	overflow: hidden;
	text-overflow: ellipsis;
}

td.col-club {
	width: 50px; /* IMPORTANT: Club column width */
}

td.col-elapsedTime {
	text-align: right;
	width: 65px; /* IMPORTANT: Elapsed time column width */
}

td.col-elapsedDiff {
	width: 45px; /* IMPORTANT: Elapsed time diff column width */
	text-align: left;
	font-size: 12px;
	padding-left: 8px; /* IMPORTANT: Add padding onto the width calcs below! */
	padding-bottom: 4px;
}

td.col-radioTime {
	text-align: right;
	width: 65px; /* IMPORTANT: Radio time column width */
}

td.col-radioRank {
	font-size: 12px;
	padding-left: 4px; /* IMPORTANT: Add padding onto the width calcs below! */
	padding-bottom: 4px;
	width: 25px; /* IMPORTANT: Radio rank column width */
}

td.col-radioDiff {
	width: 47px; /* IMPORTANT: Radio time diff column width */
	text-align: left;
	font-size: 12px;
	padding-left: 8px; /* IMPORTANT: Add padding onto the width calcs below! */
	padding-bottom: 4px;
}

</style>



<script>

	import meosResultsApi from '@/meos-results-api'

	export default {

		data() {
			return {
				resultsResponse: {}
			}
		},

		async created () {
			this.refreshResults()
		},

		methods: {

			async refreshResults () {
				this.resultsResponse = await meosResultsApi.getResults()
				// eslint-disable-next-line
				console.log(this.resultsResponse)
			},

			// Formats a time in 10ths of seconds into h:mm:ss or mm:ss
			formatAbsoluteTime(t) {

				if (t) {	

					var h, m, s;
					t = t/10; // convert from 10ths of seconds into seconds

					if (t > 3600) {
						h = Math.floor(t/3600).toString();
						m = Math.floor((t/60)%60).toString().padStart(2, '0');
						s = Math.floor(t%60).toString().padStart(2, '0');
						return `${h}:${m}:${s}`;
					}

					else {
						m = Math.floor((t/60)%60).toString().padStart(2, '0');
						s = Math.floor(t%60).toString().padStart(2, '0');
						return `${m}:${s}`;
					}
				}

				return null;

			},

			// Calculates the current elapsed time for a competitor, based on their startTime
			calculateElapsedTime(startTime) {

				// Time of day in 10ths of seconds
				var dt = new Date();
				var currentTimeSecs = (dt.getSeconds() + (60 * dt.getMinutes()) + (60 * 60 * dt.getHours())) * 10;

				// Calculate elapsed running time
				var elapsedRunningTime = currentTimeSecs - startTime;

				// Return the result
				return elapsedRunningTime;

			},

			// Determines when the status is 0 whether the competitor is started or not yet started, and returns the relevant icon
			statusZero(resultObject) {

				// Calculate their elapsed time
				var elapsedRunningTime = this.calculateElapsedTime(resultObject.startTime);

				// They have started...
				if (elapsedRunningTime >= 0) {

					// Set the icon to a running man
					return `running`;							

				}

				// They have not yet started...
				else {

					// Set the icon to three dots (waiting...)
					return `ellipsis-h`;

				}

			}

		}

	}

</script>