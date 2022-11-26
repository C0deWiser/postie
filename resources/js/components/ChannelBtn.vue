<template>
    <div class="btn-group mr-1" v-if="!channel.hidden">
        <button type="button" class="btn btn-sm dropdown-toggle"
                :class="this.getColor(channel)"
                data-toggle="dropdown" aria-expanded="false">
            <i :class="channel.icon"></i>
            {{ channel.title }}
        </button>
        <div class="dropdown-menu">
            <h6 class="dropdown-header" v-if="channel.subtitle">{{ channel.subtitle }}</h6>
            <button class="dropdown-item"
                    :class="this.getToggleClass(channel)"
                    type="button"
                    @click="$emit('toggle')">{{ this.getCaption(channel) }}</button>
            <div class="dropdown-divider" v-if="channel.previewing"></div>
            <a class="dropdown-item" v-if="channel.previewing"
               target="_blank" :href="channel.previewing">{{ $root.$gettext('subscriptions.preview') }}</a>
        </div>
    </div>
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
        getCaption(channel) {
            return channel.status ?
                this.$root.$gettext('subscriptions.unsubscribe') :
                this.$root.$gettext('subscriptions.subscribe');
        },
        getToggleClass(channel) {
            return channel.forced ? 'disabled' : '';
        },
        getColor(channel) {
            if (channel.available) {
                if (channel.status) {
                    return 'btn-primary';
                } else {
                    return 'btn-secondary';
                }
            } else {
                return 'btn-danger';
            }
        },
        /**
         * Get title.
         *
         * @param {Object} channel
         * @returns {string}
         */
        getTitle(channel) {
            if (channel.available) {
                if (channel.forced) {
                    return channel.status ?
                        this.$root.$gettext('subscriptions.forceSubscribed') :
                        this.$root.$gettext('subscriptions.forceUnsubscribed');
                } else {
                    return channel.status ?
                        this.$root.$gettext('subscriptions.subscribed') :
                        this.$root.$gettext('subscriptions.unsubscribed');
                }
            } else {
                return this.$root.$gettext('subscriptions.unavailableChannel');
            }
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
