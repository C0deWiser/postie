<template>
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h5>{{ $root.$gettext('subscriptions.title') }}</h5>
        </div>
        <div  class="card-body pb-0 pt-0 pr-0 pl-0 " :class="{loading:!ready}">
            <table class="table table-hover table-sm mb-0">
                <thead>
                <tr>
                    <th>{{ $root.$gettext('subscriptions.notification') }}</th>
                    <th class="text-right">{{ $root.$gettext('subscriptions.channels') }}</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="subscription in subscriptions"
                    :key="subscription.notification">
                    <td>
                        {{ subscription.title }} <br>

                        <p v-if="subscription.description">
                        <small class="text-muted">{{ subscription.description }}</small>
                        </p>

                        <div v-if="Postie.showAudienceBadge || Postie.showGroupsBadge" class="mb-1">
                            <template v-if="Postie.showAudienceBadge">
                                <span v-for="audience in subscription.audiences"
                                      :key="audience"
                                      class="badge badge-primary audience-badge">
                                    <i class="bi bi-people mr-1"></i>{{ audience }}
                                </span>
                                <span v-if="subscription.audiences.length === 0"
                                      class="badge badge-primary audience-badge">
                                    <i class="bi bi-people mr-1"></i>{{ $root.$gettext('subscriptions.anyone') }}
                                </span>
                            </template>

                            <template v-if="Postie.showGroupsBadge">
                                <router-link v-for="group in groups(subscription)"
                                             :key="group.shortcode"
                                             :to="'/subscriptions?group=' + group.shortcode"
                                             class="badge badge-secondary audience-badge">
                                    <i class="bi bi-folder mr-1"></i>{{ group.name }}
                                </router-link>
                            </template>
                        </div>

                        <small class="text-muted">{{ subscription.notification }}</small>
                    </td>
                    <td class="text-right table-fit">
                        <channel-btn
                            v-for="(channel, index) in subscription.channels"
                            :key="index"
                            :channel="channel"
                            @toggle="toggleChannel(subscription, channel)"
                        ></channel-btn>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script type="text/ecmascript-6">
export default {
    data() {
        return {
            ready: false,
            error: undefined,
            subscriptions: [],
        };
    },
    mounted() {
        document.title = "Postie";
        this.fetchData();
    },
    watch: {
        '$route'() {
            this.fetchData();
        },
    },
    methods: {
        /**
         * Get subscription groups, skipping the fallback group.
         *
         * @param {Object} subscription Notification.
         * @returns {Object[]} Subscription groups.
         */
        groups(subscription) {
            return subscription.groups.filter(group => !group.fallback);
        },
        /**
         * Загрузка данных
         */
        fetchData() {
            this.$http.get(Postie.basePath + '/api/subscriptions' + location.search)
                .then(response => {
                    this.subscriptions = response.data.subscriptions;
                    this.error = undefined;
                    this.ready = true;

                    document.title = "Postie - " + this.$root.$gettext('subscriptions.title');
                })
                .catch(error => {
                    this.error = error;
                });
        },
        /**
         * Toggle notification channel.
         *
         * @param {Object} subscription Notification.
         * @param {Object} channel Channel.
         */
        toggleChannel(subscription, channel) {
            if (channel.forced) {
                return;
            }

            this.ready = false;

            let channels = {};
            channels[channel.name] = !channel.status;
            let data = {
                notification: subscription.notification,
                channels: channels,
            }

            this.$http.post(Postie.basePath + '/api/subscriptions/toggle', data)
                .then(response => {
                    this.fetchData();
                });
        },

    }
}
</script>

<style lang="scss" scoped>
    .audience-badge {
        font-size: 75%;
        margin-right: .25rem;
    }
</style>
