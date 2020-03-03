import $ from 'jquery';
import 'slick-carousel';
import 'jquery-zoom';
import 'slick-carousel';

window.$ = window.JQuery = $;

$( document ).ready( function( $ ) {

    $('.owl-carousel-default').on('init', function(){
        setCardHeight();
    })

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

    setContentPadding();
    $(window).on('load resize', function(){
        setContentPadding();
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

    if($('body').hasClass('product-page')) {
        $('#tab-links .nav-link').click(function(e){
            $('#tab-links .nav-link').removeClass('active');
            $(this).addClass('active');
            $('#tab-content .tab-pane').hide();
            $($(this).data('content')).fadeIn();
        });

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

        loadZoom();

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

        $("#add-to-cart").click(function(e){
            e.preventDefault();
            const productName = $(this).data('name');
            const $cartButtons = $('.cart-action-buttons');
            $cartButtons.prop('disabled', true);
            const data = new URLSearchParams({
                buyid: $(this).data('nsid'),
                qty: 1,
                c: 1247539
            }).toString();
            $.ajax({
                type: "POST",
                url: `https://checkout.na1.netsuite.com/app/site/query/additemtocart.nl?${data}`,
                contentType: 'text/html',
                crossDomain: true,
                dataType: "jsonp",
                complete: function() {
                    reloadShoppingCart();
                    $cartButtons.removeAttr('disabled');
                    alertBottom(productName, 'Added to cart');
                }
            });
        });

        $("#buy-now").click(function(){
            window.open("https://www.sellmarknexus.com/checkout/cart.ssp?ext=T&amp;whence=&amp;sc=3#cart", "_blank");
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

    // Temporary
    $('.dropdown-toggle').click(e => e.preventDefault());

    $('#overlay-menu .has-children').click(function(){
        $(this).siblings().removeClass('show-children');
        $(this).toggleClass('show-children');
    });

});
