/**
	This is autocomplete functionality.
	It requires Ajax.js, event.js.
*/
function AutoComplete(parentInput,url,param){
	this.parentInput=null;
	this.el=null;
	this.ajax=null;
	this.isOn=true;
	this.url=null;
	this.results=null;
	this.onResults=null;
	this.id=null;
	this.isVisible=false;
	this.hasResults=false;

	this.applyTo=function(parentInput){
		this.parentInput=parentInput;
		this.parentInput.setAttribute("autocomplete", "off"); 
		this.el=d.createElement("div");
		this.el.id=this.id;
		this.el.className="acHidden";
		this.parentInput.parentNode.insertBefore(this.el,this.parentInput.nextSibling);
		this.onWResize();
		this.ajax=new Ajax();
		this.ajax.onResponse=this.ajax.delegate(this,this.onResponse);
		Event.on(this.parentInput,"keyup",this.ajax.delegate(this,this.onKeyUp));
		Event.on(this.parentInput,"click",this.ajax.delegate(this,this.onClick));
		Event.on(this.parentInput,"blur",this.ajax.delegate(this,this.onBlur));
		Event.on(window,"resize",this.ajax.delegate(this,this.onWResize));
	}
	this.onResponse=function(){
		var rs=eval('('+this.ajax.x.responseText+')');
		//turn to array:
		this.results=new Array();
		for(var id in rs){
			this.results.push(rs[id]);
		}
		this.hasResults=true;
		this.show(this.results);
	}
	this.show=function(results){
		if(results)this.results=results;
		//clear ac:
		this.el.innerHTML="";
		this.isVisible=true;

		if(!this.results){
			this.showLoading();
			return;
		}

		//if smth found?
		if(!this.results||this.results.length==0){
			this.showNone();
			return;
		}

		//smth found:
		this.showResults();
	}
	this.showLoading=function(){
		this.el.innerHTML="";
		this.el.className="ac acLoading";
		this.el.innerHTML="<div>поиск...</div>";
	}
	this.showNone=function(){
		this.el.className="ac acNone";
		this.el.innerHTML="<div>такие позиции не найдены</div>";
	}
	this.showResults=function(){
		var d=document;
		var frame=d.createElement("div");
		frame.className="frame";
		this.el.appendChild(frame);
		var list=d.createElement("div");
		list.className="list";
		frame.appendChild(list);
		for(var i=0;i<this.results.length;i++){
			var p=this.results[i];
			var div=d.createElement("div");
			div.className="item";
			div.innerHTML="<span>"+this.itemHTML(p)+"</span>";
			Event.on(div,"click",this.ajax.delegate(this,this.onItemClick,p));
			Event.on(div,"mouseover",this.ajax.delegate(this,this.onItemMouseOver,p,div));
			Event.on(div,"mouseout",this.ajax.delegate(this,this.onItemMouseOut,p,div));
			//div.setAttribute("onMouseOver","classAdd(this,'itemOver')");
			//div.setAttribute("onMouseOut","classRemove(this,'itemOver')");
			list.appendChild(div);
		}
		//var close=d.createElement("div");
		//close.className="close";
		//close.innerHTML="<span onClick=\"AutoComplete.hide('"+this.id+"',1)\">закрыть [x]</span>";
		//this.el.appendChild(close);

		this.el.className="ac";	// show AC
		if(frame.offsetHeight>100)frame.style.height="100px";
		else frame.style.height="auto";
	}
	this.onItemMouseOver=function(ac,method,item,div){
		classAdd(div,'itemOver')
	}
	this.onItemMouseOut=function(ac,method,item,div){
		classRemove(div,'itemOver')
	}
	this.hide=function(isForever){
		this.isVisible=false;
		if(isForever)this.isOn=false;
		this.el.className="acHidden";
	}
	this.onSelect=function(item){
		this.parentInput.value=item.name;
		this.hide();
	}
	this.onItemClick=function(ac,method,item){
		clearTimeout(this.timeoutBlur);
		this.timeoutBlur=null;
		this.onSelect(item);
		this.hide();
	}
	this.itemHTML=function(item){
		return item.name;
	}
	this.onClick=function(e){
		if(!this.isOn)return;
		if(this.parentInput.value.length<1)return;
		if(this.hasResults)this.show();
	}
	this.onBlur=function(e){
		this.timeoutBlur=setTimeout("AutoComplete.hide('"+this.id+"')",150);
	}
	this.onKeyUp=function(e){
		//is this ac on?
		if(!this.isOn)return;
		if(this.parentInput.value.length<1)return;
		this.showLoading();
		this.makeRequest();
	}
	this.makeRequest=function(){
		this.ajax.send(this.url,this.param+"="+encodeURI(this.parentInput.value));
	}
	this.onWResize=function(){
		if(!this.el)return;
		//this.el.style.top=(Screen.absOffset(this.parentInput,"offsetTop")+this.parentInput.offsetHeight-1)+"px";
		//this.el.style.left=Screen.absOffset(this.parentInput,"offsetLeft")+"px";
		this.el.style.top=(this.parentInput.offsetTop+this.parentInput.offsetHeight-1)+"px";
		this.el.style.left=this.parentInput.offsetLeft+"px";
		this.el.style.width=(this.parentInput.offsetWidth-2)+"px";
	}

	this.url=url;
	this.param=param;
	this.id="ac"+Math.round(Math.random()*1000000);
	AutoComplete.items[this.id]=this;
	this.applyTo(parentInput);
}
AutoComplete.items={};
AutoComplete.hide=function(acId,isForever){
	AutoComplete.items[acId].hide(isForever);
}
AutoComplete.processPage=function(){
	var inputs=d.getElementsByTagName("input");
	for(var i=0;i<inputs.length;i++){
		var el=inputs[i];
		if(el.type!="text")continue;
		var url=el.getAttribute("ac");
		if(!url)continue;
		var param=el.getAttribute("acParam");
		if(!param)param="name";
		var ac=new AutoComplete(el,url,param);
		
		var template=el.getAttribute("acTemplate");
		if(template){
			ac.template=template;
			ac.itemHTML=function(item){
				return eval(this.template);
			}
		}

		var acMakeRequest=el.getAttribute("acMakeRequest");
		if(acMakeRequest){
			ac.acMakeRequestExp=acMakeRequest;
			ac.makeRequest=function(item){
				eval(this.acMakeRequestExp+"(this)");
			}
		}
		
		var acOnSelect=el.getAttribute("acOnSelect");
		if(acOnSelect){
			ac.acOnSelectExp=acOnSelect;
			ac.onSelect=function(item){
				eval(this.acOnSelectExp);
			}
		}
		else{
			var valueTemplate=el.getAttribute("acValueTemplate");
			if(!valueTemplate&&template)valueTemplate=template;
			if(valueTemplate){
				ac.valueTemplate=valueTemplate;
				ac.acOnSelect=function(item){
					this.parentInput.value=eval(this.valueTemplate);
				}
			}
		}
	}
}
