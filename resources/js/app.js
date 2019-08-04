import './bootstrap';
import Vue from 'vue';
import VueRouter from 'vue-router';
import SuiVue from 'semantic-ui-vue';
import routes from './routes'
import store from './store'

import MainMenu from './components/MainMenu.vue';
import StatsBar from './components/StatsBar.vue';
import SystemMenu from './components/System/Menu.vue';
import SystemGroups from './components/System/Groups.vue';
import SystemUsers from './components/System/Users.vue';
import PassportClients from './components/passport/Clients.vue';
import PassportAuthorizedClients from './components/passport/AuthorizedClients.vue';
import PassportAccessTokens from './components/passport/PersonalAccessTokens.vue';

window.Vue = Vue;

Vue.use(VueRouter);
Vue.use(SuiVue);

Vue.component('main-menu', MainMenu);
Vue.component('stats-bar', StatsBar);

Vue.component('system-menu', SystemMenu);
Vue.component('system-groups', SystemGroups);
Vue.component('system-users', SystemUsers);

Vue.component('passport-clients', PassportClients);
Vue.component('passport-authorized-clients', PassportAuthorizedClients);
Vue.component('passport-personal-access-tokens', PassportAccessTokens);

const router = new VueRouter({
    mode: 'history',
    routes
});

router.beforeEach((to, from, next) => {
    let authed = store.getters.loggedIn;

    if (!authed && to.matched.some(route => route.meta.auth)) {
        next({ name: 'login' });
    } else if (authed && to.matched.some(route => route.meta.guest)) {
        next({ name: 'dashboard' });
    } else {
        next();
    }
});

window.axios.interceptors.response.use(response => {
    return response;
}, error => {
    if (error.response.status === 401) {
        router.push({ name: 'login' });
    }

    return Promise.reject(error);
});

const app = new Vue({
    el: '#app',
    router,
    store
});
