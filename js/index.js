$(document).ready(function() {
	$("#signup").click(function() {
		$("#login_box h2").html("Sign Up");
		$("form#login_form").hide();
		$("form#signup_form").show();
		return false;
	});

	$("#login").click(function() {
		$("#login_box h2").html("Log In");
		$("form#signup_form").hide();
		$("form#login_form").show();
		return false;
	});

	$("#signup_form input[type='submit']").click(function() {
		var email_val = $("#email_signup").val();
		var username_val = $("#username_signup").val();
		var password_val = $("#password_signup").val();

		$.post(siteroot + "/signup/", 
			{ email: email_val, username: username_val, password: password_val },
			function(data) {
				if (data.statuscode == "done") {
					$("#login_box h2").html("Thank You");
					$("form#signup_form").hide();
					$("#thankyou").show();
				}
			}, "json");

		return false;
	});
});
