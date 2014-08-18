function DynamicImage(src,timing){
	this.src=src;
	this.timing=Math.round(timing/50);
}
var DynamicImages={
	isIE:true
	,intervalLoadDynamicImages:0
	,intervalAnimDynamicImages:0
	,countAnimDynamicImages:0
	,loadedDynamicImages:{}
	,dynamicImageFadingTime:40

	,absOffset:function(a,b){
		var c=0;
		while(a){
			c+=a[b];
			a=a.offsetParent;
		}
		return c;
	}
	,setOpacity:function(d,o){
		if(DynamicImages.isIE)d.style.filter="alpha(opacity="+Math.round(o*100)+")";
		else d.style.opacity=o;
	}
	,isImageLoaded:function(img) {
		// During the onload event, IE correctly identifies
		// any images that
		// weren?t downloaded as not complete. Others should too. Gecko-based
		// browsers act like NS4 in that they report this incorrectly.
		if(!img.complete)return false;

		// However, they do have two very useful properties:
		//naturalWidth and
		// naturalHeight. These give the true size of the image. If it failed
		// to load, either of these should be zero.
		if(typeof img.naturalWidth!="undefined"&&img.naturalWidth==0)return false;

		// No other way of checking: assume it?s ok.
		return true;
	}
	,loadDynamicImages:function(){
		if(!isWindowLoaded)return;

		// clear this interval:
		clearInterval(DynamicImages.intervalLoadDynamicImages);
		DynamicImages.intervalLoadDynamicImages=null;

		var top=d.getElementById("dynamicImages");
		for(i=0;i<dynamicImages.length;i++){
			/*var j=dynamicImageIndex+i;
			if(j>=dynamicImages.length)j-=dynamicImages.length;
			var src=dynamicImages[j];*/

			var lim=d.createElement("img");
			lim.setAttribute("id","_im"+i);
			//lim.style.zIndex=10;
			//lim.setAttribute("width",160);
			//lim.setAttribute("height",160);
			//lim.style.display="none";
			top.appendChild(lim);
			DynamicImages.setOpacity(lim,0);
		}
		// start loading first image:
		var di=dynamicImages[dynamicImageIndex];
		var src=di.src;
		lim=d.getElementById("_im"+dynamicImageIndex);
		lim.setAttribute("src",src);
		if(DynamicImages.isImageLoaded(lim)||DynamicImages.loadedDynamicImages["im"+dynamicImageIndex]){
			//console.log("Image is already loaded:"+lim);
			DynamicImages.setImageLoaded(lim,dynamicImageIndex);
		}
		else Event.on(lim,"load",DynamicImages.onDynamicImageLoad);
	}
	,onDynamicImageLoad:function(e){
		var lim=Event.target(e);
		var id=lim.getAttribute("id").substring(1);
		var j=id.substring(2);
		DynamicImages.setImageLoaded(lim,j);
		//alert("Loaded: "+j+": "+lim.src);
	}
	,setImageLoaded:function(lim,j){
		DynamicImages.loadedDynamicImages["im"+j]=true;

		///lim.style.background="url('/i/1.jpg')";
		//lim.style.backgroundImage="url('"+lim.src+"') no-repeat center center";
		//lim.src="/i/e.gif";
		var f=get("dynamicImages");
		//lim.style.left=(f.offsetWidth/2-lim.offsetWidth/2) + "px";
		if(j==dynamicImageIndex){
			if(DynamicImages.intervalAnimDynamicImages==0){
				DynamicImages.intervalAnimDynamicImages=setInterval("DynamicImages.animDynamicImages()",50);
				//DynamicImages.countAnimDynamicImages++;
				///alert("First loaded: "+j+": "+lim.src);
			}
		}
		DynamicImages.loadNextDynamicImage(j);
	}
	,loadNextDynamicImage:function(j){
		j++;
		if(j>=dynamicImages.length)j-=dynamicImages.length;
		var di=dynamicImages[j];
		var src=di.src;
		var lim=d.getElementById("_im"+j);
		//opera.postError("loadNextDynamicImage: "+j+"DynamicImages.isImageLoaded(lim): "+DynamicImages.isImageLoaded(lim));
		if(lim.src!=""&&(DynamicImages.isImageLoaded(lim)||DynamicImages.loadedDynamicImages["im"+j])){
			//opera.postError("All images are loaded!");
			//console.log("All images are loaded!");
			return;
		}
		lim.setAttribute("src",src);
		//opera.postError("Start loading next image: "+src+":"+lim);
		Event.on(lim,"load",DynamicImages.onDynamicImageLoad);
	}
	,animDynamicImages:function(){
		if(DynamicImages.countAnimDynamicImages<=DynamicImages.dynamicImageFadingTime){
			// fading out:
			var a=DynamicImages.countAnimDynamicImages/DynamicImages.dynamicImageFadingTime;
			var lim=d.getElementById("_im"+dynamicImageIndex);
			DynamicImages.setOpacity(lim,a);
			DynamicImages.countAnimDynamicImages++;
		}
		else if(DynamicImages.countAnimDynamicImages==DynamicImages.dynamicImageFadingTime+1){
			var j=dynamicImageIndex-1;
			if(j<0)j=dynamicImages.length-1;
			var lim=d.getElementById("_im"+j);
			lim.style.display="none";
			DynamicImages.countAnimDynamicImages++;
		}
		else if(DynamicImages.countAnimDynamicImages<DynamicImages.dynamicImageFadingTime+dynamicImages[dynamicImageIndex].timing){
			// waiting cycle:
			DynamicImages.countAnimDynamicImages++;
		}
		else{
			// waiting for next image:
			var j=dynamicImageIndex+1;
			if(j>=dynamicImages.length)j-=dynamicImages.length;
			var lim=d.getElementById("_im"+j);
			if(!DynamicImages.isImageLoaded(lim))return;
			////opera.postError("Go to next image: "+j);
			//console.log("Go to next image: "+j);
			var prev=d.getElementById("_im"+dynamicImageIndex);
			prev.style.zIndex=10;
			DynamicImages.setOpacity(lim,0);
			lim.style.display="block";
			lim.style.zIndex=11;
			DynamicImages.countAnimDynamicImages=0;
			dynamicImageIndex=j;
		}
	}
	,onWResize:function(){
		var f=get("dynamicImages");
		var imgs=f.getElementsByTagName("img");
		for(var i=0;i<imgs.length;i++){
			var lim=imgs[i];
			/*if(DynamicImages.isImageLoaded(lim)){
				if(lim.offsetWidth==0){
					lim.style.display="block";
					lim.style.left=(f.offsetWidth/2-lim.offsetWidth/2)+"px";
					lim.style.display="none";
				}
				else lim.style.left=(f.offsetWidth/2-lim.offsetWidth/2)+"px";
			}*/
		}
	}
	,init:function(){
		BrowserDetect.init();
		DynamicImages.isIE=BrowserDetect.browser=="Explorer";

		// start loading dynamic images:
		if(d.getElementById("dynamicImages")!=undefined){
			DynamicImages.intervalLoadDynamicImages=setInterval("DynamicImages.loadDynamicImages()",50);
		}

		Event.on(self,"resize",DynamicImages.onWResize);
	}
}
onReadys.push(DynamicImages.init);
