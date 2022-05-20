<template>
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h5>Subscriptions</h5>
        </div>
        <div v-if="!ready"
             class="d-flex align-items-center justify-content-center card-bg-secondary p-5 bottom-radius">
            <svg v-if="!error" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                 class="icon spin mr-2 fill-text-color">
                <path
                    d="M12 10a2 2 0 0 1-3.41 1.41A2 2 0 0 1 10 8V0a9.97 9.97 0 0 1 10 10h-8zm7.9 1.41A10 10 0 1 1 8.59.1v2.03a8 8 0 1 0 9.29 9.29h2.02zm-4.07 0a6 6 0 1 1-7.25-7.25v2.1a3.99 3.99 0 0 0-1.4 6.57 4 4 0 0 0 6.56-1.42h2.1z"></path>
            </svg>
            <span v-if="!error">Loading...</span>
            <span v-if="error">{{ error }}</span>
        </div>
        <div v-if="ready" class="card-body pb-0 pt-0">
            <table class="table table-hover table-sm mb-0">
                <thead>
                <tr>
                    <th>Событие</th>
                    <th>Каналы</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="noficationDefinition in notification_definitions"
                    :key="noficationDefinition.notification">
                    <td>
                        {{ noficationDefinition.title }}
                    </td>
                    <td class="text-right table-fit">
                        <template
                            v-for="channel in noficationDefinition.channels"
                        >
                            <i
                                class="channel"
                                :class="[channel.icon, getChannelStatusClass(noficationDefinition, channel), {forced:channel.forced}]"
                                :title="channel.title"
                                v-if="!channel.hidden"
                                @click="toggleChannel(noficationDefinition, channel)"
                            >{{ channel.name }}</i>
                        </template>
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
            notification_definitions: [],
            user_id: undefined,
            user_subscriptions: [],
        };
    },
    mounted() {
        document.title = "Postie - Subscriptions";
        this.fetchData();
    },
    watch: {
        '$route'() {
            this.loadData();
        },
    },
    methods: {
        fetchData() {
            this.$http.get(Postie.basePath + '/api/subs')
                .then(response => {
                    this.notification_definitions = response.data.notification_definitions;
                    this.user_id = response.data.user_id;
                    this.user_subscriptions = response.data.user_subscriptions;
                    this.error = undefined;
                    this.ready = true;
                })
                .catch(error => {
                    this.error = error;
                });
        },
        toggleChannel(notificationDefinition, channel) {
            let userSubscription = this.getUserSubscriptionByNotification(notificationDefinition);
            if (userSubscription.length) {
                // update
                this.$http.put(Postie.basePath + '/api/subs')
            } else {
                // store


                let channels = {};
                channels[channel.name] = !this.getChannelStatus(notificationDefinition, channel);

                let data = {
                    user_id: this.user_id,
                    notification: notificationDefinition.notification,
                    channels,
                }
                console.log(data);

                this.$http.post(Postie.basePath + '/api/subs/', data)
            }
        },
        getChannelStatusClass(notificationDefinition, channel) {
            return this.getChannelStatus(notificationDefinition, channel) ? 'active' : '';
        },
        getChannelStatus(notificationDefinition, channel) {
            let isActive;
            let userSubscriptionChannelStatus = this.getUserSuscriptionChannelStatus(notificationDefinition, channel);
            return (channel.forced || userSubscriptionChannelStatus === null)
                ? channel.default
                : userSubscriptionChannelStatus;
        },
        getUserSuscriptionChannelStatus(notificationDefinition, channel) {
            let userSubscription = this.getUserSubscriptionByNotification(notificationDefinition);
            if (userSubscription.length) {
                return userSubscription[0].channels[channel.name];
            } else {
                return null;
            }
        },
        getUserSubscriptionByNotification(notificationDefinition) {
            return this.user_subscriptions.filter(subscription => {
                return subscription.notification === notificationDefinition.notification;
            });
        },
    }
}
</script>

<style lang="scss" scoped>
.channel {
    cursor: pointer;
    font-size: 1.5rem;
    color: grey;

    &.active {
        color: blue;
    }

    &.forced {
        opacity: .5;
        cursor: not-allowed;
    }
}
</style>