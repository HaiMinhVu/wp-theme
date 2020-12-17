<template>
	<section>
		<div class="product-list-row col-12">
			<div class="lds-ring"></div>
			<div class="form-group col-md-6">
		      <input type="text" class="form-control" v-model="searchQuery" placeholder="Search">
    		</div>
    		<section v-if="hasProducts">
				<div class="row">
					<div class="col-md-4 col-sm-6" v-for="product in productsInView">
						<ProductCard :key="product.nsid" :product="product" :show-price="showPrices" />
					</div>
				</div>
				<br />
				<nav v-if="showPagination">
					<ul class="pagination justify-content-center">
						<li class="page-item" v-if="canPressPrev"><a class="page-link" @click="prevPage">Previous</a></li>
						<li :class="['page-item', { active: page == paginationPage }]" v-for="paginationPage in paginationRange">
							<a class="page-link" @click="setPage(paginationPage)">{{ paginationPage }}</a>
						</li>
						<li class="page-item" v-if="canPressNext"><a class="page-link" @click="nextPage">Next</a></li>
					</ul>
				</nav>
			</section>
			<section v-else>
				<div class="no-matching-text">
					<h3 v-if="productsLoaded">No products found matching "{{ searchQuery }}"</h3>
					<div v-else class="lds-ring">
						<div></div>
						<div></div>
						<div></div>
					</div>
				</div>
			</section>
		</div>
	</section>
</template>

<script>
	import axios from 'axios';
	import ProductCard from './product-card.vue';
	import { filter, max, min, range, slice, sortBy } from 'lodash';

	const perPage = 12;
	const paginationRangeCount = 5;
	const meanPaginationRange = (1 + paginationRangeCount) / 2;

	export default {
		props: {
			query: '',
			showPrices: {
				type: Boolean,
				default: true
			}
		},
		created() {
			axios.get('/wp-json/slmk/products').then(res => {
				this.products = JSON.parse(res.data);
				this.productsLoaded = true;
			});
			this.searchQuery = this.query;
		},
		data() {
			return {
				page: 1,
				products: [],
				productsLoaded: false,
				searchQuery: null
			}
		},
		methods: {
			nextPage() {
				const page = (this.page < this.totalPages) ? this.page+1 : this.page;
				this.setPage(page);
			},
			prevPage() {
				const page = (this.page > 1) ? this.page-1 : this.page;
				this.setPage(page);
			},
			setPage(page) {
				this.page = page;
				this.resetPosition();
			},
			resetPosition() {
				window.scrollTo({
				  top: 0,
				  behavior: 'smooth',
				  duration: 100
				});
			},
			productMatchesQuery(product, fields = ['name']) {
				for(let field of fields) {
					if(product[field].toLowerCase().includes(this.searchQueryLowerCase)) {
						return true;
					}
				}
				return false;
			}
		},
		computed: {
			filteredProducts() {
				return filter(this.products, product => {
					const hasPrice = product.price;
					const hasName = product.name.length > 0;
					const matchesQuery = this.productMatchesQuery(product, ['name', 'sku', 'feature_name']);
					return hasPrice && hasName && matchesQuery;
				});
			},
			searchQueryLowerCase() {
				return this.searchQuery.toLowerCase();
			},
            productsInView() {
                return slice(this.filteredProducts, this.startSlice, this.startSlice+perPage);
            },
            startSlice() {
            	return (this.page * perPage) - perPage;
            },
            totalPages() {
            	return Math.ceil(this.filteredProducts.length / perPage);
            },
            meanPaginationRange() {
            	return meanPaginationRange;
            },
            paginationRange() {
            	let start = (this.page < meanPaginationRange) ? 1 : this.page - 2;
            	if(start > (this.totalPages - paginationRangeCount)+1) start = this.totalPages - (paginationRangeCount-1);
            	const ranged = range(start, (start+paginationRangeCount));
            	return ranged.filter(r => r > 0);
            },
            showPagination() {
            	return this.filteredProducts.length > perPage;
            },
            canPressNext() {
            	return this.page < this.totalPages;
            },
            canPressPrev() {
            	return this.page > 1;
            },
            hasProducts() {
            	return this.filteredProducts.length > 0;
            }
        },
        watch: {
        	searchQuery() {
        		this.page = 1;
        	}
        },
		components: {
			ProductCard
		}
	}
</script>
