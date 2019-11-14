document.forms[0].addEventListener('submit', function( evt ) {
	
    var file = document.getElementById('file').files[0];

    if(file && file.size > 1048576) { // 1MB=1048576 & 2MB=2097152(this size is in bytes)
        
    	//Prevent default and display error
		document.getElementById('NotOk').innerHTML = 'Your file '+bytesToSize(file.size)+' is to big!';
        evt.preventDefault();
        
    } 
    
}, false);
		
function bytesToSize(bytes) {
   var sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
   if (bytes == 0) return '0 Bytes';
   var i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)));
   return Math.round(bytes / Math.pow(1024, i), 2) + ' ' + sizes[i];
}	