import Vue from 'vue';
import Base from './base';
import axios from 'axios';
import qs from 'qs';
import Routes from './routes';
import VueRouter from 'vue-router';
import VueJsonPretty from 'vue-json-pretty';

window.Popper = require('popper.js').default;

try {
    window.$ = window.jQuery = require('jquery');

    require('bootstrap');
} catch (e) {}

let token = document.head.querySelector('meta[name="csrf-token"]');

axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

if (token) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
}

Vue.use(VueRouter);

Vue.prototype.$http = axios.create();

window.Postie.basePath = '/' + window.Postie.path;

let routerBasePath = window.Postie.basePath + '/';

if (window.Postie.path === '' || window.Postie.path === '/') {
    routerBasePath = '/';
    window.Postie.basePath = '';
}

const router = new VueRouter({
    routes: Routes,
    mode: 'history',
    base: routerBasePath,
    stringifyQuery  : query => {
        let result = qs.stringify(query)
        return result ? ('?' + result) : ''
    },
});

Vue.component('vue-json-pretty', VueJsonPretty);
Vue.component('alert', require('./components/Alert.vue').default);
Vue.component('channel-btn', require('./components/ChannelBtn').default);

Vue.mixin(Base);

new Vue({
    el: '#postie',

    router,

    data() {
        return {
            alert: {
                type: null,
                autoClose: 0,
                message: '',
                confirmationProceed: null,
                confirmationCancel: null,
            },
        };
    }
});
