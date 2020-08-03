import $ from 'jquery';
import 'slick-carousel';
import 'jquery-zoom';
import Vue from 'vue/dist/vue.js';
import Search from './components/search.vue';
import SLMKForm from './classes/SLMKForm';
import axios from 'axios';
import Cookies from 'js-cookie';

axios.defaults.headers.common['Access-Control-Allow-Origin'] = '*';

const checkIP = async (cb) => {
    if(!Cookies.get('checked-ip')) {
        try {
            const { data } = await axios.get('https://ipinfo.io/');
            const res = await axios.get(`http://demo.ip-api.com/json/${data.ip}`);
            const canShowPrices = ['CA', 'US'].includes(res.data.countryCode);
            Cookies.set('show-prices', canShowPrices);
            Cookies.set('checked-ip', true);
        } catch(e) {
            Cookies.set('show-prices', true);
            Cookies.set('checked-ip', false);
        }
    }
    cb();
}

const checkIfPurchasable = () => {
    if(Cookies.get('show-prices') == true) {
        document.body.classList.add('cannot-purchase');
    }
}

Vue.component('search', Search);

window.$ = window.JQuery = $;
window.SLMKForm = SLMKForm;
window.initSearch = () => {
    new Vue({ el: '#search' });
}

$( document ).ready( function( $ ) {

    // checkIP(function(){
    //     checkIfPurchasable();
    // });

    $('.owl-carousel-default').on('init', function(){
        setCardHeight();
    });

    $('.owl-carousel-default').slick({
        slidesToShow: 3,
        slidesToScroll: 1,
        autoplay: true,
        autoplaySpeed: 2000,
        responsive: [
            {
                breakpoint: 768,
                settings: {
                    slidesToShow: 1,
                    slidesToScroll: 1,
                }
            },
            {
                breakpoint: 1024,
                settings: {
                    slidesToShow: 2,
                    slidesToScroll: 2,
                }
            }
        ]
    });

    $('#hamburger-menu').click(function(){
        $('#overlay-menu .has-children').removeClass('show-children');
        $('body').toggleClass('mobile-menu-visible');
    });

    // setContentPadding();
    $(window).on('load resize', function(){
        // setContentPadding();
    });

    function setContentPadding() {
        $('#content').css('padding-top', $('#myHeader').outerHeight());
    }

    function setCardHeight() {
        let maxHeight = 0;
        $('.slick-slide').each(function(key, e){
            let cardHeight = $(this).height();
            maxHeight = (cardHeight > maxHeight) ? cardHeight : maxHeight;
        });
        $('.slick-slide .card-product').height(maxHeight);
    }

    $('#tns').slick({
        slidesToShow: 1,
        draggable: true,
        autoplay: true,
        autoplaySpeed: 4000,
    });

    document.getElementById('shopping-counter-content').onload = function(){
        updateCount();
    }

    function updateCount() {
        const shippingCounterEl = $('#shopping-counter-content')[0];
        const count = shippingCounterEl.contentWindow.document.body.innerText;
        if(parseInt(count) > 0) {
            $('#shopping-counter').text(count).show();
        }
    }

    $('.slic').click(function(){
        alert('test');

    });

    if($('body').hasClass('product-page')) {
        $('#tab-links .nav-link').click(function(e){
            $('#tab-links .nav-link').removeClass('active');
            $(this).addClass('active');
            $('#tab-content .tab-pane').hide();
            $($(this).data('content')).fadeIn();
        });

        function loadThumbnailClick() {
            $('.img-thumbnail-insert').click(function(){
                const $enlargedImg = $('#enlarged-image');
                const $zoomContainer = $('#zoom-container');
                const $loadingContainer = $('#enlarged-image-loading');

                $zoomContainer.hide();
                $loadingContainer.show();

                $enlargedImg.attr('src', $(this).data('img-src'));
                $enlargedImg[0].onload = function(e) {
                    loadZoom();
                    $loadingContainer.hide();
                    $zoomContainer.fadeIn(200);
                }
            });
        }
        loadZoom();
        loadThumbnailClick();

        function loadZoom() {
            if(window.innerWidth >= 768) {
                $('#enlarged-image').trigger('zoom.destroy');
                $('#enlarged-image')
                .wrap('<span style="display:inline-block"></span>')
                .css('display', 'block')
                .parent()
                .zoom({
                    url: $(this).attr('src'),
                    onZoomIn: function(e) {
                        const $imgContainer = $('#enlarged-image-container');
                        const $parent = $('#enlarged-image').parent();
                        $parent.height($imgContainer.height());
                        $('#zoom-container').height('100%');
                    },
                    onZoomOut: function(e) {
                        $('#enlarged-image').parent().css('height', '');
                        $('#zoom-container').css('height', '');
                    }
                });
            }
            $('#enlarged-image-loading').hide();
        }

        function reloadShoppingCart() {
            document.getElementById('shopping-counter-content').contentWindow.document.location.reload();
        }

        function addToCart(nsid, checkoutURL = 'https://checkout.netsuite.com/app/site/query/additemtocart.nl?qty=1&c=1247539&buyid=', cb) {
            const $cartButtons = $('.cart-action-buttons');
            $cartButtons.prop('disabled', true);
            $.ajax({
                type: "POST",
                url: `${checkoutURL}${nsid}`,
                contentType: 'text/html',
                crossDomain: true,
                dataType: "jsonp",
                complete: function() {
                    reloadShoppingCart();
                    $cartButtons.removeAttr('disabled');
                    cb();
                }
            });
        }

        $("#add-to-cart").click(function(e){
            e.preventDefault();
            const nsid = $(this).data('nsid');
            const productName = $(this).data('name');
            const checkoutURL = $(this).parent().data('checkout-url');
            addToCart(nsid, checkoutURL, function(){
                alertBottom(productName, 'Added to cart');
            });
        });

        $("#buy-now").click(function(e){
            e.preventDefault();
            const nsid = $(this).data('nsid');
            const checkoutURL = $(this).parent().data('checkout-url');
            const viewCartURL = $(this).parent().data('view-cart-url');
            addToCart(nsid, checkoutURL, function(){
                window.open(viewCartURL, "_blank");
            });
        });

        $('.related-items-carousel.active').slick({
            slidesToShow: 4,
            slidesToScroll: 1,
            autoplay: true,
            autoplaySpeed: 3000,
            responsive: [
                {
                    breakpoint: 768,
                    settings: {
                        slidesToShow: 1,
                        slidesToScroll: 1,
                    }
                },
                {
                    breakpoint: 1000,
                    settings: {
                        slidesToShow: 2,
                        slidesToScroll: 2,
                    }
                }
            ]
        });

        const responsiveSettings = {
            vertical: false,
            prevArrow: '<span class="fa fa-caret-left icon-control"></span>',
            nextArrow: '<span class="fa fa-caret-right icon-control"></span>'
        };

        const $thumbnailList = $('.thumbnail-list');

        $thumbnailList.on('init', function(event, slick, direction){
            loadThumbnailClick();
        });

        if($thumbnailList.find('.img-thumbnail-insert').length > 2) {
            $thumbnailList.slick({
                vertical:true,
                slidesToShow: 5,
                arrows: true,
                prevArrow: '<span class="fa fa-caret-up icon-control"></span>',
                nextArrow: '<span class="fa fa-caret-down icon-control"></span>',
                responsive: [
                    {
                        breakpoint: 768,
                        settings: {
                            slidesToShow: 4,
                            ...responsiveSettings
                        }
                    },
                    {
                        breakpoint: 768,
                        settings: {
                            slidesToShow: 4,
                            ...responsiveSettings
                        }
                    },
                    {
                        breakpoint: 479,
                        settings: {
                            slidesToShow: 3,
                            ...responsiveSettings
                        }
                    }
                ]
            });
        }

        $('#alert-bottom .close').click(function(){
            closeAlertBottom();
        });

        function closeAlertBottom(){
            $('#alert-bottom').removeClass('active');
            setTimeout(function(){
                $('#alert-bottom .alert-bottom-text').text('');
            }, 250);
        }

        function alertBottom(text1, text2 = null) {
            $('#alert-bottom-text1').text(text1);
            $('#alert-bottom-text2').text(text2);
            $('#alert-bottom').addClass('active');
            setTimeout(function(){
                closeAlertBottom();
            }, 3500);
        }

    }

    $('.search-toggle').click(e => {
        $('#search-bar').toggleClass('active');
    });

    // $('body').scroll()

    if (
        "IntersectionObserver" in window &&
        "IntersectionObserverEntry" in window &&
        "intersectionRatio" in window.IntersectionObserverEntry.prototype
    ) {
        let observer = new IntersectionObserver(entries => {
            if (entries[0].boundingClientRect.y < 0) {
                document.getElementById('main_navigation').classList.add("reduced");
            } else {
                document.getElementById('main_navigation').classList.remove("reduced");
            }
        });
        observer.observe(document.getElementById('scroll-anchor'));
    }

    // Temporary
    // $('.dropdown-toggle').click(e => e.preventDefault());

    $('#overlay-menu .has-children').click(function(){
        $(this).siblings().removeClass('show-children');
        $(this).toggleClass('show-children');
    });

});
