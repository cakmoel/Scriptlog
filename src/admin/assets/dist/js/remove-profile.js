$(document).ready(function(){	
	$("#contactForm").submit(function(event){
		submitForm();
		return false;
	});
});

function deleteProfile(){
    $.ajax({
        type: "POST",
        url: "remove-profile.php",
        cache: false,
        data: $('form#deleteForm').serialize(),
        success: function(response){
			$("#profile").html(response)
			$("#profile-deleted").modal('hide');
		},
		error: function(){
			alert("Error");
		}
    });
}