function submitComment(post_url, request_method, form_data) {

	let csrfToken = $('input[name="csrf"]').val(); // Get the token value
	form_data += "&csrf=" + csrfToken; // Append it to the form data

	$.ajax({
		url: post_url,
		type: request_method,
		data: form_data
	}).done(function (data) {
		formSuccess(data);
	}).fail(formError); // Pass the AJAX error object to formError
}

function formSuccess(response) {
	let msg = $.parseJSON(response);
	$("#commentForm")[0].reset();
	$("#success_message").fadeIn().html(msg.success_message);
	setTimeout(function () {
		$("#success_message").fadeOut("slow");
	}, 2000);

	// Consider fetching and displaying updated comments here
}

function formError(jqXHR, textStatus, errorThrown) {
	try {
		let msg = $.parseJSON(jqXHR.responseText);
		
		if (msg?.errors) { // Optional chaining to check for errors property
			// Display specific error messages from the server
			$.each(msg.errors, function (field, error) {
				$("#" + field).addClass("is-invalid");
				$("#error_message").show().html(error);
			});
		} else {
			// Handle other error scenarios based on textStatus and errorThrown
			switch (textStatus) {
				case "timeout":
					$("#error_message").show().html("The request timed out. Please try again.");
					break;
				case "abort":
					// Handle aborted requests (e.g., due to form submission cancellation)
					break;
				case "parsererror":
					$("#error_message").show().html("An error occurred while parsing the server response.");
					break;
				default:
					// Generic error message for other cases
					$("#error_message").show().html("An error occurred. Please try again later.");
					break;
			}
			console.error("AJAX error:", textStatus, errorThrown); // Log for debugging
		}
	} catch (e) {
		// Handle parsing errors
		console.error("Error parsing AJAX error response:", e);
		$("#error_message").show().html("An unexpected error occurred. Please contact support.");
	}
}

// checking author name - validation for author name
function checkingAuthorName(name) {
	const regex = /^[A-Z \'.-]{2,90}$/i;
	return regex.test(name);
}

$(function () {
	$("#commentForm").on('submit', function (event) {
		event.preventDefault(); // Prevent default form submission

		let post_url = $(this).attr("action");
		let request_method = $(this).attr("method");
		let form_data = $(this).serialize();

		// Perform validation
		let comment = $("#comment").val();
		let name = $("#name").val();
		let email = $("#email").val();

		// validation rules
		let isValid = true;

		// 1. Comment: non-empty 
		if (comment.trim() === "") {
			$("#comment").addClass("is-invalid");
			$("#error_message").show().html("Please enter your comment.");
			isValid = false;
		} else {
			$("#comment").removeClass("is-invalid");
		}

		// 2. Name: non-empty, full name format
		if (name.trim() === "") {
			$("#name").addClass("is-invalid");
			$("#error_message").show().html("Please enter your full name.");
			isValid = false;
		} else if (!checkingAuthorName(name)) {
			$("#name").addClass("is-invalid");
			$("#error_message").show().html("Please enter a valid name (2-90 characters, starting with an uppercase letter, spaces, apostrophes, periods, and hyphens allowed).");
			isValid = false;
		} else {
			$("#name").removeClass("is-invalid");
		}

		// 3. Email: non-empty, valid email format
		if (email.trim() === "") {
			$("#email").addClass("is-invalid");
			$("#error_message").show().html("Please enter your email address.");
			isValid = false;
		} else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { // Basic email validation
			$("#email").addClass("is-invalid");
			$("#error_message").show().html("Please enter a valid email address.");
			isValid = false;
		} else {
			$("#email").removeClass("is-invalid");
		}

		if (isValid) {
			submitComment(post_url, request_method, form_data);
		}

	});
});
