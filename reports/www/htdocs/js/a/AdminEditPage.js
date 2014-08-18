var curLanguageID=cookieGet("adminLanguageID")!=null?cookieGet("adminLanguageID"):"ru";
function setLanguage(id){
	document.getElementById("lang_"+curLanguageID).style.display="none";
	document.getElementById("tab_"+curLanguageID).className="";
	document.getElementById("lang_"+id).style.display="block";
	document.getElementById("tab_"+id).className="sel";
	curLanguageID=id;
	cookieSet("adminLanguageID",id,0);
}