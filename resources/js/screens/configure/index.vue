<template>
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h5>{{ $root.$gettext('configure.title') }}</h5>
        </div>
        <div class="card-body">
            <p>{{ $root.$gettext('configure.description') }}</p>

            <ul class="list-group">
                <li class="list-group-item d-flex justify-content-between align-items-center"
                    v-for="channel in Postie.unconfigured"
                    :key="channel.name">
                    <span class="d-flex align-items-center">
                        <i :class="channel.icon" class="mr-2"></i>
                        {{ channel.title }}
                    </span>

                    <a :href="channel.router"
                       target="_blank"
                       class="btn btn-sm btn-primary">{{ $root.$gettext('configure.configure') }}</a>
                </li>
            </ul>
        </div>
    </div>
</template>

<script>
export default {
    mounted() {
        document.title = "Postie - " + this.$root.$gettext('configure.title');
    },
};
</script>