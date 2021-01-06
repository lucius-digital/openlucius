var myEfficientFn = debounce(function() {
	initBlockHeight();
}, 250);

jQuery(window).on('load resize orientationchange', myEfficientFn)

function initBlockHeight(){
    var formHeight = jQuery('.chat-item-form').height();
    var navBar = jQuery('#main-nav').height();
    var loadPreviousForm = jQuery('.load-previous-form').height();
    var titleSection = jQuery('#title-container').height();
    var messagesArea = jQuery('.messages').height();
    var removeHeight = navBar + formHeight + loadPreviousForm ;
    if (titleSection) {
      removeHeight = removeHeight + titleSection;
    }
    if (messagesArea) {
      removeHeight = removeHeight + messagesArea;
    }

    jQuery('.chat-list-holder').css('max-height', 'calc(100vh - 130px - ' + removeHeight + 'px)');
}

function debounce(func, wait, immediate) {
	var timeout;
	return function() {
		var context = this, args = arguments;
		var later = function() {
			timeout = null;
			if (!immediate) func.apply(context, args);
		};
		var callNow = immediate && !timeout;
		clearTimeout(timeout);
		timeout = setTimeout(later, wait);
		if (callNow) func.apply(context, args);
	};
};
