<template>

	<div v-if="pages[pageNum]" 
	class="columns"
	:style="{ paddingLeft: pageSidePadding + 'px', paddingRight: pageSidePadding + 'px' }"
	>

		<table
			v-for="(column, columnI) of pages[pageNum]"
			:key="columnI"
			class="column"
			:style="{ marginLeft: (columnI === 0 ? 0 : columnGap) + 'px' }"
		>

			<template
				v-for="(results, resultsI) of column.classes"
			>

				<tr
					:key="`${resultsI}-header`"
					class="headingRow"
					:style="{ height: headerRowHeight + 'px', backgroundColor: classColor(results.cls.clsName) }"
				>
					<th class="className" colspan="5">{{ results.cls.clsName }} {{ results.continued ? '(Cont)' : '' }}</th>
					<th colspan="3" v-for="n in results.cls.radioCount" :key="n"><font-awesome-icon icon="broadcast-tower" /><sub>{{ n }}</sub></th>
					<th
						v-if="results.cls.radioCount < column.maxRadioCount"
						:colspan="(column.maxRadioCount - results.cls.radioCount) * 3"
					/>
				</tr>

				<tr
					v-for="result of results.results"
					:key="`${resultsI}-result-${result.id}`"
					:style="{ height: rowHeight + 'px' }"
				>

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

					<td
						v-if="results.cls.radioCount < column.maxRadioCount"
						:colspan="(column.maxRadioCount - results.cls.radioCount) * 3"
					/>

				</tr>

			</template>

		</table>

	</div>

</template>

<style>

@import url('https://fonts.googleapis.com/css?family=Roboto');

