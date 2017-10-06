jQuery(function($){

'use strict';

var WFC = window.WFC || {};

/* =============================================================================
   WFC NAVIGATION
   ========================================================================== */

WFC.navigation = function(){
	$('#collapse').click(function(e) {
	  setTimeout(function(){
       $('.dropdown > ul').toggleClass('open');
	   $('.dropdown li ul').removeClass('open');
	   $('.dropdown li').removeClass('active');
  	  }, 0);
	  return false;
    });
	
	$('body').waypoint(function() {
	  $('#brand-nav').toggleClass('stowed');
	  $('#collapsed-nav').toggleClass('shown');
	  if($(window).width() > 767) {
	  	$('.dropdown > ul').removeClass('open');
	  }
	}, { offset: -400 });
	
	
};

/* =============================================================================
   WFC CAROUSEL
   ========================================================================== */

WFC.carousel = function(){
	  
	$('.owl-carousel').owlCarousel({
		loop:true,
		margin:0,
		nav:true,
		navText: [ '<i class="fa fa-angle-left"></i>', '<i class="fa fa-angle-right"></i>' ],
		items:1,
        autoplay:true,
        autoplayTimeout:4000,
        autoplayHoverPause:true
	});
	
	$('.home #hero').slideDown();
	
};

/* =============================================================================
   WFC SEARCH TOGGLE
   ========================================================================== */

WFC.searchToggle = function(){
	
	$('li.search a').click(function(e) {
	  $('li.search .form-group').toggleClass('open');
	  $('li.search .form-group input[type="text"]').focus();
	  return false;
    });
	
	$('html').click(function() {
		$('.form-group').removeClass('open');
	});
	
	$('.form-group').click(function(event){
		event.stopPropagation();
	});
};

/* =============================================================================
   WFC ANIMATE PAGE ANCHORS
   ========================================================================== */

WFC.pageAnchor = function(){
	$('body').on('click', '.page-anchor', function () {
        var $anchor = $(this);
        $('html, body').stop().animate({
            scrollTop: $($anchor.attr('href')).offset().top - 100
        }, 1500, 'easeInOutExpo');
        event.preventDefault();
    });
};

/* =============================================================================
   WFC SOCIAL FEEDS
   ========================================================================== */

WFC.socialFeeds = function(){
	
	
	$("#social-stream li").each(function(i) {
		  $(this).fadeTo(0,0)
		});
	
	
	
		var shortText = '';
		var content = '';
		
		$(".dcsns-facebook").each(function() {
			
			if ($(".section-text",this).text().length > 100) {
				if ($(".fb-thumb",this).length > 0) {
					$(".fb-thumb",this).parent().addClass('inc-img');
					shortText = $.trim($(".section-text",this).text()).substring(0, 50).split(" ").slice(0, -1).join(" ") + "...";
					$(".section-text",this).empty().text(shortText);
				} else {
					shortText = $.trim($(".section-text",this).text()).substring(0, 80).split(" ").slice(0, -1).join(" ") + "...";
					$(".section-text",this).empty().text(shortText);
				}
			}
		});
	
	

	$('#social-stream').slideDown();
		
		$("#social-stream li").each(function(i) {
		  $(this).delay(i*250).fadeTo(750,1);
		});
		
		//external links to new window
		$('#social-stream a').each(function() {
		   var a = new RegExp('/' + window.location.host + '/');
		   if(!a.test(this.href)) {
			   $(this).click(function(event) {
				   event.preventDefault();
				   event.stopPropagation();
				   window.open(this.href, '_blank');
			   });
		   }
		});
	
}

/* =============================================================================
   WFC FORM MANNERS
   ========================================================================== */

WFC.formManners = function(){
	
	//pretty file upload
	var wrapper = $('<div/>').css({height:0,width:0,'overflow':'hidden'});
	var fileInput = $('.recipe_thumbnail_image').wrap(wrapper);
	
	fileInput.change(function(){
		$('#file').text($(this).val().substr(12));
		if($(this).val()) {
			$('#filePicker').text('Change File');
		} else {
			$('#filePicker').text('Upload File');
		}
	})
	
	$('#filePicker').click(function(){
		fileInput.click();
	}).show();
	
	//custom ratings stars
	$('body').on('click', 'span.star', function () {
		
		$('.star.active').each(function() {
			$(this).removeClass('active');
		});
		
		var count = 1;
		var end = $(this).data('star');
		
		$('span.star').each(function() {
			
			if(count <= end) {
				$(this).addClass('active');
			}
			count++;
		});
	});
	
	//toggle user submit form
	$('.user-photo-link').click(function(){
		$('#submit-photo').stop().slideToggle(0);
		return false;
	});
}

/* =============================================================================
   WFC FLUID VIDEO
   ========================================================================== */

WFC.fluidVideo = function(){
	$("body").fitVids();
}


/* =============================================================================
   INIT
   ========================================================================== */
   
	$(document).ready(function(){
		WFC.navigation();
		WFC.searchToggle();
		WFC.carousel();
		WFC.pageAnchor();
		WFC.socialFeeds();
		WFC.formManners();
		WFC.fluidVideo();
	});
	
});

window.console.log('wtf');
