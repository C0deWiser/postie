export default [
    { path: '/', redirect: '/subscriptions' },

    {
        path: '/subscriptions',
        name: 'subscriptions',
        component: require('./screens/subscriptions/index').default,
    },

];
