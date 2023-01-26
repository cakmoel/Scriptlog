/**
 * Template name: Sina-nav Multi Purpose Menu
 * Template URI: https://github.com/shaonsina/sina-nav-4
 * Version: 2.2
 * Author: shaonsina
 */

(function ($) {
	'use strict';
	$.fn.sinaNav = function () {
		return this.each( function() {
			let getNav		= $(this),
				top 		= getNav.data('top') || getNav.offset().top,
				mdTop 		= getNav.data('md-top') || getNav.offset().top,
				xlTop 		= getNav.data('xl-top') || getNav.offset().top,
				navigation 	= getNav.find('.sina-menu'),
				getWindow 	= $(window).outerWidth(),
				anim 		= getNav.data('animate-prefix') || '',
				getIn 		= navigation.data('in'),
				getOut 		= navigation.data('out');

				$(window).on('resize', function(){
					getWindow 	= $(window).outerWidth();
				});

			// Active Class Add & Remove 
			// ---------------------------
			getNav.find('.sina-menu').each(function(){
				let $menu = $(this);
				$menu.on('click', function(e) {
					if ( 'A' == e.target.tagName ) {
						$menu.find('li.active').removeClass('active');
						$(e.target).parent().addClass('active');
					}
				});

				$menu.find('li.active').removeClass('active');
				$menu.find( 'a[href="'+ location.href +'"]' ).parent('li').addClass('active');
			});

			// Navbar Center 
			// ---------------------------------
			if( getNav.hasClass('logo-center')){
				let mainNav		= getNav.find('.sina-menu'),
					rightNav 	= mainNav.clone(),
					lists 		= mainNav.children('li'),
					divided 	= Math.round(lists.length / 2);

				// Remove All list item for newly creation
				mainNav.empty();
				rightNav.empty();

				// Create left part
				for (let i = 0; i < divided; i++) {
					mainNav.append( lists[i] );
				}
				mainNav.addClass('sina-menu-right').wrap('<div class="col-half left"></div>');

				// Create right part
				for (let i = divided; i < lists.length; i++) {
					rightNav.append( lists[i] );
				}
				getNav.find('.col-half.left').after( rightNav.addClass('sina-menu-dropdown-right sina-menu-left') );
				rightNav.wrap('<div class="col-half right"></div>');
			}

			// Mobile Sidebar
			// ---------------------------------
			if( getNav.hasClass('mobile-sidebar') ) {
				let $collapse = getNav.find('.navbar-collapse');

				// Add Class to body
				if ( $('body').children('.sina-nav-wrapper').length < 1 ) {
					$('body').wrapInner('<div class="sina-nav-wrapper"></div>');
				}

				if ( getNav.hasClass('navbar-reverse') ) {
					$collapse.on('shown.bs.collapse', function() {
						$('body').addClass('mobile-right');
					});
					$collapse.on('hide.bs.collapse', function() {
						$('body').removeClass('mobile-right');
					});
					$(window).on('resize', function(){
						$('body').removeClass('mobile-right');
						getNav.find('.navbar-collapse').removeClass('show');
						getNav.find('.navbar-toggle .fa', this).removeClass('fa-times').addClass('fa-bars');
					});
				}
				else{
					$collapse.on('shown.bs.collapse', function() {
						$('body').addClass('mobile-left');
					});
					$collapse.on('hide.bs.collapse', function() {
						$('body').removeClass('mobile-left');
					});
					$(window).on('resize', function(){
						$('body').removeClass('mobile-left');
						getNav.find('.navbar-collapse').removeClass('show');
						getNav.find('.navbar-toggle .fa', this).removeClass('fa-times').addClass('fa-bars');
					});
				}
			}

			// Navbar Fixed
			// ---------------------------------
			function freezNav() {
				let scrollTop = $(window).scrollTop(),
					winWidth  = $(window).outerWidth();

				if( winWidth > 1599 && scrollTop > xlTop ){
					getNav.addClass('navbar-freez');
				}
				else if( winWidth < 1600 && winWidth > 1199 && scrollTop > top ){
					getNav.addClass('navbar-freez');
				}
				else if( winWidth < 1200 && winWidth > 1024 && scrollTop > mdTop ){
					getNav.addClass('navbar-freez');
				}
				else {
					getNav.removeClass('navbar-freez');
				}
			}
			if( getNav.hasClass('navbar-fixed') ){

				$(window).on('scroll', function(){
					freezNav();
				});
				$(window).on('resize', function(){
					freezNav();
				});

				if ( getWindow > 1024 && $(window).scrollTop() > top ) {
					getNav.addClass('navbar-freez');
				}
			}

			// Navbar Transparent
			// ---------------------------------
			function transNav() {
				let scrollTop = $(window).scrollTop(),
					winWidth  = $(window).outerWidth();

				if( winWidth > 1599 && scrollTop > xlTop ){
					getNav.removeClass('navbar-transparent');
				}
				else if( winWidth < 1600 && winWidth > 1199 && scrollTop > top ){
					getNav.removeClass('navbar-transparent');
				}
				else if( winWidth < 1200 && winWidth > 1024 && scrollTop > mdTop ){
					getNav.removeClass('navbar-transparent');
				}
				else {
					getNav.addClass('navbar-transparent');
				}
			}
			if( getNav.hasClass('navbar-transparent') ){

				$(window).on('scroll', function(){
					transNav();
				});
				$(window).on('resize', function(){
					transNav();
				});

				if ( getWindow > 1024 && $(window).scrollTop() > top ) {
					getNav.removeClass('navbar-transparent');
				}
			}

			// Search-box
			// ---------------------------------
			getNav.find('.extension-nav').each(function(){
				$('.search > a', this).on('click', function(e){
					e.preventDefault();
					$('.search-box').slideToggle();
				});
			});
			$('.search-addon.close-search').on('click', function(){
				$('.search-box').slideUp();
			});

			// Widget-bar
			// ---------------------------------
			getNav.find('.extension-nav').each(function(){
				$('.widget-bar-btn > a', this).on('click', function(e){
					e.preventDefault();
					getNav.children('.sina-nav-overlay').fadeIn(400);
					getNav.children('.widget-bar').toggleClass('on');
				});
			});
			getNav.find('.widget-bar .close-widget-bar').on('click', function(e){
				e.preventDefault();
				getNav.children('.widget-bar').removeClass('on');
				getNav.children('.sina-nav-overlay').fadeOut(600);
			});
			getNav.children('.sina-nav-overlay').on('click', function() {
				getNav.children('.widget-bar').removeClass('on');
				$(this).fadeOut(600);
			});

			// Toggle Button
			getNav.find('.navbar-toggle').on('click', function(){
				$('.fa', this).toggleClass('fa-bars').toggleClass('fa-times');
			});


			// Eevent
			// -------------------------------------
			getNav.find('.sina-menu, .extension-nav').each(function(){
				let menu = this;

				$('.dropdown-toggle', menu).on('click', function (e) {
					e.stopPropagation();
					return false;
				});

				$('.dropdown-menu', menu).addClass(anim+'animated');
				$('.dropdown', menu).on('mouseenter', function(){
					let dropdown = this;

					$('.dropdown-menu', dropdown).eq(0).removeClass(getOut).stop().fadeIn().addClass(getIn);
					$(dropdown).addClass('on');
				});
				$('.dropdown', menu).on('mouseleave', function(){
					let dropdown = this;

					$('.dropdown-menu', dropdown).eq(0).removeClass(getIn).stop().fadeOut().addClass(getOut);
					$(dropdown).removeClass('on');
				});

				$('.dropdown', menu).on('focusin', function(){
					let dropdown = this;

					$('.dropdown-menu', dropdown).eq(0).removeClass(getOut).stop().fadeIn().addClass(getIn);
					$(dropdown).addClass('on');
				});
				$('.dropdown', menu).on('focusout', function(){
					let dropdown = this;

					$('.dropdown-menu', dropdown).eq(0).removeClass(getIn).stop().fadeOut().addClass(getOut);
					$(dropdown).removeClass('on');
				});
				$('.mega-menu-col', menu).children('.sub-menu').removeClass('dropdown-menu '+anim+'animated');
			});

			if( getWindow < 1025 ) {
				// Megamenu
				getNav.find('.menu-item-has-mega-menu').each(function(){
					let megamenu 	= this,
						$columnMenus = [];

					$('.mega-menu-col', megamenu).children('.sub-menu').addClass('dropdown-menu '+anim+'animated');
					$('.mega-menu-col', megamenu).each(function(){
						let megamenuColumn = this;

						$('.mega-menu-col-title', megamenuColumn).on('mouseenter', function(){
							let title = this;

							$(title).closest('.mega-menu-col').addClass('on');
							$(title).siblings('.sub-menu').stop().fadeIn().addClass(getIn);
						});

						if( !$(megamenuColumn).children().is('.mega-menu-col-title') ) {
							$columnMenus.push( $(megamenuColumn).children('.sub-menu') );
						}
					});

					$(megamenu).on('mouseenter', function(){
						let submenu;
						for (submenu in $columnMenus) {
							$columnMenus[ submenu ].stop().fadeIn().addClass(getIn);
						}	
					});

					$(megamenu).on('mouseleave', function() {
						$('.dropdown-menu', megamenu).stop().fadeOut().removeClass(getIn);
						$('.mega-menu-col', megamenu).removeClass('on');
					});
				});
			}
		});
	}
	// Initialize
	$('.sina-nav').sinaNav();
}(jQuery));