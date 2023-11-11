/**
 * Mandatory Theme Upload
 * 
 * @function mandatoryThemeUpload()
 * @return boolean
 */
function mandatoryThemeUpload()
{
    if($('#themeUploaded')[0].files.length === 0){
        alert("Theme attachment required!");
        $('#themeUploaded').focus();

        return false;
    }

}