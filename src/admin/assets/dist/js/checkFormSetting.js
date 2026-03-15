/**
 * Check form setting
 * validate general setting form field is being filled or not
 * 
 * @param {*} form 
 */

function checkFormSetting(form)
{

  if (scriptlogForm.site_title.value == "") {

     alert("Please enter your site title");
     scriptlogForm.site_title.focus();
     return false;

  }

  if (scriptlogForm.app_url.value == "") {

    alert("Please enter your site address");
    scriptlogForm.app_url.focus();
    return false;

  }

  if (scriptlogForm.email.value == "") {

     alert("Please enter your email address");
     scriptlogForm.email.focus();
     return false;

  }

  return true;

}