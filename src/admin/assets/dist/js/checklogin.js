// JavaScript Document
function validasi(form){
if (formlogin.login.value == ""){
alert("Please enter username or email address");
formlogin.login.focus();
return false;
}
     
if (formlogin.user_pass.value == ""){
alert("Please enter your password");
formlogin.user_pass.focus();
return false;
}
return true;
}