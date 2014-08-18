var CallRequest={
}
CallRequest.init=function(){
	BrowserDetect.init();

	Event.on(window,"resize",CallRequest.onWResize);
	CallRequest.onWResize();
}
CallRequest.onWResize=function(){
	CallRequest.placeForm();
}
CallRequest.showForm=function(){
	// show form:
	CallRequest.isShownCallRequestForm=true;
	var p=d.getElementById("callRequestForm");
	p.style.display="block";
	var p=d.getElementById("callRequestFormMain");
	p.style.display="block";
	// show fade:
	var f=get("fade");
	CSS.addClass(f,"visible");
	CSS.setOpacity(f,0.75);
	// pos form & fade:
	CallRequest.placeForm();
}
CallRequest.closeForm=function(ccpId){
	// hide form:
	CallRequest.isShownCallRequestForm=false;
	var p=d.getElementById("callRequestForm");
	p.style.display="none";
	// hide fade:
	var f=get("fade");
	CSS.removeClass(f,"visible");
}
CallRequest.placeForm=function(ccpId){
	if(!CallRequest.isShownCallRequestForm)return;
	Screen.getSize();
	var c=get("callRequestForm");
	var s=Screen.scrollTop;
	//alert(s+" : "+Screen.height/2 + " : "+ c.offsetHeight);
	c.style.top=Math.round(s+Screen.height/2-c.offsetHeight/2)+"px";
	c.style.left=Math.round(Screen.width/2-c.offsetWidth/2)+"px";
	var f=get("fade");
	f.style.top=s+"px";
	f.style.left=0;
	f.style.width=Screen.width+"px";
	f.style.height=Screen.height+"px";
}
CallRequest.submitForm=function(f){
	if(f.phone.value==""){
		alert("Пожалуйста, укажите Ваш телефон.");
		f.phone.focus();
		return false;
	}
	var p=d.getElementById("callRequestFormMain");
	p.style.display="none";
	var l=d.getElementById("callRequestFormLoading");
	l.style.display="block";
	var ajax=new Ajax();
	var r="name="+encodeURI(f.name.value)
		+"&phone="+encodeURI(f.phone.value)
		+"&msg="+encodeURI(f.msg.value);
	ajax.onResponse=function(x){
		//f.reset();
		d.getElementById("callRequestFormLoading").style.display="none";
		CallRequest.closeForm();
		var r=eval("("+x.responseText+")");
		if(r.status)alert("Спасибо!\nНаш менеджер свяжется с Вами в ближайшее время!");
		CallRequest.isShownCallRequestForm=false;
	}
	ajax.send("/CallRequest.json",r);//?rnd"+Math.random());
	return false;
}
onReadys.push(CallRequest.init);

