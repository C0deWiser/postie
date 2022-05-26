<template>
    <i
        class="channel"
        :class="[
                    channel.icon,
                    {active:channel.status},
                    {forced:channel.forced},
                    {not_available:!channel.available}
                ]"
        :title="getTitle(channel)"
        v-if="!channel.hidden"
        @click="$emit('toggle')"
    ></i>
</template>

<script>
export default {
    name: "ChannelBtn",
    emits: ['toggle'],
    props: {
        channel: {
            type: Object,
            default: {}
        },
    },
    methods: {
        /**
         * Вычисляет title
         *
         * @param {Object} channel
         * @returns {string}
         */
        getTitle(channel) {
            let status = channel.status ? 'Включен' : 'Выключен';
            let notAvailable = channel.available ? '' : 'Не указан адрес!';

            let title = `${notAvailable} ` + channel.title + ` (${status})`;
            if (channel.forced) {
                title += ' Недоступно для изменения';
            }
            return title;
        },
    },
}
</script>

<style lang="scss" scoped>
@import "../../sass/variables";

.channel {
    cursor: pointer;
    font-size: 1.5rem;
    color: $unactiveColor;
    margin-right: 1.5rem;
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

    &.not_available {
        position: relative;

        &:after {
            display: block;
            position: absolute;
            font-family: "bootstrap-icons";
            font-size: .7rem;
            font-style: normal;
            content: "\F622";
            right: -0.2rem;
            top: -0.2rem;
            color: red;
            opacity: .7;
        }
    }
}
</style>