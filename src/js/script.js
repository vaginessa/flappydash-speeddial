// TODO: use data-target instead of elementIndex

// is the DOM ready for manipulation?
document.addEventListener('DOMContentLoaded', function() {

	// show notification on keydown and hide it on keyup
	document.addEventListener('keydown', function() {
		var targetElement = 'notification';
		document.getElementById(targetElement).classList.add('js-visible');
		document.getElementById(targetElement).classList.remove('js-hidden');
	});
	document.addEventListener('keyup', function() {
		var targetElement = 'notification';
		document.getElementById(targetElement).classList.add('js-hidden');
		document.getElementById(targetElement).classList.remove('js-visible');
	});

	// per default localStorage is updated, when tabs are switched
	updateLocalStorageOnTabSwitch = true;

	// add JS to body-tag to allow CSS-Manipulation if JS is available
	function setJs() {
		document.getElementsByTagName("body")[0].className += "js";
	}

	// scroll to desired position
	function scrollToTarget(x,y) {
		window.scrollTo(x,y);
	}

	// make element sticky (via position in css)
	function stickyElement(stickyId,compensateId,compensateProperty) {
		var compensateElement = document.getElementById(compensateId);
		var stickyElement = document.getElementById(stickyId);
		var stickyHeight = stickyElement.clientHeight + 'px';
		stickyElement.classList.add('js-sticky');

		//add Element-Height as defined property to desired element
		compensateElement.style.setProperty(compensateProperty,stickyHeight);
	}

	// toggle Element
	function toggleElement(elementId,targetElementId) {
		toggleElement = document.getElementById(elementId);
		toggleElement.onclick = function() {
			targetElement = document.getElementById(targetElementId);
			if(targetElement.classList.contains('js-visible')) {
				targetElement.classList.add('js-hidden');
				targetElement.classList.remove('js-visible');
			} else {
				targetElement.classList.remove('js-hidden');
				targetElement.classList.add('js-visible');
			}
			event.preventDefault();
		}
	}

	// Overlay-Handling
	function handleOverlayTriggers(elementClassName) {
		var elements = document.getElementsByClassName(elementClassName);
		for(i=0; i < elements.length; i++) {
			elements[i].onclick = function(event) {
				var target = this.getAttribute('data-target');
				toggleOverlay(target,event);
			}
		}
	}
	function toggleOverlay(elementId,event) {
		var targetElement = document.getElementById(elementId);
		if(targetElement.classList.contains('js-visible')) {
			targetElement.classList.add('js-hidden');
			targetElement.classList.remove('js-visible');
		} else {
			targetElement.classList.remove('js-hidden');
			targetElement.classList.add('js-visible');
		}
		fixElement("content");
		event.preventDefault();
	}

	// Collapse-Handling
	function handleCollapseTriggers(elementClassName) {
		var elements = document.getElementsByClassName(elementClassName);
		for(i=0; i < elements.length; i++) {
			elements[i].onclick = function(event) {
				var target = this.getAttribute('data-target');
				toggleCollapse(target,event);
			}
		}
	}
	function toggleCollapse(elementId,event) {
		var targetElement = document.getElementById(elementId);
		if(targetElement.classList.contains('js-opened')) {
			targetElement.classList.add('js-closed');
			targetElement.classList.remove('js-opened');
		} else {
			targetElement.classList.remove('js-closed');
			targetElement.classList.add('js-opened');
		}
		event.preventDefault();
	}

	// Tab-Handling-Function
	// initially set Element-count of tabs and tabbedContent and check consistency
	var tabTrigger = document.getElementsByClassName("js-tab-trigger");
	var tabbedContent = document.getElementsByClassName("tabbed-content");
	if(tabTrigger.length !== tabbedContent.length) {
		console.log("count of tabs and tabbed-contend isn't consistent");
	}
	// -- set Current-Tab and tabbed-content
	var setCurrentTab = function(newTabId) {
		markTabTrigger = document.getElementsByClassName("js-tab-trigger")[newTabId].classList.add("active");
		unhideSelectedContent = document.getElementsByClassName("tabbed-content")[newTabId].classList.add("active");
		if(updateLocalStorageOnTabSwitch === false) {
			console.log("localStorage not updated");
		} else {
			localStorage.setItem("tabbedContentId", newTabId);
			console.log("localStorage ID is: " + newTabId);
		}

	}

	// -- unset Current-Tab and tabbed-conent
	var unsetTabs = function() {
		for(i=0; i < tabbedContent.length; i++) {
			tabTrigger[i].classList.remove("active");
			tabbedContent[i].classList.remove("active");
		}
	}

	// -- if DOM is ready, check if localStorage is filled and stored Id is still available
	var savedLocalStorageId = localStorage.getItem("tabbedContentId");
	if(savedLocalStorageId !== null && tabTrigger.length >= savedLocalStorageId) {
		setCurrentTab(savedLocalStorageId);
	} else {
		console.log("localStorage is empty or stored Id is no longer present - setting default");
		setCurrentTab(0);
	}

	// -- switch tabs onClick
	for(i=0; i < tabTrigger.length; i++) {
		(function(index){
			tabTrigger[i].onclick = function(event){
				if(event.altKey === true) {
					updateLocalStorageOnTabSwitch = false;
				} else {
					updateLocalStorageOnTabSwitch = true;
				}
				unsetTabs();
				setCurrentTab(index,updateLocalStorageOnTabSwitch);
				return false;
			}
		})(i);
	}

	// ---- fix element to current position
	function fixElement(elementId) {
		elementToFix = document.getElementById(elementId);
		scrollY = window.pageYOffset;

		if(elementToFix.classList.contains('js-fixed')) {
			elementToFix.classList.remove('js-fixed');
			elementToFix.style.top = '';
			scrollToTarget(0,scrollYMem);
		} else {
			elementToFix.classList.add('js-fixed');
			elementToFix.style.top = '-' + scrollY + 'px';
			scrollYMem = scrollY;
		}
		console.log(scrollYMem);
	}


	// ---- initialize ----
		// set Js on body if JS is available
		setJs();

		//sticky header (item(Id) to fix, item(Id) with property to compensate fix)
		function stickyElements() {
			stickyElement('application-header','content','padding-top');
			stickyElement('application-footer','content','padding-bottom');
		}

		stickyElements();
		window.onresize = stickyElements;

		// Collapse
		handleCollapseTriggers('js-collapse-toggle');

		// Overlays
		handleOverlayTriggers('js-overlay-toggle');
});
