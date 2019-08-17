<template>
	<td id="flashCell" :class="{ flash }">
		{{ displayValue }}
	</td>
</template>

<style>

	@keyframes flash {
		100% { background-color: #333; }
		0% { background-color: yellow; }
	}

	.flash {
		animation: flash 10s;
	}

</style>

<script>

	export default {

		name: 'flash-cell',
		props: ['display-value', 'watch-value'],

		data() {
			return {
				flash: false,
			};
		},
  
		watch: {
			watchValue() {
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