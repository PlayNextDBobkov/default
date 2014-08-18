var HTML={};
HTML.get=function(id){
	return document.getElementById(id);
}
HTML.parent=function(el,tagName,withClassName,withRecursion){
	if(!el)return null;
	var p=el.parentNode;
	tagName=tagName.toUpperCase();
	while(p!=undefined){
		if(!tagName&&!withClassName)return p;
		if(tagName&&!withClassName&&p.tagName==tagName)return p;
		if(!tagName&&withClassName&&p.className.indexOf(withClassName)!=-1)return p;
		if(tagName&&withClassName&&p.tagName==tagName&&p.className.indexOf(withClassName)!=-1)return p;

		if(!withRecursion)break;
		p=p.parentNode;
	}
	return null;
}
HTML.child=function(el,tagName,withClassName){
	if(!el)return null;
	tagName=tagName.toUpperCase();
	var children=el.childNodes;
	for(var i=0;i<children.length;i++){
		var child=children[i];
		if(!tagName&&!withClassName)return child;
		if(tagName&&!withClassName&&child.tagName==tagName)return child;
		if(!tagName&&withClassName&&child.className.indexOf(withClassName)!=-1)return child;
		if(tagName&&withClassName&&child.tagName==tagName&&child.className.indexOf(withClassName)!=-1)return child;

		var child=HTML.child(child,tagName,withClassName);
		if(child)return child;
	}
	return null;
}
HTML.getAll=function(el,tagName,withClassName){
	if(!el)el=d.documentElement;
	var els=[];
	var j=0;
	var children=el.getElementsByTagName(tagName);
	for(var i=0;i<children.length;i++){
		var child=children[i];
		if(!withClassName)els[els.length]=child;
		else if(withClassName&&child.className.indexOf(withClassName)!=-1)els[els.length]=child;
	}
	return els;
}
var all=function(tagName,withClassName){return HTML.getAll(null,tagName,withClassName)};
var get=HTML.get;

