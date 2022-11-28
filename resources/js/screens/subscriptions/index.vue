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
                    <th>{{ $root.$gettext('subscriptions.channels') }}</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="notificationDefinition in notificationDefinitions"
                    :key="notificationDefinition.notification">
                    <td>
                        {{ notificationDefinition.title }} <br>

                        <p v-if="notificationDefinition.description">
                        <small class="text-muted">{{ notificationDefinition.description }}</small>
                        </p>

                        <small class="text-muted">{{ notificationDefinition.notification }}</small>
                    </td>
                    <td class="text-right table-fit">
                        <channel-btn
                            v-for="(channel, index) in notificationDefinition.channels"
                            :key="index"
                            :channel="channel"
                            @toggle="toggleChannel(notificationDefinition, channel)"
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
            notificationDefinitions: [],
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
         * Загрузка данных
         */
        fetchData() {
            this.$http.get(Postie.basePath + '/api/subscriptions')
                .then(response => {
                    this.notificationDefinitions = response.data.notification_definitions;
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
         * @param {Object} notificationDefinition Notification.
         * @param {Object} channel Channel.
         */
        toggleChannel(notificationDefinition, channel) {
            if (channel.forced) {
                return;
            }

            this.ready = false;

            let channels = {};
            channels[channel.name] = !channel.status;
            let data = {
                notification: notificationDefinition.notification,
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

</style>