body {
	background-color: #333;
	color: #eee;
	font-family: Roboto;
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

tr:nth-child(even){
	background-color: #444;
}

tr:nth-child(odd){
	background-color: #333;
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
	vertical-align: middle;
}

td.col-competitor {
	max-width: 180px;
	white-space: nowrap;
	overflow: hidden;
	text-overflow: ellipsis;
	vertical-align: middle;
}

td.col-club {
	vertical-align: middle;
}

td.col-elapsedTime {
	text-align: right;
	vertical-align: middle;
}

td.col-elapsedDiff {
	text-align: left;
	font-size: 12px;
	vertical-align: middle;
	padding-top: 2px;
}

td.col-radioTime {
	text-align: right;
	vertical-align: middle;
}

td.col-radioRank {
	font-size: 12px;
	vertical-align: middle;
	padding-top: 2px;
}

td.col-radioDiff {
	text-align: left;
	font-size: 12px;
	vertical-align: middle;
	padding-top: 2px;
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
				rowHeight: 26,
				headerRowHeight: 44,
				columnGap: 20,
				pageSidePadding: 10,

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
				
				const windowWidth = this.windowWidth - this.pageSidePadding * 2

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
						column = {
							maxRadioCount: 0,
							classes: [],
						}
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
						column.maxRadioCount = Math.max(column.maxRadioCount, cls.radioCount)
						column.classes.push({
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
				
				// Trim away empty columns/sections/etc
				for (let i = pages.length - 1; i >= 0; i--) {
					const page = pages[i]

					for (let j = page.length - 1; j >= 0; j--) {
						const column = page[j]

						for (let k = column.classes.length - 1; k >= 0; k--) {
							const section = column.classes[k]

							if (!section.results.length) {
								column.classes.splice(k, 1)
							}
						}

						if (!column.classes.length) {
							page.splice(j, 1)
						}
					}

					if (!page.length) {
						pages.splice(i, 1)
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
						if (t >= 600 ) {
							m = Math.floor((t/60)%60).toString().padStart(2, '0');
						}
						else {
							m = Math.floor((t/60)%60).toString().padStart(1, '0');
						}
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

			},

			// Returns a colour for a given string, based upon the string's hash
			classColor(str) {

				var colors = [
					"#63b598", "#ce7d78", "#ea9e70", "#a48a9e", "#c6e1e8", "#648177" ,"#0d5ac1" ,
					"#f205e6" ,"#1c0365" ,"#14a9ad" ,"#4ca2f9" ,"#a4e43f" ,"#d298e2" ,"#6119d0",
					"#d2737d" ,"#c0a43c" ,"#f2510e" ,"#651be6" ,"#79806e" ,"#61da5e" ,"#cd2f00" ,
					"#9348af" ,"#01ac53" ,"#c5a4fb" ,"#996635","#b11573" ,"#4bb473" ,"#75d89e" ,
					"#2f3f94" ,"#2f7b99" ,"#da967d" ,"#34891f" ,"#b0d87b" ,"#ca4751" ,"#7e50a8" ,
					"#c4d647" ,"#e0eeb8" ,"#11dec1" ,"#289812" ,"#566ca0" ,"#ffdbe1" ,"#2f1179" ,
					"#935b6d" ,"#916988" ,"#513d98" ,"#aead3a", "#9e6d71", "#4b5bdc", "#0cd36d",
					"#250662", "#cb5bea", "#228916", "#ac3e1b", "#df514a", "#539397", "#880977",
					"#f697c1", "#ba96ce", "#679c9d", "#c6c42c", "#5d2c52", "#48b41b", "#e1cf3b",
					"#5be4f0", "#57c4d8", "#a4d17a", "#225b8", "#be608b", "#96b00c", "#088baf",
					"#f158bf", "#e145ba", "#ee91e3", "#05d371", "#5426e0", "#4834d0", "#802234",
					"#6749e8", "#0971f0", "#8fb413", "#b2b4f0", "#c3c89d", "#c9a941", "#41d158",
					"#fb21a3", "#51aed9", "#5bb32d", "#807fb", "#21538e", "#89d534", "#d36647",
					"#7fb411", "#0023b8", "#3b8c2a", "#986b53", "#f50422", "#983f7a", "#ea24a3",
					"#79352c", "#521250", "#c79ed2", "#d6dd92", "#e33e52", "#b2be57", "#fa06ec",
					"#1bb699", "#6b2e5f", "#64820f", "#1c271", "#21538e", "#89d534", "#d36647",
					"#7fb411", "#0023b8", "#3b8c2a", "#986b53", "#f50422", "#983f7a", "#ea24a3",
					"#79352c", "#521250", "#c79ed2", "#d6dd92", "#e33e52", "#b2be57", "#fa06ec",
					"#1bb699", "#6b2e5f", "#64820f", "#1c271", "#9cb64a", "#996c48", "#9ab9b7",
					"#06e052", "#e3a481", "#0eb621", "#fc458e", "#b2db15", "#aa226d", "#792ed8",
					"#73872a", "#520d3a", "#cefcb8", "#a5b3d9", "#7d1d85", "#c4fd57", "#f1ae16",
					"#8fe22a", "#ef6e3c", "#243eeb", "#1dc18", "#dd93fd", "#3f8473", "#e7dbce",
					"#421f79", "#7a3d93", "#635f6d", "#93f2d7", "#9b5c2a", "#15b9ee", "#0f5997",
					"#409188", "#911e20", "#1350ce", "#10e5b1", "#fff4d7", "#cb2582", "#ce00be",
					"#32d5d6", "#17232", "#608572", "#c79bc2", "#00f87c", "#77772a", "#6995ba",
					"#fc6b57", "#f07815", "#8fd883", "#060e27", "#96e591", "#21d52e", "#d00043",
					"#b47162", "#1ec227", "#4f0f6f", "#1d1d58", "#947002", "#bde052", "#e08c56",
					"#28fcfd", "#bb09b", "#36486a", "#d02e29", "#1ae6db", "#3e464c", "#a84a8f",
					"#911e7e", "#3f16d9", "#0f525f", "#ac7c0a", "#b4c086", "#c9d730", "#30cc49",
					"#3d6751", "#fb4c03", "#640fc1", "#62c03e", "#d3493a", "#88aa0b", "#406df9",
					"#615af0", "#4be47", "#2a3434", "#4a543f", "#79bca0", "#a8b8d4", "#00efd4",
					"#7ad236", "#7260d8", "#1deaa7", "#06f43a", "#823c59", "#e3d94c", "#dc1c06",
					"#f53b2a", "#b46238", "#2dfff6", "#a82b89", "#1a8011", "#436a9f", "#1a806a",
					"#4cf09d", "#c188a2", "#67eb4b", "#b308d3", "#fc7e41", "#af3101", "#ff065",
					"#71b1f4", "#a2f8a5", "#e23dd0", "#d3486d", "#00f7f9", "#474893", "#3cec35",
					"#1c65cb", "#5d1d0c", "#2d7d2a", "#ff3420", "#5cdd87", "#a259a4", "#e4ac44",
					"#1bede6", "#8798a4", "#d7790f", "#b2c24f", "#de73c2", "#d70a9c", "#25b67",
					"#88e9b8", "#c2b0e2", "#86e98f", "#ae90e2", "#1a806b", "#436a9e", "#0ec0ff",
					"#f812b3", "#b17fc9", "#8d6c2f", "#d3277a", "#2ca1ae", "#9685eb", "#8a96c6",
					"#dba2e6", "#76fc1b", "#608fa4", "#20f6ba", "#07d7f6", "#dce77a", "#77ecca"
					];

				var hash = 0;

				if (str.length === 0) return hash;

				for (var i = 0; i < str.length; i++) {
					hash = str.charCodeAt(i) + ((hash << 5) - hash);
					hash = hash & hash;
				}

				hash = ((hash % colors.length) + colors.length) % colors.length;

				return colors[hash];

			},

		}

	}

</script>
