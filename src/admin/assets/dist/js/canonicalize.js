/*! canonicalize URL
 * ====================
 * @author matt mastracci 
 * @see https://grack.com/blog/2009/11/17/absolutizing-url-in-javascript/
 */

function canonicalize(url) {

    var div = document.createElement('div');
    div.innerHTML = "<a></a>";
    div.firstChild.href = url; // Ensures that the href is properly escaped
    div.innerHTML = div.innerHTML; // Run the current innerHTML back through the parser
    return div.firstChild.href;

}
