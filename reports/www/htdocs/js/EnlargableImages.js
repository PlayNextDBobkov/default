function ImageView(index,url,width,height,name){
	this.index=parseInt(index);
	this.url=url;
	this.width=width;
	this.height=height;
	this.name=name;
}
var EnlargableImages={
	maxFade:0.8
	,animZoomInSteps:10
	,animZoomOutSteps:5
	,countAnimZoom:0
	,intervalAnimZoom:0
	,imageViewerData:null
	,imageViews:null
	,imageViewIndex:0
	,imageViewerTextTopMargin:8
	,imageViewerTextHeight:32
	,imageViewerPadding:{
		top:10
		,right:10
		,bottom:100
		,left:10
	}
	,isImageViewerMode:false
	,intervalLoadDynamicImages:0
	,intervalAnimDynamicImages:0
	,countAnimDynamicImages:0
	,loadedDynamicImages:{}
	,isDivMode:true

	,absOffset:function(a,b){
		var c=0;
		while(a){
			c+=a[b];
			a=a.offsetParent;
		}
		return c;
	}
	,onWResize:function(){
		var ei=EnlargableImages;
		if(!ei.isImageViewerMode)return;
		getScreenSize();
		ei.redrawFade();

		// reload big image:
		ei.createImageViewer(ei.imageViewerData.imageView,ei.imageViewerData.origImg);
		ei.showImageControls();
		ei.showImage();
		ei.loadImage();
	}
	,onWScroll:function(){
		var ei=EnlargableImages;
		if(!ei.isImageViewerMode)return;
		getScreenScroll();
		ei.showImageControls();
		ei.showImage();
	}
	,showImage:function(){
		var ei=EnlargableImages;
		// get screen size/scroll:
		getScreenSize();
		getScreenScroll();
		// set image taarget size:
		var tX=screenWidth/2-ei.imageViewerData.targetWidth/2;
		var tY=ei.imageViewerPadding.top+(screenHeight-ei.imageViewerPadding.top-ei.imageViewerPadding.bottom)/2-ei.imageViewerData.targetHeight/2;
		var vImg=ei.imageViewerData.img;
		vImg.style.width=(ei.imageViewerData.targetWidth)+"px";
		vImg.style.height=(ei.imageViewerData.targetHeight)+"px";
		vImg.style.left=tX+"px";
		vImg.style.top=screenScrollTop+tY+"px";

		// make it visible:
		vImg.style.display="block";

		// show text:
		var vText=ei.imageViewerData.textDiv;
		vText.style.left=Math.round(screenWidth*.15)+"px";
		vText.style.top=((ei.absOffset(vImg,"offsetTop"))+vImg.offsetHeight+ei.imageViewerTextTopMargin)+"px";
		vText.style.display="block";
	}
	,loadImage:function(){
		var ei=EnlargableImages;
		ei.imageViewerData.img.src=ei.imageViewerData.imageView.url;
		//CSS.a(ei.imageViewerData.img,"imageViewerLoading");
		if(ei.isImageLoaded(ei.imageViewerData.img)){
			ei.hideImageLoader();

			/*var vImg=ei.imageViewerData.img;
			CSS.setOpacity(vImg,0);
			vImg.style.display="block";
			FX.fadeIn(vImg,1,500);*/
		}
		else {
			ei.showImageLoader();
			Event.on(ei.imageViewerData.img,"load",ei.onImageLoad);
		}
	}
	,onImageLoad:function(e){
		var ei=EnlargableImages;
		ei.hideImageLoader();

		/*var vImg=ei.imageViewerData.img;
		CSS.setOpacity(vImg,0);
		vImg.style.display="block";
		FX.fadeIn(vImg,1,500);*/
	}
	,showImageLoader:function(){
		var ei=EnlargableImages;
		var l=get("enlargableImageLoader");
		l.style.display="block";
		getScreenSize();
		getScreenScroll();
		var tX=screenWidth/2-ei.imageViewerData.targetWidth/2;
		var tY=ei.imageViewerPadding.top+(screenHeight-ei.imageViewerPadding.top-ei.imageViewerPadding.bottom)/2-ei.imageViewerData.targetHeight/2;
		l.style.width=(ei.imageViewerData.targetWidth)+"px";
		l.style.height=(ei.imageViewerData.targetHeight)+"px";
		l.style.left=tX+"px";
		l.style.top=screenScrollTop+tY+"px";
	}
	,hideImageLoader:function(){
		get("enlargableImageLoader").style.display="none";
	}
	,showImageControls:function(){
		var ei=EnlargableImages;
		var c=d.getElementById("imageViewerControl");
		CSS.a(c,"visible");
		c.style.left=(screenWidth/2-c.offsetWidth/2)+"px";
		c.style.top=(screenScrollTop
			+screenHeight
			-ei.imageViewerPadding.bottom/2
			-c.offsetHeight/2
			)+"px";
		if(ei.imageViewIndex==0)CSS.a(d.getElementById("imageViewerControlPrev"),"disabled");
		else CSS.r(d.getElementById("imageViewerControlPrev"),"disabled");
		if(ei.imageViewIndex+1>=ei.imageViews.length)CSS.a(d.getElementById("imageViewerControlNext"),"disabled");
		else CSS.r(d.getElementById("imageViewerControlNext"),"disabled");
	}
	,animZoomIn:function(){
		var ei=EnlargableImages;
		// get current screen size/scroll positions:
		getScreenSize();
		getScreenScroll();
		// increment animation counter:
		ei.countAnimZoom++;
		// calculate animation step:
		var a=ei.countAnimZoom/ei.animZoomInSteps;
		var f=d.getElementById("fade");
		if(ei.countAnimZoom>=ei.animZoomInSteps){
			// animation is done - stop interval:
			clearInterval(ei.intervalAnimZoom);
			ei.intervalAnimZoom=0;
			// show fade in full:
			CSS.setOpacity(f,ei.maxFade);

			// show controls:
			ei.showImageControls();
			// show image:
			ei.showImage();

			// start loading image:
			ei.loadImage();
			//CSS.a(ei.imageViewerData.img,"imageViewerLoading");

			Event.on(f,"click",ei.onImageViewerClose);
		}
		else{
			// zoom-in animation loop:

			// increase image viewer size a little:
			/*var tX=screenWidth/2-ei.imageViewerData.targetWidth/2;
			var tY=ei.imageViewerPadding.top+(screenHeight-ei.imageViewerPadding.top-ei.imageViewerPadding.bottom)/2-ei.imageViewerData.targetHeight/2;
			var w=((ei.imageViewerData.targetWidth-ei.imageViewerData.origWidth)*a);
			var h=((ei.imageViewerData.targetHeight-ei.imageViewerData.origHeight)*a);
			ei.imageViewerData.img.style.width=w+"px";
			ei.imageViewerData.img.style.height=h+"px";
			ei.imageViewerData.img.style.left=(ei.imageViewerData.origLeft+(tX-ei.imageViewerData.origLeft)*a)+"px";
			ei.imageViewerData.img.style.top=(ei.imageViewerData.origTop+(tY-ei.imageViewerData.origTop)*a)+"px";
			ei.imageViewerData.img.style.display="block";*/

			// fade out a little:
			CSS.setOpacity(f,ei.maxFade*a);
		}
	}
	,animZoomOut:function(){
		var ei=EnlargableImages;
		ei.countAnimZoom++;
		var a=ei.countAnimZoom/ei.animZoomOutSteps;
		var f=get("fade");
		if(ei.countAnimZoom>=ei.animZoomOutSteps){
			clearInterval(ei.intervalAnimZoom);
			ei.intervalAnimZoom=0;
			CSS.setOpacity(f,0);
			CSS.r(f,"visible");
			CSS.r(d.body,"nonScrollable");
			ei.isImageViewerMode=false;

			Event.off(f,"click",ei.onImageViewerClose);
		}
		else{
			CSS.setOpacity(f,(1-a)*ei.maxFade);
		}
	}
	,onImageViewerPrev:function(){
		var ei=EnlargableImages;
		if(ei.imageViewIndex==0)return;
		ei.createImageViewer(ei.imageViews[ei.imageViewIndex-1],null);
		ei.showImage();
		ei.loadImage();
		if(ei.imageViewIndex==0)CSS.a(d.getElementById("imageViewerControlPrev"),"disabled");
		else CSS.r(d.getElementById("imageViewerControlPrev"),"disabled");
		CSS.r(d.getElementById("imageViewerControlNext"),"disabled");
	}
	,onImageViewerNext:function(){
		var ei=EnlargableImages;
		if(ei.imageViewIndex+1>=ei.imageViews.length)return;
		ei.createImageViewer(ei.imageViews[ei.imageViewIndex+1],null);
		ei.showImage();
		ei.loadImage();
		if(ei.imageViewIndex+1>=ei.imageViews.length)CSS.a(d.getElementById("imageViewerControlNext"),"disabled");
		else CSS.r(d.getElementById("imageViewerControlNext"),"disabled");
		CSS.r(d.getElementById("imageViewerControlPrev"),"disabled");
	}
	,onImageViewerClose:function(){
		var ei=EnlargableImages;
		ei.removeImageViewer();
		// hide control:
		var c=d.getElementById("imageViewerControl");
		CSS.r(c,"visible");
		ei.hideImageLoader();
		// start fade in animation:
		ei.countAnimZoom=0;
		ei.intervalAnimZoom=setInterval("EnlargableImages.animZoomOut()",25);
	}
	,redrawFade:function(){
		getScreenSize();
		getScreenScroll();
		var f=d.getElementById("fade");
		f.style.top="0px";//screenScrollTop+"px";
		f.style.left="0px";
		f.style.width=screenWidth+"px";
		var h=d.body.offsetHeight;
		if(h<screenScrollTop+screenHeight)h=screenScrollTop+screenHeight;
		f.style.height=h+"px";//d.body.offsetHeight+"px";//
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
	/**
		Creates image viewer.
		One of the following params should be not null:
		imageView - ImageView object
		img - clicked <img> element
	*/
	,createImageViewer:function(imageView,img){
		var ei=EnlargableImages;
		ei.removeImageViewer();
		if(img){
			// take data from attributes of the original <img>:
			var viewIndex=parseInt(img.getAttribute("enlargeIndex"));
			imageView=ei.imageViews[viewIndex];
		}
		// set index of currently viewed image:
		ei.imageViewIndex=imageView.index;
		// fill in image viewer params:
		ei.imageViewerData={
			imageView:imageView,
			targetWidth:imageView.width,
			targetHeight:imageView.height,
			textDiv:null,
			origImg:img
		};
		// create image viewer element - enlarged image object - invisible so far:
		var vImg=d.createElement("img");
		vImg.style.position="absolute";
		vImg.style.zIndex="10001";
		vImg.style.display="none";
		d.body.appendChild(vImg);
		ei.imageViewerData.img=vImg;
		if(img){
			ei.imageViewerData.origLeft=ei.absOffset(img,"offsetLeft");
			ei.imageViewerData.origTop=ei.absOffset(img,"offsetTop");
			ei.imageViewerData.origWidth=img.offsetWidth;
			ei.imageViewerData.origHeight=img.offsetHeight;

			// set same image src as in the clicked original <img>:
			//vImg.src=img.src;	// this prevents isImageLoaded from working!

			// set image original position (over clicked <img>):
			vImg.style.left=(ei.absOffset(img,"offsetLeft"))+"px";
			vImg.style.top=(ei.absOffset(img,"offsetTop"))+"px";
			vImg.style.width=img.offsetWidth+"px";
			vImg.style.height=img.offsetHeight+"px";
		}
		// calculate target size:
		var w=screenWidth-(ei.imageViewerPadding.left+ei.imageViewerPadding.right);
		var h=screenHeight-(ei.imageViewerPadding.top+ei.imageViewerPadding.bottom+ei.imageViewerTextTopMargin+ei.imageViewerTextHeight);
		var ratio=ei.imageViewerData.imageView.width/ei.imageViewerData.imageView.height;
		ei.imageViewerData.targetWidth=w
		ei.imageViewerData.targetHeight=w/ratio;
		if(ei.imageViewerData.targetWidth>w){
			ei.imageViewerData.targetWidth=w;
			ei.imageViewerData.targetHeight=Math.ceil(ei.imageViewerData.targetWidth/ratio);
		}
		if(ei.imageViewerData.targetHeight>h){
			ei.imageViewerData.targetHeight=h;
			ei.imageViewerData.targetWidth=Math.ceil(ei.imageViewerData.targetHeight*ratio);
		}
		// check we have not oversized the loading image:
		if(ei.imageViewerData.targetWidth>ei.imageViewerData.imageView.width){
			ei.imageViewerData.targetWidth=ei.imageViewerData.imageView.width;
			ei.imageViewerData.targetHeight=Math.ceil(ei.imageViewerData.targetWidth/ratio);
		}
		// create text label:
		var vText=d.createElement("div");
		vText.style.display="none";
		vText.className="imageViewerText";
		d.body.appendChild(vText);
		vText.innerHTML=ei.imageViewerData.imageView.name?ei.imageViewerData.imageView.name:"";
		//vText.style.height=img.offsetHeight+"px";
		ei.imageViewerData.textDiv=vText;
	}
	,removeImageViewer:function(){
		var ei=EnlargableImages;
		// if image element for viewer already exists?
		if(ei.imageViewerData&&ei.imageViewerData.img){
			// remove old image element in viewer:
			d.body.removeChild(ei.imageViewerData.img);
			ei.imageViewerData.img=null;
		}
		// if text element for viewer already exists?
		if(ei.imageViewerData&&ei.imageViewerData.textDiv){
			// remove old text:
			d.body.removeChild(ei.imageViewerData.textDiv);
			ei.imageViewerData.textDiv=null;
		}
	}
	/**
		Handles click on an image on the page.
		Creates image viewer.
	*/
	,onImgClick:function(e){
		var ei=EnlargableImages;
		// enter image viewer mode:
		ei.isImageViewerMode=true;
		// get screen/scroll positions:
		getScreenSize();
		getScreenScroll();
		// get clicked image (<img> HTML element):
		var img=Event.target(e);
		// create image viewer:
		ei.createImageViewer(null,img);
		// prepare fade animation:
		ei.redrawFade();	// fits fade over the <body>
		var f=d.getElementById("fade");
		CSS.setOpacity(f,0);
		CSS.a(f,"visible");

		// start fade animation:
		ei.countAnimZoom=0;
		ei.intervalAnimZoom=setInterval("EnlargableImages.animZoomIn()",25);
		// disable scrolling:
		CSS.a(d.body,"nonScrollable");
	}
	,init:function(){
		var ei=EnlargableImages;

		// attach image enlarge handlers to all <img> with viewIndex attribute:
		if(ei.isDivMode) var imgs=d.getElementsByTagName("DIV");
		else var imgs=d.getElementsByTagName("IMG");

		ei.imageViews=new Array();
		var k=0;
		for(var i=0;i<imgs.length;i++){
			var img=imgs[i];
			if(!img.getAttribute("enlargeURL"))continue;

			ei.imageViews.push(new ImageView(k,img.getAttribute("enlargeURL"),img.getAttribute("enlargeWidth"),img.getAttribute("enlargeHeight"),img.getAttribute("enlargeName")));
			img.setAttribute("enlargeIndex",k++);

			if(!img.getAttribute("enlargeListOnly")) Event.on(img,"click",ei.onImgClick);
			CSS.a(img,"enlargable");
			img.setAttribute("title","Нажмите, чтобы увеличить");
		}
		if(!ei.imageViews.length)return;
		if(!get("imageViewerControl")){
			var c=d.createElement("div");
			c.id="imageViewerControl";
			d.body.appendChild(c);

			var prev=d.createElement("div");
			prev.id="imageViewerControlPrev";
			c.appendChild(prev);

			var next=d.createElement("div");
			next.id="imageViewerControlNext";
			c.appendChild(next);

			var close=d.createElement("div");
			close.id="imageViewerControlClose";
			c.appendChild(close);

			var loader=d.createElement("div");
			loader.id="enlargableImageLoader";
			d.body.appendChild(loader);

			// create image viewer control handlers:
			if(d.getElementById("imageViewerControlPrev")){
				Event.on(d.getElementById("imageViewerControlPrev"),"click",ei.onImageViewerPrev);
				Event.on(d.getElementById("imageViewerControlNext"),"click",ei.onImageViewerNext);
				Event.on(d.getElementById("imageViewerControlClose"),"click",ei.onImageViewerClose);
			}
		}

		// listen to window scroll and resize:
		Event.on(self,"scroll",EnlargableImages.onWScroll);
		Event.on(self,"resize",EnlargableImages.onWResize);
	}
}
onReadys.push(EnlargableImages.init);
