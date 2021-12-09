<?php 
require dirname(__FILE__) . '/functions.php'; 
?>
<!--
Author: W3layouts
Author URL: http://w3layouts.com
License: Creative Commons Attribution 3.0 Unported
License URL: http://creativecommons.org/licenses/by/3.0/
-->
<!DOCTYPE html>
<html lang="en">
<head>
<title>404 Error Page</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="keywords" content="" />
<script type="application/x-javascript"> 
addEventListener("load", function() { setTimeout(hideURLbar, 0); }, false); 
function hideURLbar(){ window.scrollTo(0,1); } 
</script>
<!-- Custom Theme files -->
<link href="<?= theme_dir(); ?>assets/css/error-page.css" rel="stylesheet" type="text/css" media="all" />
<link rel="shortcut icon" href="<?= app_url() . '/favicon.ico'; ?>">
<!-- web font -->
<link href="//fonts.googleapis.com/css?family=Josefin+Sans" rel="stylesheet">
<link href='//fonts.googleapis.com/css?family=Roboto+Condensed:400,300,300italic,400italic,700,700italic' rel='stylesheet' type='text/css'>
<!-- //web font -->
</head>
<body>
<!--mian-content-->
<h1>404 Error Page</h1>
	<div class="main-wthree">
		<h2>404</h2>
		<p><span class="sub-agileinfo">Whoops! </span>That page can't be found.</p>
		<!--form-->
			<form class="newsletter" action="#" method="post"></form>

		<!--//form-->
	</div>
<!--//mian-content-->
<!-- copyright -->
	<div class="copyright-w3-agile">
		<p> 
        <?php 

          $starYear = 2013;
          $thisYear = date ( "Y" );
          
          if ($starYear == $thisYear) {
             
              echo $starYear;
             
          } else {
              
              echo " {$starYear} &#8211; {$thisYear} ";
           }
                     
             echo "Scriptlog";
          
        ?>    
        | Design by <a href="http://w3layouts.com/" target="_blank">W3layouts</a></p>
	</div>
<!-- //copyright --> 

</body>
</html>