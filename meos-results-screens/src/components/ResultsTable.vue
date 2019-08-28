<template>

	<div v-if="pages[pageNum]" 
	class="columns"
	:style="{ paddingLeft: pageSidePadding + 'px', paddingRight: pageSidePadding + 'px', paddingTop: pageTopPadding + 'px' }"
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
					:style="{ height: headerRowHeight + 'px' }"
				>
					<th class="className" colspan="3"><span class="pillIcon" :style="{ backgroundColor: classColor(results.cls.clsName) }">{{ results.cls.clsName }} <span class="contText">{{ results.continued ? '(Cont)' : '' }}</span> <span class="classLength">{{ results.cls.length != null ? formatDistance(results.cls.length) + ' km' : '' }}</span></span></th>
					<th class="elapsedHeading" colspan="2">Total</th>
					<th class="splitHeading" colspan="3" v-for="(n, i) in results.cls.radioCount" :key="n">Split {{ n }} - {{ results.cls.radioInfo[i].distance != null ? formatDistance(results.cls.radioInfo[i].distance) + ' km' : '' }}</th>
					<th
						v-if="results.cls.radioCount < column.maxRadioCount"
						:colspan="(column.maxRadioCount - results.cls.radioCount) * 3"
					/>
				</tr>

				<tr
					v-for="result of results.results"
					:key="`${resultsI}-result-${result.id}`"
					:style="{ height: rowHeight + 'px' }"
					v-bind:class="{ 'proceedToDownload':(result.status == 100) }"
				>

					<td class="col-overallRank" :style="{ width: colOverallRank + 'px' }">
						<font-awesome-icon v-if="result.status == 0 && statusZero(result) == 'running'" icon="running" class="pillIcon running" />
						<font-awesome-icon v-else-if="result.status == 0 && statusZero(result) == 'pending'" icon="ellipsis-h" />
						<template v-else-if="result.status == 1"><span class="pillIcon finisher">{{ result.finishRank }}</span></template>
						<template v-else-if="result.status == 100"><span class="pillIcon finisher">{{ result.finishRank }}</span></template>
						<template v-else><span class="pillIcon nonfinisher">{{ statusToRank[result.status] }}</span></template>
					</td>

					<td class="col-competitor" :style="{ width: colCompetitor + 'px' }">{{ result.competitor }}</td>
					<td class="col-club" :style="{ width: colClub + 'px' }">{{ result.club }}</td>

					<td class="col-elapsedTime" :style="{ width: colElapsedTime + 'px' }">
						<template v-if="result.finishTime == null && result.status == 0">{{ (calculateElapsedTime(result.startTime) / 10) | formatAbsoluteTime }}</template>
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

/* roboto-regular - latin */
@font-face {
	font-family: 'Roboto';
	font-style: normal;
	font-weight: 400;
	src: local('Roboto'), local('Roboto-Regular'),
		url('/fonts/roboto-v20-latin-regular.woff2') format('woff2'), /* Chrome 26+, Opera 23+, Firefox 39+ */
		url('/fonts/roboto-v20-latin-regular.woff') format('woff'); /* Chrome 6+, Firefox 3.6+, IE 9+, Safari 5.1+ */
	}

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
	overflow: hidden;
}

table tr td {
	/*border: 1px solid #777;*/
	vertical-align: bottom;
}

td, th {
	padding: 0 3px;
	box-sizing: border-box;
	position: relative;
}

th {
	border-bottom: 1px solid #777;
	vertical-align: bottom;
	padding-bottom: 10px;
}

@keyframes proceedToDownload {
	0% { 
		opacity: 0.1;
	}

	15% {
		opacity: 1;
	}

	25% {
		opacity: 1;
	}

	40% {
		opacity: 0.1;
	}

	100% {
		opacity: 0.1;
	}

}

