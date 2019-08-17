<template>
	<td id="timeCell" class="col-radioTime" :class="{ flash }">
		{{ value.time }}
	</td>
</template>

<style>

	@keyframes flash {
		100% { background-color: #333; }
		0% { background-color: yellow; }
	}

	.flash {
		animation: flash 2s;
	}

</style>

<script>

	export default {

		name: 'time-cell',
		props: ['value'],

		data() {
			return {
				flash: false,
			};
		},
  
		watch: {
			value() {
				if (this.flash) {
					this.flash = false;
					this.$nextTick(() => {
						this.$el.offsetWidth;  // Force element to reflow
						this.flash = true;
					});
				}

				else {
					this.flash = true;
				}
			},
		}

	}

</script>