/**
	Performs all Ajax operations with XML/JSON asyncronius requests.
*/
function Ajax(){
	/**
		Sends request for XML data.
	*/
	this.send=function(url,str,method){
		if(this.x==null)return;

		this.retries=10;
		this.responseText=this.r=null;
		if(this.x.readyState!=0&&this.x.readyState!=4)this.x.abort();
		if(method==null)method="POST";
		this.x.open(method,url,true);
		this.x.onreadystatechange=this.delegate(this,this.onReadyStateChange);
		if(str!=null)this.x.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
		this.x.send(str);
	}

	/**
		Default empty respose handler.
	*/
	this.onResponse=function(x){
		alert("Empty response handler!\nReceived:\n"+x.responseText);
	}

	/**
		Processes state changes of XMLHttpRequest.
	*/
	this.onReadyStateChange=function(){
		if(this.x==null)return;
		if(this.x.readyState==4&&this.x.responseText!=null){
			switch(this.x.status){
				case 403:
				case 302:
				case 500:
				case 502:
				case 503:
					if(this.retries>0)this.retries--;
					break;
				case 200:
					this.responseText=this.r=this.x.responseText;
					/*if(this.r.charAt(0)!="<"&&(this.r.indexOf("sendRPCDone")!=-1||this.r.indexOf("Suggest_apply")!=-1)){
						eval(this.r);
					}*/
			}
			this.onResponse(this.x);
		}
	}

	/**
		Returns valid Msxml2.XMLHTTP/Microsoft.XMLHTTP/XMLHttpRequest.
	*/
	this.getXMLHttpRequest=function(){
		var a=null;
		try{
			a=new ActiveXObject("Msxml2.XMLHTTP");
		}
		catch(b){
			try{
				a=new ActiveXObject("Microsoft.XMLHTTP");
			}
			catch(c){
				a=null;
			}
		}
		if(!a&&typeof XMLHttpRequest!="undefined")a=new XMLHttpRequest;
		return a;
	}

	/**
		Returns a delegate function.
		This means the $method of $instance will be invoked when the returned
		function is called.
	*/
	this.delegate=function(instance,method){
		var args=arguments;
		return function(){
			if(arguments.concat)args=arguments.concat(args);
			return method.apply(instance,args);
		}
	}

	// init:
	if(this.x==null)this.x=this.xmlHttpRequest=this.getXMLHttpRequest();
}