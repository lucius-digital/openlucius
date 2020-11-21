var myEfficientFn = debounce(function() {
	initBlockHeight();
}, 250);

jQuery(window).on('load resize orientationchange', myEfficientFn)

function initBlockHeight(){
    var formHeight = jQuery('.stream-item-form').height();
    var navBarHeading = jQuery('.load-previous-form').height();
    var headingMessage = jQuery('.container-fluid .heading').height();
    var headingImg = jQuery('.header-image').height();
    var messagesArea = jQuery('.messages').height();
    var removeHeight = formHeight + navBarHeading ;
    if (headingMessage) {
      removeHeight = removeHeight + headingMessage;
    }
    if (headingImg) {
      removeHeight = removeHeight + headingImg;
    }
    if (messagesArea) {
      removeHeight = removeHeight + messagesArea;
    }
    console.log('hoit' +messagesArea);
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
