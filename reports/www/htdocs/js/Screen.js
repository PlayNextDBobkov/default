var screenWidth=0,screenHeight=0;
var screenScrollTop=0,screenScrollLeft=0;
function getScreenSize(){
	if(typeof(window.innerWidth)=='number'){
		//Non-IE
		//screenWidth=window.innerWidth;
		var u=document.getElementById("dummy-width");
		if(u)screenWidth=u.offsetWidth;
		else screenWidth=window.innerWidth;
		screenHeight=window.innerHeight;
	}else if(document.documentElement&&(document.documentElement.clientWidth||document.documentElement.clientHeight)){
		//IE 6+ in 'standards compliant mode'
		screenWidth=document.documentElement.clientWidth;
		screenHeight=document.documentElement.clientHeight;
	}else if(document.body&&(document.body.clientWidth||document.body.clientHeight)) {
		//IE 4 compatible
		screenWidth=document.body.clientWidth;
		screenHeight=document.body.clientHeight;
	}
}
function getScreenScroll(){
	var scrOfX = 0, scrOfY = 0;
	if( typeof( window.pageYOffset ) == 'number' ) {
		//Netscape compliant
		screenScrollTop=window.pageYOffset;
		screenScrollLeft=window.pageXOffset;
	} else if( document.body && ( document.body.scrollLeft || document.body.scrollTop ) ) {
		//DOM compliant
		screenScrollTop=document.body.scrollTop;
		screenScrollLeft=document.body.scrollLeft;
	} else if( document.documentElement && ( document.documentElement.scrollLeft || document.documentElement.scrollTop ) ) {
		//IE6 standards compliant mode
		screenScrollTop=document.documentElement.scrollTop;
		screenScrollLeft=document.documentElement.scrollLeft;
	}
}

function Screen(){
}
Screen.width=0;
Screen.height=0;
Screen.scrollTop=0;
Screen.scrollLeft=0;
Screen.getSize=function(){
	if(typeof(window.innerWidth)=='number'){
		//Non-IE
		//Screen.width=window.innerWidth;
		var u=document.getElementById("dummy-width");
		if(u)screenWidth=u.offsetWidth;
		else screenWidth=window.innerWidth;
		Screen.height=window.innerHeight;
	}else if(document.documentElement&&(document.documentElement.clientWidth||document.documentElement.clientHeight)){
		//IE 6+ in 'standards compliant mode'
		Screen.width=document.documentElement.clientWidth;
		Screen.height=document.documentElement.clientHeight;
	}else if(document.body&&(document.body.clientWidth||document.body.clientHeight)) {
		//IE 4 compatible
		Screen.width=document.body.clientWidth;
		Screen.height=document.body.clientHeight;
	}
}
Screen.getScroll=function(){
	var scrOfX = 0, scrOfY = 0;
	if( typeof( window.pageYOffset ) == 'number' ) {
		//Netscape compliant
		Screen.scrollTop=window.pageYOffset;
		Screen.scrollLeft=window.pageXOffset;
	} else if( document.body && ( document.body.scrollLeft || document.body.scrollTop ) ) {
		//DOM compliant
		Screen.scrollTop=document.body.scrollTop;
		Screen.scrollLeft=document.body.scrollLeft;
	} else if( document.documentElement && ( document.documentElement.scrollLeft || document.documentElement.scrollTop ) ) {
		//IE6 standards compliant mode
		Screen.scrollTop=document.documentElement.scrollTop;
		Screen.scrollLeft=document.documentElement.scrollLeft;
	}
}
Screen.absOffset=function(a,b){
	var c=0;
	while(a){
		c+=a[b];
		a=a.offsetParent;
	}
	return c;
}