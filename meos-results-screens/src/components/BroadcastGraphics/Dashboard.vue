<template>

	<div id="graphics-dashboard">

		<b-navbar toggleable="md" type="dark" variant="dark">
			<b-navbar-toggle target="nav_collapse"></b-navbar-toggle>
			<b-navbar-brand>Oceania 2019 Broadcast Graphics Control</b-navbar-brand>
			<b-navbar-nav class="ml-auto">
				<button class="btn btn-outline-success" type="button" v-on:click="launchOutputWindow()">Launch Output Window</button>
			</b-navbar-nav>
		</b-navbar>

		<div class="container mt-4">

			<div>
				<b-alert show variant="info"><strong>Current Event:</strong> {{ resultsResponse.cmpName }}</b-alert>
			</div>

			<div>
				<b-card no-body>
					<b-tabs pills card>

						<b-tab title="Latest Punches" active>

							<b-form-group label="Select a radio">
								<template v-for="radio in resultsResponse.radios">
									<b-form-radio v-model="latestPunchesRadio" name="latestPunchesRadio" :value="{ radio }">{{ radio }}</b-form-radio>
								</template>
								<b-form-radio v-model="latestPunchesRadio" name="latestPunchesRadio" value="FINISH">Finish</b-form-radio>
							</b-form-group>

							<button class="btn btn-outline-success" type="button" v-on:click="showLatestPunches()">Show Latest Punches</button>

						</b-tab>

						<b-tab title="Lower Third / Radio Split">

							<b-form-group label="" class="radioSplitButtons">
								<button class="btn btn-outline-success" type="button" v-on:click="showRunningTime()">Running Time</button>
								<template v-for="radio in resultsResponse.radios">
									<button class="btn btn-outline-success" type="button" v-on:click="showRadioSplit({ radio })">Control {{ radio }}</button>
								</template>
								<button class="btn btn-outline-success" type="button" v-on:click="showRadioSplit('FINISH')">Finish</button>
								<button class="btn btn-outline-danger clear-button" type="button" v-on:click="clearFields()">Clear Filters</button>
							</b-form-group>

							<b-table striped hover selectable
								:items="radioSpiltClassFilteredData"
								:fields="radioSplitClassTableFields" 
								:current-page="radioSplitTableCurrentPage"
								:per-page="radioSplitTablePerPage"
								select-mode="single"
								selected-variant="success"
								class="lowerThirdTable"
								@row-selected="radioSplitOnRowSelected"
							>
								
								<template slot="top-row" slot-scope="{ fields }">
									<td v-for="field in fields" :key="field.key">
										<template v-if="field.label != 'Time' && field.label != 'Club'">
											<input v-model="radioSplitClassFilters[field.key]" :placeholder="field.label" @input="radioSplitClassFilterTextChanged()" style="width: 100px;">
										</template>
									</td>
								</template>

								<template v-slot:cell(runningTime)="data">
									{{ radioSplitClassTimeToShow(data.item) | formatAbsoluteTime }}
								</template>

							</b-table>

							<b-pagination
								v-model="radioSplitTableCurrentPage"
								:total-rows="radioSplitTableTotalRows"
								:per-page="radioSplitTablePerPage"
								align="center"
								size="md"
								class="my-0"
							></b-pagination>

						</b-tab>

						<b-tab title="Overall Results">

							<b-form-group label="Select classes to display">
								<b-form-checkbox-group id="overall-results-classes" v-model="overallResultsClassesSelected" name="overall-results-classes">
									<template v-for="cmpCls in resultsResponse.classes">
										<b-form-checkbox :value="cmpCls.id" number>{{ cmpCls.name }}</b-form-checkbox>
									</template>
								</b-form-checkbox-group>
							</b-form-group>

							<div class="overallResultsSelectAll">
								<input type="checkbox" v-model="overallResultsSelectAllClasses" id="overall-results-classes-select-all"> Select All
							</div>

							<div class="overallResultsButton">
								<button class="btn btn-outline-success" type="button" v-on:click="showOverallResults()">Show Overall Results</button>
								&nbsp;&nbsp;&nbsp;&nbsp;
								<button class="btn btn-outline-success" type="button" v-on:click="showOverallResultsTop10()">Show Overall Results (Top 10)</button>
							</div>

						</b-tab>

					</b-tabs>
				</b-card>
			</div>

		</div>

	</div>	

</template>

<!-- https://stackoverflow.com/questions/49653931/scope-bootstrap-css-in-vue -->
<style scoped lang="scss">
#graphics-dashboard /deep/ {
	@import "~bootstrap/dist/css/bootstrap.min";
}
</style>

<style>

	#graphics-dashboard {
		font-family: sans-serif;
	}

	#graphics-dashboard .custom-control-label {
		padding-top: 2px;
		margin-bottom: 5px !important;
	}

	#graphics-dashboard .overallResultsButton {
		margin-top: 20px;
	}

	#graphics-dashboard .radioSplitButtons {
		margin: 10px 0;
	}

	#graphics-dashboard .radioSplitButtons button {
		margin-right: 20px;
	}

	#graphics-dashboard .radioSplitButtons button.clear-button {
		margin-left: 100px;
	}

</style>

