var videoIdIncrement=1;
function videoPlayer(url,imageURL,width,height,play){
	var height;
	if(width){
		var w=width+"";
		if(w.indexOf("%")==-1){
			if(!height)height=Math.round((width/16)*9)+19+"px";
			width+="px";
		}
	}
	else width="350px";
	if(!height)height="216px";
	if(width.indexOf("px")==-1)width+="px";
	if(height.indexOf("px")==-1)height+="px";
	document.writeln("<div class='video' style='width:"+width+";height:"+height+"'>");
	AC_FL_RunContent(
		'codebase','http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=9,0,28,0',
		'width', width,
		'height', height,
		'src','/mediaplayer/player?id=n0&amp;controlbar=bottom&amp;playlist=none&amp;autostart='+(play?"true":"false")+'&amp;bufferlength=1&amp;displayclick=play&amp;icons=true&amp;linktarget=_blank&amp;mute=false&amp;quality=true&amp;repeat=none&amp;resizing=true&amp;shuffle=false&amp;stretching=uniform&amp;volume=90&amp;screencolor=0x000000&amp;aboutlink=http://www.longtailvideo.com/players/&amp;file='+url+'&amp;image='+imageURL,
		'quality','high',
		'pluginspage', 'https://www.macromedia.com/go/getflashplayer',
		'align','left',
		'play','true',
		'id','videoObject'+(videoIdIncrement++),
		'loop','false',
		'scale','noscale',
		//'background','0xffffff',
		'devicefont', 'false',
		'menu', 'false',
		'allowScriptAccess','always',
		'allowfullscreen','true',
		'movie', '/mediaplayer/player?id=n0&amp;controlbar=bottom&amp;playlist=none&amp;autostart='+(play?"true":"false")+'&amp;bufferlength=1&amp;displayclick=play&amp;icons=true&amp;linktarget=_blank&amp;mute=false&amp;quality=true&amp;repeat=none&amp;resizing=true&amp;shuffle=false&amp;stretching=uniform&amp;volume=90&amp;screencolor=0x000000&amp;aboutlink=http://www.longtailvideo.com/players/&amp;file='+url+'&amp;image='+imageURL,
		'salign', 'tl'
	);
	document.writeln("<div class='clear'></div>");
	document.writeln("</div>");
}