var FX={};
FX.FREQUENCY=25;

/*********************
Fading
*********************/
FX.intervalFading=null;
FX.fades=new Array();
function FXFade(direction,el,opacity,duration,callback){
	this.direction=direction;
	this.el=el;
	this.opacity=opacity;
	this.duration=duration;
	this.step=0;
	this.callback=callback;
	this.startOpacity=0;

	this.startOpacity=CSS.getOpacity(el);
	if(direction>0 && this.opacity<this.startOpacity)this.opacity=this.startOpacity;
	else if(direction<0 && this.opacity>this.startOpacity)this.opacity=this.startOpacity;

	this.process=function(){
		var o=((this.step*FX.FREQUENCY)/this.duration)*(this.opacity-this.startOpacity)+this.startOpacity;
		if(this.direction<0){
			//var o=(1-(this.step*FX.FREQUENCY)/this.duration)*(this.startOpacity-this.opacity)+this.startOpacity;
			if(o<this.opacity)o=this.opacity;
		}
		else {
			//var o=((this.step*FX.FREQUENCY)/this.duration)*(this.opacity-this.startOpacity)+this.startOpacity;
			if(o>this.opacity)o=this.opacity;
		}

		CSS.setOpacity(el,o);
		if(o==this.opacity){
			if(this.callback)this.callback(this);
			return false;
		}

		this.step++;
		return true;
	}
};
FX.fadeIn=function(el,o,d,cb){
	FX.fades.push(new FXFade(1,el,o,d,cb));
	FX.startFading();
}
FX.fadeOut=function(el,o,d,cb){
	FX.fades.push(new FXFade(-1,el,o,d,cb));
	FX.startFading();
}
FX.processFades=function(){
	for(var i=0;i<FX.fades.length;i++){
		var f=FX.fades[i];
		if(!f)continue;
		if(!f.process()){
			FX.fades.splice(i,1);
			i--;
		}
	}
	if(!FX.fades.length)FX.stopFading();
}
FX.startFading=function(){
	FX.stopFading();
	// remove those fades on the same el:
	var els={};
	for(var i=FX.fades.length-1;i>=0;i--){
		var f=FX.fades[i];
		if(!f)continue;
		//if(!f.el.id)f.el.id="fade"+i;

		for(var j=i-1;j>=0;j--){
			var f2=FX.fades[j];
			if(!f2)continue;

			if(f2.el==f.el){
				// remove same element from q:
				//console.log("Removing "+f2.el);
				FX.fades.splice(j,1);
			}
		}

		
		/*if(els[f.el.id]){
			// remove same element from q:
			console.log("Removing "+f.el.id);
			FX.fades.splice(i,1);
			//i++;
		}
		else {
			console.log("Adding "+f.el.id);
			els[f.el.id]=true;
		}*/
	}
	FX.intervalFading=setInterval("FX.processFades()",FX.FREQUENCY);
}
FX.stopFading=function(){
	if(!FX.intervalFading)return;
	clearInterval(FX.intervalFading);
	FX.intervalFading=null;
}

