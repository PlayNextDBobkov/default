var UserInternalPage={};
var UIP=UserInternalPage;
UIP.init=function(){
	if(UIP.isFirm){
		setInterval("UIP.checkOrders()",60000);
	}

	Event.on(self,"resize",UIP.onWResize);
	Event.on(self,"scroll",UIP.onWScroll);

	var f=get("rek1");
	f.setAttribute("top", Screen.absOffset(f,"offsetTop"));
}
UIP.onWResize=function(){
	Screen.getSize();
	UIP.placeRek1();
}
UIP.onWScroll=function(){
	Screen.getScroll();
	UIP.placeRek1();
}
UIP.placeRek1=function(){
	var f=get("rek1");
	var t=parseInt(f.getAttribute("top"));
	if(Screen.scrollTop+32>t)CSS.a(f,"fixed");
	else CSS.r(f,"fixed");
}
UIP.checkOrders=function(){
	var ajax=new Ajax();
	ajax.onResponse=function(x){
		var rs=eval('('+x.responseText+')');
		if(!rs.success)return;

		var o=get("firmCountOrders");
		var n=parseInt(o.innerHTML);
		if(n!=rs.count){
			o.innerHTML=rs.count;
			CSS.r(o,"hidden");
		}
	};

	ajax.send("/FirmCheckOrders.json");
}
UIP.posItem=function(entity,id,s,redirect){
	var f=get("UIPFormPosItem");
	f.id.value=id;
	f.entity.value=entity;
	f.pos.value=s.selectedIndex+1;
	if(redirect!=undefined)f.redirect.value=redirect;
	f.submit();
}
UIP.toggleItem=function(entity,id,prop,redirect){
	var f=get("UIPFormToggleItem");
	f.id.value=id;
	f.entity.value=entity;
	f.prop.value=prop;
	if(redirect!=undefined)f.redirect.value=redirect;
	f.submit();
}
UIP.delItem=function(entity,id,confirmText,redirect){
	if(!confirm((confirmText?confirmText:"Удалить данную запись?")))return;
	var f=get("UIPFormDelItem");
	f.id.value=id;
	f.entity.value=entity;
	if(redirect!=undefined)f.redirect.value=redirect;
	f.submit();
}
UIP.friendRequest=function(parent,customerId){
	var ajax=new Ajax();
	ajax.onResponse=function(x){
		var rs=eval('('+x.responseText+')');
		if(!rs.success)return;
		alert(rs.msg);

		CSS.r(parent,"busy");

		if(rs.isFriend){
			var el=get("friendCancel"+customerId);
			if(el) CSS.r(el,"hidden");

			var el=get("friendMsg"+customerId);
			if(el) CSS.r(el,"hidden");
		}
		else {
			var el=get("friendRequestSent"+customerId);
			if(el) CSS.r(el,"hidden");
		}

		var el=get("friendCancelBack"+customerId);
		if(el) CSS.a(el,"hidden");

		var el=get("friendRequest"+customerId);
		if(el)CSS.a(el,"hidden");

		var el=get("friend"+customerId);
		CSS.a(el,"friend");
	};

	CSS.a(parent,"busy");

	ajax.send("/FriendRequest.json",
		"id="+encodeURI(customerId)
		);
}
UIP.friendCancel=function(parent,customerId,isRequest,delFromList){
	if(!confirm(isRequest?"Отменить запрос на дружбу?":"Удалить пользователя из друзей?"))return;
	var ajax=new Ajax();
	ajax.onResponse=function(x){
		var rs=eval('('+x.responseText+')');
		if(!rs.success)return;

		if(delFromList){
			var el=get("friend"+customerId);
			CSS.a(el,"hidden");
		}
		else {
			CSS.r(parent,"busy");

			var el=get("friendCancel"+customerId);
			if(el)CSS.a(el,"hidden");

			var el=get("friendMsg"+customerId);
			if(el) CSS.a(el,"hidden");

			var el=get("friendRequestSent"+customerId);
			if(el)CSS.a(el,"hidden");

			var el=get("friendCancelBack"+customerId);
			if(el)CSS.a(el,"hidden");

			var el=get("friendRequest"+customerId);
			if(el)CSS.r(el,"hidden");
		}
	};

	CSS.a(parent,"busy");

	ajax.send("/FriendCancel.json",
		"id="+encodeURI(customerId)
		);
}
UIP.drive=function(parent,firmId){
	var ajax=new Ajax();
	ajax.onResponse=function(x){
		var rs=eval('('+x.responseText+')');
		if(!rs.success)return;

		CSS.r(parent,"busy");

		if(rs.yes==1){
			//var el=HTML.child(parent,"span");
			//if(el) el.innerHTML="This drives!";

			CSS.a(parent,"yes");
		}
		else {
			//var el=HTML.child(parent,"span");
			//if(el) el.innerHTML="This drives?";

			CSS.r(parent,"yes");
		}

		var el=HTML.child(parent,"div","count");
		if(el) el.innerHTML=rs.count;
	};

	CSS.a(parent,"busy");

	ajax.send("/MakeDrive.json",
		"firmId="+encodeURI(firmId)
		+"&yes="+(parent.className.indexOf("yes")>=0?0:1)
		);
}
UIP.favorite=function(parent,firmId){
	var ajax=new Ajax();
	ajax.onResponse=function(x){
		var rs=eval('('+x.responseText+')');
		if(!rs.success)return;

		CSS.r(parent,"busy");

		if(rs.yes==1){
			//var el=HTML.child(parent,"span");
			//if(el) el.innerHTML="Избранная!";
			parent.setAttribute("title", "Избранная компания!");

			CSS.a(parent,"yes");
		}
		else {
			//var el=HTML.child(parent,"span");
			//if(el) el.innerHTML="В избранное";
			parent.setAttribute("title", "В избранное!");

			CSS.r(parent,"yes");
		}
	};
	
	CSS.a(parent,"busy");

	ajax.send("/MakeFavorite.json",
		"firmId="+encodeURI(firmId)
		+"&yes="+(parent.className.indexOf("yes")>=0?0:1)
		);
}
onReadys.push(UIP.init);
