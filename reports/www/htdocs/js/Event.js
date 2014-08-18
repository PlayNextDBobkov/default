var Event={};
/**
	Attaches event handler to an object.
	Event.on(listener,event,handler)
*/
Event.on=function(a,b,c){
	if(!a)alert("Undefined element for Event.on '"+b+"':\n"+c);
	var d="on"+b;
	if(a.addEventListener){
		a.addEventListener(b,c,false)
	}
	else if(a.attachEvent){
		a.attachEvent(d,c)
	}
	else{
		var e=a[d];
		a[d]=function(){
			var f=e.apply(this,arguments),h=c.apply(this,arguments);
			return f==undefined?h:(h==undefined?f:h&&f)
		}
	}
}
Event.off=function(obj,type,fn){
	if(obj.removeEventListener) obj.removeEventListener(type,fn,false);
	else if(obj.detachEvent){
		obj.detachEvent("on"+type,fn);
	}
}
Event.target=function(e){
	if(!e)var e=window.event;
	if(e.srcElement)return e.srcElement;
	return e.currentTarget;
}
Event.fire=function(element,event){
    if (document.createEventObject){
		// dispatch for IE
		var evt = document.createEventObject();
		return element.fireEvent('on'+event,evt);
    }
    else{
		// dispatch for firefox + others
		var evt = document.createEvent("HTMLEvents");
		evt.initEvent(event, true, true ); // event type,bubbling,cancelable
		return !element.dispatchEvent(evt);
    }
}