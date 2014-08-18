var Tabs={};
Tabs.currentName=null;
Tabs.set=function(name){
	if(!Tabs.currentName){
		// try to detect selected li:
		var uls=d.getElementsByTagName("UL");
		for(var i=0;i<uls.length;i++){
			var ul=uls[i];
			if(ul.className!="tabs")continue;
			var lis=ul.childNodes;
			for(var j=0;j<lis.length;j++){
				var li=lis[j];
				if(li.tagName!="LI")continue;
				if(li.className.indexOf(" sel")==-1&&
					li.className.indexOf("sel ")==-1&&
					li.className!="sel")continue;
				if(!li.id||!li.id.match(/tab(.+)/))continue;
				Tabs.currentName=li.id.substr(3);
				break;
			}
		}
	}
	if(Tabs.currentName){
		var content=d.getElementById("tabContent"+Tabs.currentName);
		var li=d.getElementById("tab"+Tabs.currentName);
		CSS.r(content,'tabContentSel');
		CSS.r(li,'sel');
	}
	Tabs.currentName=name;
	var content=d.getElementById("tabContent"+Tabs.currentName);
	var li=d.getElementById("tab"+Tabs.currentName);
	CSS.a(content,'tabContentSel');
	CSS.a(li,'sel');
}