tr.proceedToDownload td:first-child:after {
	position: absolute;
	z-index: 1;
	display: block;
	text-align: left;
	left: 0;
	top: 0;
	width: 1000px;
	height: 24px;
	padding-top: 5px;
	margin-left: -50px;
	content:"UNOFFICIAL • PROCEED TO DOWNLOAD • UNOFFICIAL • PROCEED TO DOWNLOAD • UNOFFICIAL • PROCEED TO DOWNLOAD";
	font-size: 17px;
	background: repeating-linear-gradient(
		45deg,
		#EF9F01,
		#EF9F01 10px,
		#FEBB34 10px,
		#FEBB34 20px
	);
	color: white;
	opacity: 0.1;
	animation-duration: 30000ms;
	animation-name: proceedToDownload;
	animation-iteration-count: infinite;
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
	padding-top: 10px;
	padding-left: 10px;
}

tr.headingRow th.className span.pillIcon {
	border-radius: 5px;
	display: inline-block;
	padding: 4px 16px 3px 16px;
	width: 225px;
}

th.className .classLength {
	margin-left: 5px;
	font-size: 16px;
}

th.className .contText {
	font-size: 16px;
}

th.elapsedHeading {
	font-size: 14px;
	text-align: center;
}

th.splitHeading {
	font-size: 14px;
	text-align: center;
}

td {
	border-bottom: 1px solid #777;
}

td.col-overallRank {
	text-align: center;
	font-size: 13px;
	vertical-align: middle;
}

td.col-overallRank .pillIcon {
	border: none;
	padding: 2px 5px;
	color: white;
	text-align: center;
	text-decoration: none;
	display: inline-block;
	margin: 4px 2px;
	border-radius: 18px;
}

td.col-overallRank .pillIcon.finisher {
	background-color: #32a852;
}

td.col-overallRank .pillIcon.running {
	padding: 2px 7px;
	background-color: #d18400;
}

tr.proceedToDownload td.col-overallRank .pillIcon {
	background-color: #d18400;
}

