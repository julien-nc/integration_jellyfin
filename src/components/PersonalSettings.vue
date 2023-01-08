<template>
	<div id="jellyfin_prefs" class="section">
		<h2>
			<JellyfinIcon class="icon" />
			{{ t('integration_jellyfin', 'Jellyfin integration') }}
		</h2>
		<div id="jellyfin-content">
			<div class="line">
				<label>
					<EarthIcon :size="20" class="icon" />
					<span v-if="state.server_url">
						{{ t('integration_jellyfin', 'Jellyfin instance address') }} : {{ state.server_url }}
					</span>
					<span v-else>
						{{ t('integration_jellyfin', 'Your administrator has not configured the Jellyfin integration.') }}
					</span>
				</label>
			</div>
			<br>
			<div id="jellyfin-search-block">
				<NcCheckboxRadioSwitch
					:checked="state.search_items_enabled"
					@update:checked="onCheckboxChanged($event, 'search_items_enabled')">
					{{ t('integration_jellyfin', 'Enable searching for items') }}
				</NcCheckboxRadioSwitch>
				<br>
				<p v-if="state.search_items_enabled" class="settings-hint">
					<InformationOutlineIcon :size="20" class="icon" />
					{{ t('integration_jellyfin', 'Warning, everything you type in the search bar will be sent to Jellyfin.') }}
				</p>
				<NcCheckboxRadioSwitch
					:checked="state.link_preview_enabled"
					@update:checked="onCheckboxChanged($event, 'link_preview_enabled')">
					{{ t('integration_jellyfin', 'Enable Jellyfin link previews') }}
				</NcCheckboxRadioSwitch>
			</div>
			<NcCheckboxRadioSwitch v-if="state.server_url"
				:checked="state.navigation_enabled"
				@update:checked="onCheckboxChanged($event, 'navigation_enabled')">
				{{ t('integration_jellyfin', 'Enable navigation link') }}
			</NcCheckboxRadioSwitch>
		</div>
	</div>
</template>

<script>
import InformationOutlineIcon from 'vue-material-design-icons/InformationOutline.vue'
import EarthIcon from 'vue-material-design-icons/Earth.vue'

import JellyfinIcon from './icons/JellyfinIcon.vue'

import { loadState } from '@nextcloud/initial-state'
import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import { showSuccess, showError } from '@nextcloud/dialogs'

import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js'

export default {
	name: 'PersonalSettings',

	components: {
		JellyfinIcon,
		NcCheckboxRadioSwitch,
		EarthIcon,
		InformationOutlineIcon,
	},

	props: [],

	data() {
		return {
			state: loadState('integration_jellyfin', 'user-config'),
			loading: false,
		}
	},

	computed: {
	},

	watch: {
	},

	mounted() {
	},

	methods: {
		onCheckboxChanged(newValue, key) {
			this.state[key] = newValue
			this.saveOptions({ [key]: this.state[key] ? '1' : '0' })
		},
		saveOptions(values) {
			const req = {
				values,
			}
			const url = generateUrl('/apps/integration_jellyfin/config')
			axios.put(url, req).then((response) => {
				showSuccess(t('integration_jellyfin', 'Jellyfin options saved'))
			}).catch((error) => {
				showError(
					t('integration_jellyfin', 'Failed to save Jellyfin options')
					+ ': ' + (error.response?.data?.error ?? '')
				)
				console.debug(error)
			})
		},
	},
}
</script>

<style scoped lang="scss">
#jellyfin_prefs {
	#jellyfin-content {
		margin-left: 40px;
	}
	h2,
	.line,
	.settings-hint {
		display: flex;
		align-items: center;
		.icon {
			margin-right: 4px;
		}
	}

	h2 .icon {
		margin-right: 8px;
	}

	.line {
		> label {
			width: 300px;
			display: flex;
			align-items: center;
		}
		> input {
			width: 300px;
		}
	}
}
</style>
