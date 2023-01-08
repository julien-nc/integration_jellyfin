<template>
	<div id="jellyfin_prefs" class="section">
		<h2>
			<JellyfinIcon class="icon" />
			{{ t('integration_jellyfin', 'Jellyfin integration') }}
		</h2>
		<div id="jellyfin-content">
			<div class="line">
				<label for="jellyfin-url">
					<EarthIcon :size="20" class="icon" />
					{{ t('integration_jellyfin', 'Jellyfin instance address') }}
				</label>
				<input id="jellyfin-url"
					v-model="state.server_url"
					type="text"
					:disabled="connected === true"
					:placeholder="t('integration_jellyfin', 'Jellyfin instance address')"
					@input="onInput">
			</div>
			<div v-show="!connected"
				class="line">
				<label
					for="jellyfin-login">
					<AccountIcon :size="20" class="icon" />
					{{ t('integration_jellyfin', 'Login') }}
				</label>
				<input id="jellyfin-login"
					v-model="login"
					type="text"
					:placeholder="t('integration_jellyfin', 'Jellyfin login')"
					@keyup.enter="onConnectClick">
			</div>
			<div v-show="!connected"
				class="line">
				<label
					for="jellyfin-password">
					<LockIcon :size="20" class="icon" />
					{{ t('integration_jellyfin', 'Password') }}
				</label>
				<input id="jellyfin-password"
					v-model="password"
					type="password"
					:placeholder="t('integration_jellyfin', 'Jellyfin password')"
					@keyup.enter="onConnectClick">
			</div>
			<br>
			<p v-if="!connected" class="settings-hint">
				<InformationOutlineIcon :size="20" class="icon" />
				{{ t('integration_jellyfin', 'Login and password are not stored but just used to create a Jellyfin session.') }}
			</p>
			<NcButton v-if="!connected"
				id="jellyfin-connect"
				:class="{ loading }"
				@click="onConnectClick">
				<template #icon>
					<OpenInNewIcon :size="20" />
				</template>
				{{ t('integration_jellyfin', 'Connect to Jellyfin') }}
			</NcButton>
			<div v-if="connected" class="line">
				<label class="jellyfin-connected">
					<CheckIcon :size="20" class="icon" />
					{{ t('integration_jellyfin', 'Connected as {user}', { user: state.user_name }) }}
				</label>
				<NcButton @click="onLogoutClick">
					<template #icon>
						<CloseIcon :size="20" />
					</template>
					{{ t('integration_jellyfin', 'Disconnect from Jellyfin') }}
				</NcButton>
				<span />
			</div>
		</div>
	</div>
</template>

<script>
import InformationOutlineIcon from 'vue-material-design-icons/InformationOutline.vue'
import OpenInNewIcon from 'vue-material-design-icons/OpenInNew.vue'
import CheckIcon from 'vue-material-design-icons/Check.vue'
import CloseIcon from 'vue-material-design-icons/Close.vue'
import EarthIcon from 'vue-material-design-icons/Earth.vue'
import AccountIcon from 'vue-material-design-icons/Account.vue'
import LockIcon from 'vue-material-design-icons/Lock.vue'

import JellyfinIcon from './icons/JellyfinIcon.vue'

import { loadState } from '@nextcloud/initial-state'
import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import { delay } from '../utils.js'
import { showSuccess, showError } from '@nextcloud/dialogs'

import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'

export default {
	name: 'AdminSettings',

	components: {
		JellyfinIcon,
		NcButton,
		OpenInNewIcon,
		EarthIcon,
		CheckIcon,
		CloseIcon,
		AccountIcon,
		LockIcon,
		InformationOutlineIcon,
	},

	props: [],

	data() {
		return {
			state: loadState('integration_jellyfin', 'admin-config'),
			loading: false,
			login: '',
			password: '',
		}
	},

	computed: {
		connected() {
			return !!this.state.server_url
				&& !!this.state.user_name
				&& !!this.state.user_id
		},
	},

	watch: {
	},

	mounted() {
	},

	methods: {
		onLogoutClick() {
			this.state.user_name = ''
			this.saveOptions({ user_name: '' })
		},
		onInput() {
			this.loading = true
			delay(() => {
				this.saveOptions({
					server_url: this.state.server_url,
				})
			}, 2000)()
		},
		saveOptions(values) {
			const req = {
				values,
			}
			const url = generateUrl('/apps/integration_jellyfin/admin-config')
			axios.put(url, req).then((response) => {
				if (response.data.user_name !== undefined) {
					this.state.user_name = response.data.user_name
					this.state.user_id = response.data.user_id
					if (response.data.user_name === '') {
						showError(t('integration_jellyfin', 'Invalid login/password'))
					} else if (response.data.user_name) {
						showSuccess(t('integration_jellyfin', 'Successfully connected to Jellyfin!'))
						this.login = ''
						this.password = ''
					}
				} else {
					showSuccess(t('integration_jellyfin', 'Jellyfin options saved'))
				}
			}).catch((error) => {
				showError(
					t('integration_jellyfin', 'Failed to save Jellyfin options')
					+ ': ' + (error.response?.data?.error ?? '')
				)
				console.debug(error)
			}).then(() => {
				this.loading = false
			})
		},
		onConnectClick() {
			this.loading = true
			this.saveOptions({
				login: this.login,
				password: this.password,
				server_url: this.state.server_url,
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
