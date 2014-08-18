function dwe(){
	d.write(String.fromCharCode.apply(this,arguments));
}
function trim(str){
	while(str.length&&str.charAt(0)==' '){
		str=str.substr(1);
	}
	while(str.length&&str.charAt(str.length-1)==' '){
		str=str.substr(0,str.length-1);
	}
	return str;
}