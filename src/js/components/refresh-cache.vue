<template>
    <div class="refresh-container" v-if="isValidType">
        <button :class="['btn', 'btn-dark', { 'disabled': disabled }]" @click="refresh"><i class="fa fa-refresh"></i> {{ buttonTitle }}</button>
    </div>
</template>

<script>
    import axios from 'axios';

    export default {
        props: {
            itemId: null,
            type: null
        },
        data() {
            return {
                loading: false
            }
        },
        methods: {
            async refresh() {
                if(this.canRefresh) {
                    this.loading = true;
                    await axios.get(this.url);
                    this.loading = false;
                    window.location.reload();
                }
            }
        },
        computed: {
            buttonTitle() {
                return `Refresh ${this.type} Cache`;
            },
            canRefresh() {
                return (this.itemId && this.urlPrefix) || (this.type == 'search' && this.urlPrefix);
            },
            disabled() {
                return (this.loading || !this.canRefresh);
            },
            isValidType() {
                return ['category', 'product', 'search'].includes(this.type);
            },
            url() {
                return this.itemId ? `${this.urlPrefix}/${this.itemId}` : this.urlPrefix;
            },
            urlPrefix() {
                switch (this.type) {
                  case 'category':
                    return '/wp-json/cache/v1/update/category';
                    break;
                  case 'product':
                    return '/wp-json/cache/v1/update/product';
                    break;
                  case 'search':
                    return '/wp-json/cache/v1/update/products';
                    break;
                  default:
                    return null;
                }
            }
        }
    }
</script>

<style scoped>
    .refresh-container {
        background-color: #ffffff;
        border-radius: 5px;
        bottom:0;
        position:fixed;
        right:0;
        z-index: 99999;
    }
</style>
