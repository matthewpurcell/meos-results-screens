<template>

	<div v-if="pages[pageNum]" class="columns">

		<div
			v-for="(column, columnI) of pages[pageNum]"
			:key="columnI"
			class="column"
			:style="{ marginLeft: (columnI === 0 ? 0 : columnGap) + 'px' }"
		>

			<table
				v-for="(results, resultsI) of column"
				:key="resultsI"
			>

				<tbody>

					<tr class="headingRow" :style="{ height: rowHeight * 2 + 'px' }">
						<th class="className" colspan="5">{{ results.cls.clsName }} {{ results.continued ? '(Cont)' : '' }}</th>
						<th colspan="3" v-for="n in results.cls.radioCount" :key="n"><font-awesome-icon icon="broadcast-tower" /><sub>{{ n }}</sub></th>
					</tr>

					<tr v-for="result of results.results" :key="result.id" :style="{ height: rowHeight + 'px' }">

						<td class="col-overallRank" :style="{ width: colOverallRank + 'px' }">
							<font-awesome-icon v-if="result.status == 0" :icon="statusZero(result)" />
							<template v-else-if="result.status == 1">{{ result.finishRank }}</template>
							<template v-else>{{ statusToRank[result.status] }}</template>
						</td>

						<td class="col-competitor" :style="{ width: colCompetitor + 'px' }">{{ result.competitor }}</td>
						<td class="col-club" :style="{ width: colClub + 'px' }">GS A</td>

						<td class="col-elapsedTime" :style="{ width: colElapsedTime + 'px' }">
							<template v-if="result.finishTime == null">{{ (calculateElapsedTime(result.startTime) / 10) | formatAbsoluteTime }}</template>
							<template v-else>{{ result.finishTime }}</template>
						</td>

						<td class="col-elapsedDiff" :style="{ width: colElapsedDiff + 'px' }">{{ result.finishDiff }}</td>

						<!-- we need to use i (index) rather than n (value) so we start at zero-->
						<template v-for="(n, i) in results.cls.radioCount">

							<flash-cell :display-value="result.radios[i].time" :watch-value="result.radios[i].time" :key="result.id + '-' + result.radios[i].code + '-time'" class="col-radioTime" :style="{ width: colRadioTime + 'px' }"></flash-cell>

							<!-- if no radio punch then print no brackets-->
							<flash-cell v-if="result.radios[i].time == null" :display-value="null" :watch-value="result.radios[i].time" :key="result.id + '-' + result.radios[i].code + '-rank'" class="col-radioRank" :style="{ width: colRadioRank + 'px' }"></flash-cell>
							<flash-cell v-else :display-value="'(' + result.radios[i].rank + ')'" :watch-value="result.radios[i].time" :key="result.id + '-' + result.radios[i].code + '-rank'" class="col-radioRank" :style="{ width: colRadioRank + 'px' }"></flash-cell>

							<flash-cell :display-value="result.radios[i].diff" :watch-value="result.radios[i].time" :key="result.id + '-' + result.radios[i].code + '-diff'" class="col-radioDiff" :style="{ width: colRadioDiff + 'px' }"></flash-cell>

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
	white-space: nowrap;
	table-layout: fixed;
}

table tr td {
	/*border: 1px solid #777;*/
	vertical-align: bottom;
}

td, th {
	padding: 0 3px;
	box-sizing: border-box;
}

th {
	border-bottom: 1px solid #777;
	vertical-align: bottom;
	padding-bottom: 10px;
}

th.className {
	text-align: left;
	font-size: 24px;
	text-transform: uppercase;
}

td {
	border-bottom: 1px solid #777;
}

td.col-overallRank {
	text-align: center;
	font-size: 13px;
	padding-bottom: 4px;
}

td.col-competitor {
	max-width: 180px;
	white-space: nowrap;
	overflow: hidden;
	text-overflow: ellipsis;
}

td.col-club {
}

td.col-elapsedTime {
	text-align: right;
}

td.col-elapsedDiff {
	text-align: left;
	font-size: 12px;
}

td.col-radioTime {
	text-align: right;
}

td.col-radioRank {
	font-size: 12px;
}

td.col-radioDiff {
	text-align: left;
	font-size: 12px;
}

.columns {
	display: flex;
}

.column {
	flex: 0 0 auto;
}

</style>

