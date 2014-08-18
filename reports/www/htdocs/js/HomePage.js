var HomePage={
}
HomePage.isMapLoading=false;
HomePage.onTabChange=function(name){
	if(name=="JobsMap"&&!HomePage.isMapLoading){
		HomePage.isMapLoading=true;
		Job.loadMap();
	}
}
//onReadys.push(publicPage.init);
