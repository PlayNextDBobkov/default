var isWindowLoaded=false;
var isDomReady=false;
function onWindowLoad(){
	//alert("onWindowLoad()");
	isWindowLoaded=true;
	//if(isDomReady)onReady();
}
function onDOMReady(){
	//alert("onDOMReady()");
	isDomReady=true;
	/*if(isWindowLoaded)*/onReady();
}
function onReady(){
	//alert("onReady()");
	//document.getElementById("noJS").style.display="none";
	for(var i=0;i<onReadys.length;i++){
		onReadys[i]();
	}
}
if(document.addEventListener)
	document.addEventListener("DOMContentLoaded",onDOMReady, false);
else if(document.all&&!window.opera){
	document.write('<script type="text/javascript" id="contentloadtag" defer="defer" src="javascript:void(0)"><\/script>')
	var contentloadtag=document.getElementById("contentloadtag");
	contentloadtag.onreadystatechange=function(){
		if(this.readyState=="complete")onDOMReady();
	}
}
window.onload=onWindowLoad;
//alert("onReady.js: "+onReadys.length);