<script>

	import meosResultsApi from '@/meos-results-api'

	export default {

		data() {
			return {
				now: new Date(),
				resultsResponse: [],
				outputWindow: null,
				latestPunchesRadio: '',
				overallResultsClassesSelected: [],
				radioSplitTableTotalRows: 1,
				radioSplitTableCurrentPage: 1,
				radioSplitTablePerPage: 10,
				radioSplitClassTableFields: [
					{
						key: 'bib',
						sortable: true
					},
					{
						key: 'lastName',
						sortable: true
					},
					{
						key: 'firstName',
						sortable: true
					},
					{
						key: 'className',
						sortable: true
					},
					{
						key: 'club',
						sortable: true
					},
					{
						key: 'runningTime',
						label: 'Time'
					}
				],
				radioSplitClassFilters: {
					id: '',
					lastName: '',
					firstName: '',
					className: '',
					club: '',
					startTime: '',
					finishTime: ''
				},
				radioSplitTableRowSelected: [],
			}
		},

		computed: {

			overallResultsSelectAllClasses: {

				get: function () {
					return this.resultsResponse.classes ? this.overallResultsClassesSelected.length == this.resultsResponse.classes.length : false;
				},

				set: function (value) {
					var selected = [];
					if (value) {
						this.resultsResponse.classes.forEach(function (cmpClass) {
						selected.push(cmpClass.id);
						});
					}
					this.overallResultsClassesSelected = selected;
				}
			},

			radioSpiltClassFilteredData () {

				const filtered = this.resultsResponse.competitors.filter(item => {
					return Object.keys(this.radioSplitClassFilters).every(key =>
					String(item[key]).toLowerCase().includes(this.radioSplitClassFilters[key]))
				})

				return filtered.length > 0 ? filtered : [{
					id: '',
					lastName: '',
					firstName: '',
					className: '',
					club: '',
					startTime: '',
					finishTime: ''
				}]

			}

		},

		created () {
			
		},

		mounted() {
			
			// Refresh the results from the API
			this.refreshResults();

			setInterval(() => this.now = new Date(), 1000)

			// Update the display
			const updateLoop = () => {
				const nowMs = +new Date()
				const updateIntervalMs = 5000;
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

				// Refresh from the API
				this.resultsResponse = await meosResultsApi.getDashboardInfo();

				// Set the overallResultsClassesOptions
				this.overallResultsClassesOptions = this.resultsResponse.classes;

				// Set the initial number of items
				this.radioSplitTableTotalRows = this.resultsResponse.competitors.length;

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

			// Gets the competitors for a particular class
			classObjectForClassId(classId) {

				// Find that class in the results
				for (var i = 0; i < this.resultsResponse.classes; i = i + 1) {

					var classObject = this.resultsResponse.classes[i];

					// Found
					if (classObject.id == classId) {
						return this.resultsResponse.classes[i];
					}

				}

				return null;

			},

			radioSplitClassFilterTextChanged() {
				this.radioSplitTableCurrentPage = 1;
			},

			radioSplitClassTimeToShow(competitorObject) {

				// Return their elapsed running time thus far
				if (competitorObject.finishTime == null || competitorObject.finishTime == 0)
					return this.calculateElapsedTime(competitorObject.startTime);

				// Otherwise, return their finish time if they are finished
				return competitorObject.finishTime;

			},

			radioSplitOnRowSelected(items) {
				this.radioSplitTableRowSelected = items
			},

			// Launches the output window
			launchOutputWindow() {

				// Open the output window and get a reference
				this.outputWindow = window.open("/static/output.html");

			},

			showLatestPunches() {

				// Get the radio id
				var radioId = 'FINISH';
				if (this.latestPunchesRadio != "FINISH")
					radioId = this.latestPunchesRadio.radio;

				if (this.outputWindow) {
					this.outputWindow.changeUrl('/#/LatestPunches/' + radioId);
				}

				else {
					alert("Please open the output window.")
				}

			},

			showRunningTime() {

				var competitorId = this.radioSplitTableRowSelected[0].id;

				if (this.outputWindow) {
					this.outputWindow.changeUrl('/#/SplitControl/' + competitorId);
				}

				else {
					alert("Please open the output window.")
				}

			},

			showRadioSplit(radioObject) {

				var radioId;

				if (radioObject == "FINISH")
					radioId = "FINISH"
				else
					radioId = radioObject.radio;

				var competitorId = this.radioSplitTableRowSelected[0].id;

				if (this.outputWindow) {
					this.outputWindow.changeUrl('/#/SplitControl/' + competitorId + '/' + radioId);			
				}

				else {
					alert("Please open the output window.")
				}

			},

			showOverallResults() {

				// Get the classes to show
				var classesToShow = this.overallResultsClassesSelected.join();

				if (this.outputWindow) {
					this.outputWindow.changeUrl(`/#/OverallStandings/${classesToShow}`);
				}

				else {
					alert("Please open the output window.")
				}

			},

			showOverallResultsTop10() {

				// Get the classes to show
				var classesToShow = this.overallResultsClassesSelected.join();

				if (this.outputWindow) {
					this.outputWindow.changeUrl(`/#/OverallStandings/${classesToShow}/10`);
				}

				else {
					alert("Please open the output window.")
				}

			},

			clearFields() {

				this.radioSplitClassFilters['bib'] = '';
				this.radioSplitClassFilters['firstName'] = '';
				this.radioSplitClassFilters['lastName'] = '';
				this.radioSplitClassFilters['className'] = '';

			}

		}

	}

</script>