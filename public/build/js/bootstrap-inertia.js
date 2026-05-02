import axios from 'axios';

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

const tokenMeta = document.head.querySelector('meta[name="csrf-token"]');
if (tokenMeta) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = tokenMeta.content;
}
