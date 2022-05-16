export default [
    { path: '/', redirect: '/subs' },

    {
        path: '/subs',
        name: 'subs',
        component: require('./screens/subs/index').default,
    },

];
