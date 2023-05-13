$(function(){
	$("#commentForm").on('submit', function(event){

		let post_url = $(this).attr("action"); //get form action url
		let request_method = $(this).attr("method"); //get form GET/POST method
		let form_data = $(this).serialize(); //Encode form elements for submission
	
		if (event.isDefaultPrevented()) {
			// handle the invalid form...
			$("#error_message").show().html("All Fields are Required");
	
		} else {
			// everything looks good!
			event.preventDefault();
			submitComment(post_url, request_method, form_data);
		}
	
	});
});

function submitComment(post_url, request_method, form_data){

	$.ajax({
		
		url : post_url,
		type: request_method,
		data : form_data

	}).done(function(data){
		
	   formSuccess(data);

	}).fail(function(data) {

		formError();

	});
	
}

function formSuccess(response) { 

 let msg = $.parseJSON(response);
 $("#commentForm")[0].reset();
 $("#success_message").fadeIn().html(msg.success_message);
 setTimeout(function(){
    $("#success_message").fadeOut("slow");
 }, 2000 );
}

function formError() {
	
	$("#commentForm").html(
		'<div class="alert alert-danger">Could not reach server, please try again later.</div>'
	  );
	
}