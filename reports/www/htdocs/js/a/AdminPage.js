function get(id){
	return d.getElementById(id);
}
function getParentElByTag(el,tag){
	while(el.parentNode&&el.parentNode.tagName.toLowerCase()!=tag.toLowerCase()){
		el=el.parentNode;
	}
	if(el.parentNode)return el.parentNode;
	return null;
}
//onReadys.push(AdminPage.init);