/*********************
Moving
*********************/
FX.intervalMoving=null;
FX.moves=new Array();
function FXMove(el,x,y,duration,callback){
	this.el=el;
	this.x=x;
	this.y=y;
	this.xDir=0;
	this.yDir=0;
	this.duration=duration;
	this.step=0;
	this.callback=callback;
	this.startX=el.offsetLeft;
	this.startY=el.offsetTop;

	if(this.startX>this.x)this.xDir=-1;
	else if(this.startX<this.x)this.xDir=1;
	if(this.startY>this.y)this.yDir=-1;
	else if(this.startY<this.y)this.yDir=1;

	this.process=function(){
		var x=el.offsetLeft;
		var y=el.offsetTop;

		if(x!=this.x){
			var f=Math.sin((Math.PI/2)*(this.step*FX.FREQUENCY)/this.duration)*(this.x-this.startX);
			x=Math.round(this.startX+f);
		}
		if(y!=this.y){
			var f=Math.sin((Math.PI/2)*(this.step*FX.FREQUENCY)/this.duration)*(this.y-this.startY);
			y=Math.round(this.startY+f);
		}

		//console.log(x+","+y+" of "+this.x+","+this.y);

		var xOk=false;
		var yOk=false;
		if((this.xDir>0&&x>=this.x)||(this.xDir<0&&x<=this.x)||this.xDir==0)xOk=true;
		if((this.yDir>0&&y>=this.y)||(this.yDir<0&&y<=this.y)||this.yDir==0)yOk=true;

		if(xOk&&yOk){
			el.style.left=this.x+"px";
			el.style.top=this.y+"px";

			if(this.callback)this.callback(this);
			return false;
		}

		el.style.left=x+"px";
		el.style.top=y+"px";

		this.step++;
		return true;
	}
};
FX.move=function(el,x,y,duration,callback){
	FX.moves.push(new FXMove(el,x,y,duration,callback));
	FX.startMoving();
}
FX.processMoves=function(){
	for(var i=0;i<FX.moves.length;i++){
		var f=FX.moves[i];
		if(!f)continue;
		if(!f.process()){
			FX.moves.splice(i,1);
			i--;
		}
	}
	if(!FX.moves.length)FX.stopMoving();
}
FX.startMoving=function(){
	FX.stopMoving();
	// remove those fades on the same el:
	var els={};
	for(var i=FX.moves.length-1;i>=0;i--){
		var f=FX.moves[i];
		if(!f)continue;
		//if(!f.el.id)f.el.id="fade"+i;

		for(var j=i-1;j>=0;j--){
			var f2=FX.moves[j];
			if(!f2)continue;

			if(f2.el==f.el){
				// remove same element from q:
				FX.moves.splice(j,1);
			}
		}
	}
	FX.intervalMoving=setInterval("FX.processMoves()",FX.FREQUENCY);
}
FX.stopMoving=function(){
	if(!FX.intervalMoving)return;
	clearInterval(FX.intervalMoving);
	FX.intervalMoving=null;
}

/*********************
Image Loading
*********************/
FX.intervalImageLoading=null;
FX.imageLoaders=new Array();
function FXImageLoader(src,callback,param){
	this.interval;
	this.src=src;
	this.callback=callback;
	this.param=param;

	this.elFrame=d.createElement("div");
	this.elFrame.style.position="absolute";
	this.elFrame.style.top="-20000px";
	this.elFrame.style.width="1px";
	this.elFrame.style.height="1px";
	this.elFrame.style.overflow="hidden";
	d.body.appendChild(this.elFrame);

	this.el=d.createElement("img");
	this.elFrame.appendChild(this.el);
	this.el.src=src;
}
FX.loadImage=function(src,callback,param){
	if(!FX.imageLoaders)FX.imageLoaders=new Array();
	FX.imageLoaders.push(new FXImageLoader(src,callback,param));
	FX.startImageLoading();
}
FX.startImageLoading=function(){
	FX.stopImageLoading();
	FX.intervalImageLoading=setInterval("FX.processImageLoaders()",FX.FREQUENCY);
}
FX.stopImageLoading=function(){
	if(!FX.intervalImageLoading)return;
	clearInterval(FX.intervalImageLoading);
	FX.intervalImageLoading=null;
}
FX.processImageLoaders=function(src,callback){
	for(var i=0;i<FX.imageLoaders.length;i++){
		var f=FX.imageLoaders[i];
		if(FX.isImageLoaded(f.el)){
			FX.imageLoaders.splice(i,1);
			i--;
			if(f.callback)f.callback(f);
		}
	}
	if(!FX.imageLoaders.length)FX.stopImageLoading();
}
FX.isImageLoaded=function(img) {
	if(!img.complete)return false;
	if(typeof img.naturalWidth!="undefined"&&img.naturalWidth==0)return false;
	return true;
}

