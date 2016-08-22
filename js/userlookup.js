var search = function(query, syncResults, asyncResults) {
	$.getJSON("/api/v1/users/lookup", {
		name: query,
	}, function(result) {
		if (result.code == 200) {
			asyncResults(result.users);
		}
	});
};
var displayer = function(a) {
	return a.username
}
var suggest = function(a) {
	return "<div><span class='avileft'><img src='https://a.ripple.moe/" + a.id + "' class='tinyavatar'></span> " + a.username + "</div>";
}

var fired = false;
$("#query").typeahead(
{
	highlight: true,
},
{
	source: search,
	limit: 10,
	display: displayer,
	templates: {
		suggestion: suggest,
	}
}).bind('typeahead:select', function(ev, suggestion) {
  fired = true;
  window.location.href = "/?u=" + suggestion.id;
}).keyup(function(event){
    if(event.keyCode == 13){
		setTimeout(function(){
			if (fired)
				return;
	        window.location.href = "/?u=" + encodeURIComponent($("#query").val());
		}, 200);
    }
});;
