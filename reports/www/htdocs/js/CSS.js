var CSS={};
CSS.a=function(el,c){
	CSS.addClass(el,c);
}
CSS.r=function(el,c){
	CSS.removeClass(el,c);
}
CSS.t=function(el,c){
	return CSS.toggle(el,c);
}
CSS.toggle=function(el,c){
	if(el.className.indexOf(c)==-1){
		var added=true;
		CSS.a(el,c);
	}
	else {
		var added=false;
		CSS.r(el,c);
	}
	return added;
}
CSS.th=function(el){
	return CSS.t(el,'hidden');
}
CSS.addClass=function(el,c){
	classRemove(el,c);
	if(el.className.length>0)el.className+=" "+c;
	else el.className=c;
}
CSS.removeClass=function(el,c){
	var cc=el.className.split(" ");
	var cc2=new Array();
	for(var i=0;i<cc.length;i++){
		cc[i]=cc[i].replace(" ","");
		if(cc[i]==c)continue;
		cc2.push(cc[i]);
	}
	el.className=cc2.join(" ");
}
CSS.contains=function(el,name){
	var cc=el.className.split(" ");
	for(var i=0;i<cc.length;i++){
		var c=cc[i].replace("/\s/", "");
		if(c==name)return true;
	}
	return false;
}
CSS.getOpacity=function(el){
	if(BrowserDetect.browser=="Explorer"){
		if(!el.filters||!el.filters.alpha)return 1;
		var opacity=parseFloat(el.filters.alpha.opacity)/100;
	}
	else{
		if(!el.style.opacity)return 0;
		//if(el.style.opacity=="")return 1;
		var opacity=parseFloat(el.style.opacity);
	}
	return opacity;

}
CSS.setOpacity=function(d,o){
	if(!d)return;
	if(BrowserDetect.browser=="Explorer"){
		if(o==1)d.style.filter="none";
		else d.style.filter="alpha(opacity="+Math.round(o*100)+")";
	}
	else d.style.opacity=o;
}
/*
	Obsolete functions.
*/
function classAdd(el,c){
	classRemove(el,c);
	if(el.className.length>0)el.className+=" "+c;
	else el.className=c;
}
function classRemove(el,c){
	if(!el){
		alert("COuld not find element to set CSS '"+c+"'.");
		return;
	}
	var cc=el.className.split(" ");
	var cc2=new Array();
	for(var i=0;i<cc.length;i++){
		cc[i]=cc[i].replace(" ","");
		if(cc[i]==c)continue;
		cc2.push(cc[i]);
	}
	el.className=cc2.join(" ");
}