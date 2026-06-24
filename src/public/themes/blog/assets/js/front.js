/*global $, document, Chart, LINECHART, data, options, window, setTimeout*/
$(document).ready(function () {

    'use strict';

    // ------------------------------------------------------- //
    // For demo purposes only
    // ------------------------------------------------------ //

    var stylesheet = $('link#theme-stylesheet');
    $( "<link id='new-stylesheet' rel='stylesheet'>" ).insertAfter(stylesheet);
    var alternateColour = $('link#new-stylesheet');

    var themeCookie = $.cookie("theme_csspath");
    if (themeCookie && themeCookie.indexOf('css/style.') === 0 && themeCookie.indexOf('..') === -1) {
        alternateColour.attr("href", themeCookie);
    }

    $("#colour").change(function () {

        if ($(this).val() !== '') {

            var theme_csspath = 'css/style.' + $(this).val() + '.css';

            alternateColour.attr("href", theme_csspath);

            var cookiePath = window.location.pathname.substr(0, window.location.pathname.lastIndexOf('/')) || '/';
            $.cookie("theme_csspath", theme_csspath, { expires: 30, path: cookiePath });

        }

        return false;
    });


    // ------------------------------------------------------- //
    // Equalixe height
    // ------------------------------------------------------ //
    function equalizeHeight(x, y) {
        var textHeight = $(x).height();
        $(y).css('min-height', textHeight);
    }
    equalizeHeight('.featured-posts .text', '.featured-posts .image');

    $(window).resize(function () {
        equalizeHeight('.featured-posts .text', '.featured-posts .image');
    });


    // ---------------------------------------------- //
    // Preventing URL update on navigation link click
    // ---------------------------------------------- //
    $('.link-scroll').bind('click', function (e) {
        var href = this.getAttribute('href');
        if (href && href.charAt(0) === '#') {
            var $target = $(href);
            if ($target.length) {
                $('html, body').stop().animate({
                    scrollTop: $target.offset().top + 2
                }, 700);
            }
        }
        e.preventDefault();
    });


    // ---------------------------------------------- //
    // FancyBox
    // ---------------------------------------------- //
    $("[data-fancybox]").fancybox();


    // ---------------------------------------------- //
    // Divider Section Parallax Background
    // ---------------------------------------------- //
    $(window).on('scroll', function () {

        var scroll = $(this).scrollTop();

        if ($(window).width() > 1250) {
            $('section.divider').css({
                'background-position': 'left -' + scroll / 8 + 'px'
            });
        } else {
            $('section.divider').css({
                'background-position': 'center bottom'
            });
        }
    });


    // ---------------------------------------------- //
    // Search Bar
    // ---------------------------------------------- //
    $('.search-btn').on('click', function (e) {
        e.preventDefault();
        $('.search-area').fadeIn();
    });
    $('.search-area .close-btn').on('click', function () {
        $('.search-area').fadeOut();
    });



    // ---------------------------------------------- //
    // Navbar Toggle Button
    // ---------------------------------------------- //
    $('.navbar-toggler').on('click', function () {
        $('.navbar-toggler').toggleClass('active');
    });

});
