var Mouse={};
Mouse.x=0;
Mouse.y=0;
// detect mouse moves:
Mouse.IE=document.all?true:false
if (!Mouse.IE)document.captureEvents(Event.MOUSEMOVE)
if(document.onmousemove)document.onmousemove=Mouse.get;
// Main function to retrieve mouse x-y pos.s
Mouse.get=function(e) {
	if (Mouse.IE) { // grab the x-y pos.s if browser is IE
		Mouse.x=event.clientX+document.body.scrollLeft;
		Mouse.y=event.clientY+document.body.scrollTop;
	}
	else if(e.targetTouches){
		Mouse.x=e.targetTouches[0].pageX;
		Mouse.y=e.targetTouches[0].pageY;
	}
	else{  // grab the x-y pos.s if browser is NS
		Mouse.x=e.pageX;
		Mouse.y=e.pageY;
	}  
	// catch possible negative values in NS4
	if (Mouse.x<0){Mouse.x=0}
	if (Mouse.y<0){Mouse.y=0}
}