<script>

	import meosResultsApi from '@/meos-results-api'
	import FlashCell from '@/components/FlashCell.vue'

	export default {

		data() {
			return {
				now: new Date(),
				pageNum: +new URLSearchParams(window.location.search).get('page') || 0,
				resultsResponse: null,

				windowWidth: 0,
				windowHeight: 0,
				rowHeight: 22,
				headerRowHeight: 44,
				columnGap: 20,

				// Column widths
				colOverallRank: 40,
				colCompetitor: 180,
				colClub: 50,
				colElapsedTime: 65,
				colElapsedDiff: 45,
				colRadioTime: 65,
				colRadioRank: 30,
				colRadioDiff: 47,

				statusToRank: {
					3: 'MP',
					4: 'DNF',
					5: 'DQ',
					6: 'OT',
					7: 'DNS',
					8: 'CNL',
					9: 'NP',
				}
			}
		},

		computed: {
			// Splits the data into "pages" such that all of the data within each page
			// will fit as efficiently as possible onto the page
			pages() {
				const {
					resultsResponse,
					windowWidth,
					windowHeight,
					rowHeight,
					headerRowHeight,
					columnGap,
					colOverallRank,
					colCompetitor,
					colClub,
					colElapsedTime,
					colElapsedDiff,
					colRadioTime,
					colRadioRank,
					colRadioDiff,
				} = this

				if (!resultsResponse) {
					return []
				}

				const pages = []
				let page = null
				let column = null
				let results = null
				let lastCls = null
				let cls = null
				let tableWidth = 0
				let columnWidth = 0
				let columnHeight = 0
				let overallWidth = 0

				const tableBaseWidth = colOverallRank + colCompetitor + colClub + colElapsedTime + colElapsedDiff
				const tableRadioWidth = colRadioTime + colRadioRank + colRadioDiff

				const fit = height => {
					const additionalWidth = Math.max(0, tableWidth - columnWidth)
					const overflowH = overallWidth + additionalWidth > windowWidth
					const overflowV = columnHeight + height > windowHeight

					// Do we need another page?
					if (!page || (overflowH && page.length > 1)) {
						page = []
						pages.push(page)
						column = null
						results = null
						columnWidth = 0
						columnHeight = 0
						overallWidth = 0
						fit(height)
						return
					}

					// Do we need another column?
					if (!column || overflowV) {
						column = []
						page.push(column)
						results = null
						columnWidth = tableWidth
						columnHeight = 0
						overallWidth += tableWidth + (page.length === 1 ? 0 : columnGap)
						fit(height)
						return
					}

					// Do we need another results section?
					if (!results) {
						results = []
						column.push({
							cls,
							results,
							continued: lastCls === cls,
						})
						lastCls = cls
						columnHeight += headerRowHeight
						fit(height)
						return
					}

					columnWidth += additionalWidth
					columnHeight += height
				}

				for (cls of resultsResponse.cmpResults) {
					tableWidth = tableBaseWidth + tableRadioWidth * cls.radioCount
					results = null

					for (const result of cls.clsResults) {
						fit(rowHeight)
						results.push(result)
					}
				}

				return pages
			},
		},

		filters: {
			formatAbsoluteTime: function(t) {
				
				if (t) {

					var h, m, s;

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
		},

		created () {
			window.addEventListener('resize', () => this.updateWindowSize())
			this.updateWindowSize()
			this.refreshResults()

			setInterval(() => this.now = new Date(), 1000)

			const updateLoop = () => {
				const nowMs = +new Date()
				const updateIntervalMs = 5000;
				const delay = Math.floor(nowMs / 1000) * 1000 - nowMs + updateIntervalMs

				setTimeout(() => {
					this.refreshResults()
					updateLoop()

				}, delay)
			}

			updateLoop()
		},

		components: {
			FlashCell
		},

		methods: {

			updateWindowSize() {
				this.windowWidth = window.innerWidth
				this.windowHeight = window.innerHeight
			},

			async refreshResults () {
				this.resultsResponse = await meosResultsApi.getResults()
			},

			// Calculates the current elapsed time for a competitor, based on their startTime
			calculateElapsedTime(startTime) {
				// Time of day in 10ths of seconds
				const { now } = this;
				const currentTimeSecs = (now.getSeconds() + (60 * now.getMinutes()) + (60 * 60 * now.getHours())) * 10;

				// Calculate elapsed running time
				const elapsedRunningTime = currentTimeSecs - startTime;

				// Check that it's positive
				if (elapsedRunningTime >= 0) {

					// Return the result
					return elapsedRunningTime;

				}

				// Otherwise, return null
				return null;

			},

			// Determines when the status is 0 whether the competitor is started or not yet started, and returns the relevant icon
			statusZero(resultObject) {

				// Calculate their elapsed time
				var elapsedRunningTime = this.calculateElapsedTime(resultObject.startTime);

				// They have not started
				if (elapsedRunningTime == null) {

					// Set the icon to three dots (waiting...)
					return `ellipsis-h`;

				}

				// They have started...
				else {

					// Set the icon to a running man
					return `running`;

				}

			}

		}

	}

</script>
