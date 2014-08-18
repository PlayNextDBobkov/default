var Form={};
Form.lastForm=null;
Form.repeatSubmit=function(){
	if(!Form.lastForm)return;
	if(!Form.check(Form.lastForm))return;
	Form.lastForm.submit();
}
Form.check=function(f){
	Form.lastForm=f;
	var i;
	var warn="";

	for(i=0;i<f.elements.length;i++){
		var e=f.elements[i];

		var prevent=e.getAttribute("preventSubmit");
		if(prevent){
			setTimeout("Form.repeatSubmit()",500);
			return false;
		}

		// clear iFocus:
		var parentIDiv=HTML.parent(e,"div","i",true);
		if(parentIDiv)CSS.r(parentIDiv,"iFocus");

		var requiredIf=e.getAttribute("requiredIf")||e.getAttribute("validationIf");
		if(requiredIf) c=eval(requiredIf);
		else c=1;
		var required=e.getAttribute("required")||e.getAttribute("validation");
		if(required&&c){
			if((e.type=="text"||e.type=="password"||e.type=="textarea")&&(e.value==""||e.className.indexOf("hint")!=-1)){
				warn="Пожалуйста, введите поле '"+required+"'!";
				if(e.getAttribute("hint"))e.value="";
			}
			else if(e.type=="select-one"&&(e.options[e.selectedIndex].value==''||e.options[e.selectedIndex].value=='%%%NULL%%%')) warn="Пожалуйста, выберите из списка '"+required+"'!";
			else if(e.type=="textarea"&&e.value=="") warn="Пожалуйста, введите текст в поле '"+required+"'!";
			else if(e.type=="file"&&e.value=="") warn="Пожалуйста, укажите файл в поле '"+required+"'!";
			else if(e.type=="checkbox"&&!e.checked) warn="Пожалуйста, поставьте галочку в пункт '"+required+"'!";
			else if(e.type=="radio"){
				for(j=0;j<f.elements.length;j++){
					if(f.elements[j].type=="radio"&&f.elements[j].name==e.name&&f.elements[j].checked) break;
				}
				if(j==f.elements.length)warn="Пожалуйста, выберите из вариантов '"+required+"'!";
			}
			else if(e.type=="hidden"&&e.value==""){
				warn=required;
			}
		}
		if(e.type=="file"&&e.value!=""){
			var uploadableFormats=e.getAttribute("uploadableFormats");
			if(uploadableFormats){
				var v=e.value.toLowerCase();
				var fs=uploadableFormats.split(",");
				for(var j=0;j<fs.length;j++){
					if(v.substr(v.length-fs[j].length)==fs[j].toLowerCase()) break;
				}
				if(j==fs.length) warn="Пожалуйста, выберите файл в одном из форматов: "+uploadableFormats;
			}
		}
		if(warn!=""){
			alert(warn);
			// check there is a parent div.i:
			if(parentIDiv)CSS.a(parentIDiv,"iFocus");
			e.focus();
			return false;
		}
	}

	// clear hints:
	for(i=0;i<f.elements.length;i++){
		var e=f.elements[i];
		if((e.type=="text"||e.type=="password"||e.type=="textarea")&&e.className.indexOf("hint")!=-1&&e.getAttribute("hint")){
			e.value="";
		}
	}
	return true;
}
Form.onInputFocus=function(e){
	var s=Event.target(e);
	if(s.className.indexOf("hint")==-1)return;
	CSS.r(s,"hint");
	if(s.type=="text") {
		s.value="";
		if(s.getAttribute("realType")=="password")s.setAttribute("type","password");
	}
	if(s.type=="textarea")s.value="";
}
Form.onInputBlur=function(e){
	var s=Event.target(e);
	if(s.type=="select-one"&&(s.options[s.selectedIndex].value==''||s.options[s.selectedIndex].value=='%%%NULL%%%')){
		CSS.a(s,"hint");
	}
	else if(s.value.length==0){
		Form.setHint(s);
	}
}
/*Form.onInputChange=function(e){
	var s=Event.target(e);
	CSS.a(s,"clicked");
}*/
Form.init=function(){
	var forms=d.getElementsByTagName("form");
	for(var j=0;j<forms.length;j++){
		var f=forms[j];

		// process custom form elements:
		var divs=f.getElementsByTagName("div");
		for(var i=0;i<divs.length;i++){
			var div=divs[i];

			if(div.getAttribute("uploaderToken")||div.getAttribute("uploaderEntity")){
				var uploader=new Uploader.FileUploader({
					element:div//,
					//debug: true,
					/*onComplete:function(id,fileName,responseJSON){
						///alert(responseJSON["file"]+"!!!");
					}*/
				});  
			}
			else if(div.getAttribute("calendarName")){
				var calendar=new Calendar.FormSelector(div,{
					callback:function(c){
						c.input.value=c.selectedDateString;
						c.inputHidden.value=c.selectedDateValue;
						CSS.r(c.input,"hint");
					}
				});
			}
		}

		// add hints:
		for(var i=0;i<f.elements.length;i++){
			var e=f.elements[i];
			if(!e.getAttribute("hint"))continue;
			if(e.type=="text"||e.type=="password"||e.type=="textarea"||e.type=="select-one"){

				Form.hintize(e);
			}
		}
	}
}
Form.setHint=function(s){
	if(s.getAttribute("hint")) {
		if(s.type!="select-one")s.value=s.getAttribute("hint");
		if(s.getAttribute("type")=="password"){
			s.setAttribute("realType","password");
			s.setAttribute("type","text");
		}
		CSS.a(s,"hint");
	}
}
Form.isHinted=function(e){
	if((e.type=="text"||e.type=="password"||e.type=="textarea"||e.type=="select-one")&&e.className.indexOf("hint")!=-1)return true;
	return false;
}
Form.hintize=function(e){
	if(!e.value){
		// hint this element:
		Form.setHint(e);
	}

	//if(e.getAttribute("isCalendar")) continue;

	Event.on(e,"focus",Form.onInputFocus);
	Event.on(e,"blur",Form.onInputBlur);
	//Event.on(e,"change",Form.onInputChange);
}
onReadys.push(Form.init);
