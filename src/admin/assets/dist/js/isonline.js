let ifConnected = window.navigator.onLine;
if (ifConnected) {
  document.getElementById("checkOnline").innerHTML = "Online";
  document.getElementById("checkOnline").style.color = "#7fff00";
} else {
  document.getElementById("checkOnline").innerHTML = "Offline";
  document.getElementById("checkOnline").style.color = "red";
}
setInterval(function(){ 
let ifConnected = window.navigator.onLine;
if (ifConnected) {
  document.getElementById("checkOnline").innerHTML = "Online";
  document.getElementById("checkOnline").style.color = "#7fff00";
} else {
  document.getElementById("checkOnline").innerHTML = "Offline";
  document.getElementById("checkOnline").style.color = "red";
}
}, 3000);