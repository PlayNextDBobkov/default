function PageActionData(id,action,name,data){
	this.id=id;
	this.action=action;
	this.name=name;
	this.data=data;
}
function onChangeAction(s){
	var s=document.getElementById("selectActionDataId");
	resetSelect(s,"-- без привязки --");
	var sa=document.getElementById("selectAction");
	var pads=pageActionDatas[sa.options[sa.selectedIndex].value];
	if(pads==undefined)return;
	for(key in pads){
		var item=pads[key];
		var o=document.createElement("option");
		o.value=item.id;
		o.text=item.name;
		try {
			s.add(o,null); // standards compliant; doesn-t work in IE
		}
		catch(ex) {
			s.add(o); // IE only
		}
	}
	s.disabled=false;
	s.focus();
}
function resetSelect(cc,text){
	while(cc.options.length>0){
		cc.remove(cc.options.length-1);
	}
	var o=document.createElement("option");
	o.text=text;
	o.value="";
	try{
		cc.add(o,null); // standards compliant; doesn't work in IE
	}
	catch(ex) {
		cc.add(o); // IE only
	}
	cc.disabled=true;
}