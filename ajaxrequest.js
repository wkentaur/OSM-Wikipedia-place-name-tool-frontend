
function MyAjaxRequest(target_div,file,check_div)
{
var MyHttpRequest = false;
var MyHttpLoading = '..'; 
var ErrorMSG = 'Sorry - No XMLHTTP support in your browser, buy a newspaper instead';

if(check_div)
{
var check_value = document.getElementById(check_div).value;
}
else
{
var check_value = '';
}


if(window.XMLHttpRequest) 
{
try
{
MyHttpRequest = new XMLHttpRequest();
}
catch(e)
{
MyHttpRequest = false;
}
}
else if(window.ActiveXObject) // IE
{
try
{
MyHttpRequest = new ActiveXObject("Msxml2.XMLHTTP");
}
catch(e)
{
try
{
MyHttpRequest = new ActiveXObject("Microsoft.XMLHTTP");
}
catch(e)
{
MyHttpRequest = false;
}
}
}
else
{
MyHttpRequest = false;
}



if(MyHttpRequest) 
{
var random = Math.random() * Date.parse(new Date()); 

var file_array = file.split('.'); 
if(file_array[1] == 'php') 
{
  var query_string = '?rand=' + random;
}
else if(file_array[1] == 'htm' || file_array[1] == 'html') 
{
  var query_string = '';
}
else 
{
  var query_string = check_value + '&rand=' + random;
}


MyHttpRequest.open("get", url_encode(file + query_string), true); 


MyHttpRequest.onreadystatechange = function ()
{
if(MyHttpRequest.readyState == 4) 
{
document.getElementById(target_div).innerHTML = MyHttpRequest.responseText; 
}
else
{
document.getElementById(target_div).innerHTML = MyHttpLoading; 
}
}
MyHttpRequest.send(null);
}
else 
{
document.getElementById(target_div).innerHTML = ErrorMSG; 
}
}



function url_encode(string)
{
var string;
var safechars = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz/-_.&?=";
var hex = "0123456789ABCDEF";
var encoded_string = "";
for(var i = 0; i < string.length; i++)
{
var character = string.charAt(i);
if(character == " ")
{
encoded_string += "+";
}
else if(safechars.indexOf(character) != -1)
{
encoded_string += character;
}
else
{
var hexchar = character.charCodeAt(0);
if(hexchar > 255)
{
encoded_string += "+";
}
else
{
encoded_string += "%";
encoded_string += hex.charAt((hexchar >> 4) & 0xF);
encoded_string += hex.charAt(hexchar & 0xF);
}
}
}
return encoded_string;
}

// end .js file