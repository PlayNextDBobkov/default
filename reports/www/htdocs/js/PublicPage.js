var PublicPage={};
PublicPage.init=function(){
	Event.on(self,"resize",PublicPage.onWResize);
	PublicPage.onWResize();
}
PublicPage.onWResize=function(){
	Screen.getSize();
}
onReadys.push(PublicPage.init);