td.col-overallRank .pillIcon.nonfinisher {
	background-color: #8c1414;
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
				rowHeight: 30,
				headerRowHeight: 56,
				columnGap: 20,
				pageSidePadding: 10,
				pageTopPadding: 0,

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
					20: 'DNS',
					21: 'CNL',
					99: 'NP',
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

				const save = () => {
					// This is all the data we need in order to restore the state
					return {
						pagesLength: pages.length,
						pageLength: page && page.length,
						columnLength: column && column.classes.length,
						lastCls,
						tableWidth,
						columnWidth,
						columnHeight,
						overallWidth,
					}
				}

				const rollback = state => {
					({
						lastCls,
						tableWidth,
						columnWidth,
						columnHeight,
						overallWidth,
					} = state)

					pages.splice(state.pagesLength)
					page = pages[pages.length - 1]

					if (page) {
						page.splice(state.pageLength)
						column = page[page.length - 1]

						if (column) {
							column.classes.splice(state.columnLength)
							results = column.classes[column.classes.length - 1]
						}
					}
				}

				const fit = height => {
					const additionalWidth = Math.max(0, tableWidth - columnWidth)
					const overflowH = overallWidth + additionalWidth > windowWidth
					const overflowV = columnHeight + height + this.pageTopPadding > windowHeight

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
					
					// Save the current state in case we need to rollback
					const state = save()

					// Are we at the start of a page?
					const atStartOfPage = !page || !page.length || !page[0].classes.length
					const pagesLengthBefore = pages.length

					// Fit the class
					for (const result of cls.clsResults) {
						fit(rowHeight)
						results.push(result)
					}
					
					// Was the class split across multiple pages?
					const classSpansMultiplePages = pages.length > pagesLengthBefore

					if (classSpansMultiplePages && !atStartOfPage) {
						// Rollback
						rollback(state)

						// Fit the class on a new page
						page = null
						column = null
						results = null
						
						for (const result of cls.clsResults) {
							fit(rowHeight)
							results.push(result)
						}
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

					// This code does hh:mm:ss for > 1 hour and mm:ss for < 1 hour

					/*
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
					*/

					// This code does mmm:ss for everyone
					var m, s;

					m = Math.floor(t/60).toString();
					s = Math.floor(t%60).toString().padStart(2, '0');
					return `${m}:${s}`;

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

					// Set the icon to three dots (pending...)
					return 'pending';

				}

				// They have started...
				else {

					// Set the icon to a running man
					return 'running';

				}

			},

			// Displays the distance info (if available) for a particular radio
			formatDistance(d) {

				// Convert the distance in meters into km for display, rounded to 1dp
				var distanceInKm = parseFloat(d / 1000).toFixed(1);

				// Return the distance
				return distanceInKm;

			},

			// Returns a colour for a given string, based upon the string's hash
			classColor(str) {

				var colors = [
					"#278EF1", "#290C74", "#1FC1DA", "#1F1A13", "#0B0A64", "#22BE54", "#148488", "#1329AF", "#23C288", "#29BABF", "#040154", "#4E8A08", "#25C12D", "#5559DE", "#3A6734", "#5F0206", "#12A667", "#5B6EC1", "#53D746", "#1C7C05", "#2237C0", "#35A865", "#245831", "#350CF3", "#06205D", "#362A9D", "#5209C7", "#25B4D1", "#4D3A5E", "#0D76AE", "#12BF6C", "#38D205", "#2DE3F8", "#38F6F6", "#524647", "#2BC774", "#25EF9B", "#1F0162", "#4C7DA6", "#25D957", "#2F4D83", "#47C517", "#2FA877", "#4C49C6", "#3A38B8", "#48DF56", "#21B0C1", "#2355E2", "#3A9C56", "#1A8B5B", "#182867", "#1C331D", "#4EDCD7", "#290540", "#35AEA2", "#163AC9", "#1A1FB1", "#36B1E4", "#5BC3AD", "#25E1DD", "#2ED9B2", "#284D73", "#4BC997", "#03BBC6", "#1CBFD4", "#14E2D1", "#5654BA", "#32A36D", "#451AB1", "#293BE9", "#01BDE2", "#346A96", "#111F56", "#18BA87", "#3F4276", "#4C2544", "#19C977", "#4E6852", "#0724BE", "#2ECDC5", "#31B7D8", "#1B4B31", "#215272", "#1328CC", "#27BC07", "#15C5E8", "#3A2AC3", "#450FF8", "#1CDC86", "#23F3D8", "#41680A", "#38E47E", "#15B5B4", "#186876", "#48C105", "#4DA032", "#37039A", "#4CC0B7", "#30EBD3", "#4DF296", "#329BC8", "#34852A", "#4B70AC", "#2E2CAD", "#3929B7", "#4AD7CB", "#04454D", "#0E1F0F", "#29D608", "#299F6A", "#1A5E48", "#17D3DD", "#4E816E", "#135B42", "#167D96", "#2164B9", "#1BE0F4", "#1E516D", "#5525B9", "#554C71", "#218C1D", "#1A75FA", "#514184", "#4DE313", "#21167B", "#5E67B2", "#3281DA", "#5572F7", "#1E526B", "#3F361F", "#41FCA3", "#155B38", "#0D741C", "#0E3366", "#31DBA7", "#1937DC", "#1E2339", "#347D34", "#0F337B", "#36166D", "#3C1ED3", "#3EF069", "#3C8EA8", "#011327", "#073C33", "#04E83D", "#4694F1", "#5B7A16", "#4D9821", "#4559AB", "#03B615", "#275189", "#58C8FA", "#372BCC", "#589258", "#12F5BC", "#4B36E6", "#161191", "#06C02C", "#1C512B", "#329D86", "#1A72B5", "#04B91B", "#21EB5B", "#33D817", "#24E23F", "#5333F0", "#45C9BC", "#3E08D0", "#4BC539", "#24A610", "#4248EB", "#346C3E", "#5AA472", "#4523CD", "#4FF49B", "#248115", "#590611", "#07A234", "#29FC6E", "#20CE3E", "#3FA8C7", "#23FF9E", "#1FF411", "#21D381", "#5B6D91", "#49A6C1", "#55C621", "#2596B4", "#587C96", "#1ABA60", "#47313E", "#488BFA", "#359E7B", "#4D7611", "#2BB342", "#4F4479", "#4F8368", "#2BFB26", "#063C55", "#04F6A9", "#4B3CB2", "#0B618F", "#286EC5", "#59D6A3", "#1A89EC", "#245F0E", "#26B69A", "#215618", "#2DE732", "#3BB716", "#1DE5AB", "#04F4D1", "#38142A", "#1A1721", "#1360E4", "#0BD473", "#28DE39", "#29A17D", "#51E4D6", "#3E7AA2", "#2AD34A", "#3212D6", "#14C188", "#1626F3", "#2FA84D", "#38A901", "#278BD2", "#526613", "#4F19EB", "#2D7E46", "#0C9CB4", "#221521", "#16ADA6", "#11A1D8", "#3ADC15", "#571EEB", "#36D937", "#015DB5", "#1E3FCE", "#295D8E", "#2E8AB3", "#371643", "#3BF530", "#4E8E02", "#1E8E72", "#16467B", "#230C79", "#547F31", "#49360E", "#1E412E", "#0EEB7E", "#3C8336", "#47497B", "#54B31B", "#48456D", "#1625ED", "#32999B", "#589A5A", "#307FC5", "#060BF9", "#257142", "#3F94AA", "#48599A", "#23CAFC", "#4D3CD4", "#350BF4", "#47D3C8", "#126BD7", "#21F407", "#075791", "#5F5859", "#48A8CF", "#3483DD", "#49406C", "#5FF311", "#192695", "#21452E", "#1BDED4", "#196E92", "#0CB435", "#2ABC6F", "#4B68A8", "#0CA713", "#3BC345", "#365525", "#45841A", "#1E8260", "#3DB268", "#01A97C", "#4BEDA4", "#1C781B", "#04FB15", "#3CB3D6", "#497F69", "#2B8194", "#01734B", "#1F72A1", "#5CC056", "#4B7AE2", "#48D494", "#4177BE", "#286D24", "#112DBD", "#2ECFD6", "#4D3118", "#2457AF", "#149A79", "#23E2D2", "#14FC82", "#1C26BB", "#3BD4B5", "#2D9927", "#20996D", "#148877", "#3A6F39", "#3677E7", "#34BC56", "#1776D2", "#214987", "#2C6C57", "#5B22D6", "#1A6173", "#17A27B", "#46C9AE", "#2EDC99", "#3AFA37", "#36B193", "#488477", "#29E74E", "#212861", "#074A7F", "#496E8A", "#2EBEBC", "#333B2D", "#1FED60", "#15253F", "#2AE0E7", "#18A765", "#427876", "#2C471E", "#23EA89", "#31A7BA", "#424A90", "#188B19", "#1FA99E", "#3171FE", "#35BAF6", "#0EED5D", "#099A61", "#049FC3", "#125359", "#3FBD85", "#2A1A83", "#2C65D4", "#4836E1", "#2D2905", "#1DDDA9", "#13D0D7", "#1FAACB", "#2D4AB7", "#36F9B5", "#358973", "#54B923", "#2BDC1B", "#54E425", "#424DD9", "#490C34", "#08CBC9", "#3AC1C0", "#3CA4B1", "#426287", "#06A952", "#39DED9", "#486EE2", "#4ADE19", "#558A95", "#3976E0", "#0D2511", "#0FBA42", "#4B5292", "#324F39", "#26CA56", "#0AF92D", "#4386EE", "#23E77E", "#3553B1", "#212CE6", "#14C647", "#2BBDA5", "#023351", "#332207", "#438766", "#35AE69", "#073E6C", "#396A8A", "#41B116", "#36BA61", "#49483B", "#29AFE1"
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
