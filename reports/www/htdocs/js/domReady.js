var isLoaded=false;
var isDomReady=false;
function onWindowLoad(){
	isLoaded=true;
	if(isDomReady)onReady();
}
function onDOMReady(){
	isDomReady=true;
	onReady();
}
function onReady(){
	document.getElementById("noJS").style.display="none";
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
if(/Safari/i.test(navigator.userAgent)){
  var _timer=setInterval(function(){
  if(/loaded|complete/.test(document.readyState)){
    clearInterval(_timer);
    onDOMReady();
  }},10);
}