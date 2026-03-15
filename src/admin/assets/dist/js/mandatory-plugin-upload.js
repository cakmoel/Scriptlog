/**
 * Make plugin file upload mandatory
 * @links https://stackoverflow.com/questions/23949148/how-to-make-file-upload-field-mandatory
 * 
 */
function mandatoryPluginUpload()
{
    if($('#pluginUploaded')[0].files.length === 0){
        alert("Plugin attachment required!");
        $('#pluginUploaded').focus();

        return false;
    }

}
