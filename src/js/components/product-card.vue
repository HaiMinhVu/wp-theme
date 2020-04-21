<template>
    <a class="card product-card ng-star-inserted item" :data-nsid="product.nsid" :href="product.url">
        <div class="image">
            <img alt="" class="img-fluid" style="width:auto" :src="product.image">
        </div>
        <div class="card-body text-center">
            <p class="card-title">{{ product.name }}</p>
            <p class="sub-title">{{ product.sku }}</p>
            <p class="status" v-html="product.in_stock_html"></p>
            <p class="price">{{ product.price }}</p>
        </div>
    </a>
</template>

<script>
    export default {
        props: ['product'],
        methods: {
            inStock(stockStatus) {
                return stockStatus;
            }
        }
    }
</script>