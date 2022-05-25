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
                <tr v-for="notificationDefinition in notificationDefinitions"
                    :key="notificationDefinition.notification">
                    <td>
                        {{ notificationDefinition.title }}
                    </td>
                    <td class="text-right table-fit">
                        <template
                            v-for="channel in notificationDefinition.channels"
                        >
                            <i
                                class="channel"
                                :class="[channel.icon, {active:channel.status}, {forced:channel.forced}]"
                                :title="getTitle(channel)"
                                v-if="!channel.hidden"
                                @click="toggleChannel(notificationDefinition, channel)"
                            ></i>
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
            notificationDefinitions: [],
        };
    },
    mounted() {
        document.title = "Postie - Subscriptions";
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
                })
                .catch(error => {
                    this.error = error;
                });
        },
        /**
         * Изменение статуса канала оповещения
         * @param {Object} notificationDefinition Оповещение
         * @param {Object} channel Канал
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
        /**
         * Вычисляет title
         *
         * @param {Object} channel
         * @returns {string}
         */
        getTitle(channel) {
            let status = channel.status ? 'Включен' : 'Выключен';
            let forced = channel.forced ? 'Недоступно для изменения' : '';

            let title = channel.title + ` (${status})`;
            if (channel.forced) {
                title += ' Недоступно для изменения';
            }
            return title;
        },
    }
}
</script>

<style lang="scss" scoped>
@import "../../../sass/variables";

.channel {
    cursor: pointer;
    font-size: 1.5rem;
    color: $unactiveColor;
    margin-right: .5rem;
    transition: color ease .3s;
    &:last-child {
        margin-right: 0;
    }
    &:hover {
        color: $unactiveHoverColor;
    }

    &.active {
        color: $activeColor;
        &:hover {
            color: $activeHoverColor;
        }
    }

    &.forced {
        opacity: .5;
        cursor: not-allowed;
    }
}
</style>