//JavaScript Document
function validasi(form){
if (formlogin.user_email.value == ""){
alert("Please enter your email address");
formlogin.user_email.focus();
return false;
}
     
if (formlogin.user_pass.value == ""){
alert("Please enter your password");
formlogin.user_pass.focus();
return false;
}
return true;
}