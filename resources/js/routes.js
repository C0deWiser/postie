export default [
    { path: '/', redirect: '/subscriptions' },
    {
        path: '/subscriptions',
        name: 'subscriptions',
        component: require('./screens/subscriptions/index').default,
    },
    {
        path: '/configure',
        name: 'configure',
        component: require('./screens/configure/index').default,
    },
];
