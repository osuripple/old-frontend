<?php
// Get functions
require_once './inc/functions.php';
// Frontend stuff
// We're using ob_start to safely send headers while we're processing the script initially.
ob_start();
// Start session with user if we got a valid cookie.
startSessionIfNotStarted();
$c = new RememberCookieHandler();
if ($c->Check()) {
	$c->Validate();
}
// Redirect to 2FA block page if needed
redirect2FA();

// CONTROLLER SYSTEM v2
$model = 'old';
if (isset($_GET['p'])) {
	$found = false;
	foreach ($pages as $page) {
		if (defined(get_class($page).'::PageID') && $page::PageID == $_GET['p']) {
			$found = true;
			$model = $page;
			$title = '<title>'.$page::Title.'</title>';
			$p = $page::PageID;
			if (defined(get_class($page).'::LoggedIn')) {
				if ($page::LoggedIn) {
					clir();
				} else {
					clir(true, 'index.php?p=1&e=1');
				}
			}
			break;
		}
	}
	if (!$found) {
		if (isset($_GET['p']) && !empty($_GET['p'])) {
			$p = $_GET['p'];
		} else {
			$p = 1;
		}
		$title = setTitle($p);
	}
} elseif (isset($_GET['u']) && !empty($_GET['u'])) {
	$title = setTitle('u');
	$p = 'u';
} elseif (isset($_GET['__PAGE__'])) {
	$pages_split = explode('/', $_GET['__PAGE__']);
	if (count($_GET['__PAGE__']) < 2) {
		$title = '<title>Ripple</title>';
		$p = 1;
	}
	$found = false;
	foreach ($pages as $page) {
		if ($page::URL == $pages_split[1]) {
			$found = true;
			$model = $page;
			$title = '<title>'.$page::Title.'</title>';
			break;
		}
	}
	if (!$found) {
		$p = 1;
		$title = '<title>Ripple</title>';
	}
} else {
	$p = 1;
	$title = '<title>Ripple</title>';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="">
    <meta name="author" content="">

    <!-- Dynamic title -->
    <?php echo $title; ?>

	<?php
if ($p == 27) {
	global $ServerStatusConfig;
	if ($ServerStatusConfig['netdata']['enable']) {
		echo '
						<!-- Netdata script -->
						<script type="text/javascript">var netdataServer = "'.$ServerStatusConfig['netdata']['server_url'].'";</script>
						<script type="text/javascript" src="'.$ServerStatusConfig['netdata']['server_url'].'/dashboard.js"></script>
				';
	}
}
?>

    <!-- Bootstrap Core CSS -->
    <link href="./css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap select CSS -->
    <link href="./css/bootstrap-select.min.css" rel="stylesheet">

    <!-- Slider CSS -->
    <link href="./css/slider.css" rel="stylesheet">

    <!-- Bootstrap Font Awesome Picker CSS -->
    <link href="./css/fontawesome-iconpicker.min.css" rel="stylesheet">

    <!-- Bootstrap Color Picker CSS -->
    <link href="./css/bootstrap-colorpicker.min.css" rel="stylesheet">

    <!-- SCEditor CSS -->
    <link rel="stylesheet" href="./css/themes/default.css" type="text/css" media="all" />

    <!-- Animate CSS -->
    <link rel="stylesheet" href="./css/animate.css">

    <!-- Custom CSS -->
    <link href="./css/style-desktop.css" rel="stylesheet">

    <!-- Favicon -->
    <link rel="icon" href="favicon.png"/>

    <meta name=viewport content="width=device-width, initial-scale=1">
</head>

<body>
    <!-- Navbar -->
    <?php printNavbar(); ?>

    <!-- Page content (< 100: Normal pages, >= 100: Admin CP pages) -->
    <?php
$status = '';
if ($model !== 'old') {
	P::Messages();
}
if ($p < 100) {
	// Normal page, print normal layout (will fix this in next commit, dw)
	echo '
        <div class="container">
            <div class="row">
                <div class="col-lg-12 text-center">
                    <div id="content">';
	if ($model === 'old') {
		printPage($p);
	} else {
		echo $status;
		checkMustHave($model);
		$model->P();
	}
	echo '
                    </div>
                </div>
            </div>
        </div>';
} else {
	// Admin cp page, print admin cp layout
	if ($model === 'old') {
		printPage($p);
	} else {
		echo $status;
		$model->P();
	}
}
?>

    <!-- jQuery -->
    <script src="./js/jquery.js"></script>

	<!-- User lookup -->
	<?php
		if (checkLoggedIn()) {
			APITokens::PrintScript();
			echo '<script src="/js/typeahead.min.js"></script>
				<script src="/js/userlookup.js"></script>';
		}
	?>

    <!-- Bootstrap Core JavaScript -->
    <script src="./js/bootstrap.min.js"></script>

    <!-- Bootstrap Select JavaScript -->
    <script src="./js/bootstrap-select.min.js"></script>

    <!-- Slider JavaScript -->
    <script src="./js/bootstrap-slider.js"></script>

    <!-- Bootstrap Font Awesome Picker JavaScript -->
    <script src="./js/fontawesome-iconpicker.min.js"></script>

    <!-- Bootstrap Color Picker JavaScript -->
    <script src="./js/bootstrap-colorpicker.min.js"></script>

    <!-- SCEditor JavaScript -->
    <script src="./js/jquery.sceditor.bbcode.js"></script>

    <!-- Custom JavaScript for every page -->
    <script type="text/javascript">
        // Initialize stuff
        $('.icp-auto').iconpicker();
        $('.colorpicker').colorpicker({format:"hex"});
        $('.sceditor').sceditor({plugins: "bbcode", resizeEnabled: false, toolbarExclude: "font,table,code,quote,ltr,rtl" , style: "css/jquery.sceditor.default.css"});
        $(".spoiler-trigger").click(function() {$(this).parent().next().collapse('toggle');});
		//$(".slider").slider()

        // Are you sure window
        function sure($redirect)
        {
            var r = confirm("Are you sure?");
            if (r == true) window.location.replace($redirect);
        }

        function reallysure($redirect)
        {
            var r = confirm("This action cannot be undone. Are you sure you want to continue?");
            if (r == true)
                r = confirm("Are you REALLY sure?");
                if (r == true)
                    window.location.replace($redirect);
        }
    </script>


    <!-- Custom JavaScript for this page here -->
    <?php
switch ($p) {
		// Admin cp - beta keys

	case 105:
		echo '
            <script type="text/javascript">
            var text = "Digital Insanity";

            for(var i in text) {
              if(text[i] === " ") {
                $(".wavetext").append( $("<span>").html("&nbsp;") );
              } else {
                $(".wavetext").append( $("<span>").text(text[i]) );
              }
            }
            </script>

            <script type="text/javascript">
                // Ripple insanity
                $("#addBetaKeyModal").on("shown.bs.modal", function () {
                    audio = new Audio("./audio/keygen.mp3");
                    audio.play();
                });

                $("#addBetaKeyModal").on("hidden.bs.modal", function () {
                    audio.pause();
                });
            </script>';
	break;
		// Admin cp - edit user

	case 103:
		echo '
                <script type="text/javascript">
                    function censorUserpage()
                    {
                        document.getElementsByName("up")[0].value = "[i]:peppy:Userpage reset by an admin.:peppy:[/i]";
                    }

                    function removeSilence()
                    {
                        document.getElementsByName("se")[0].value = 0;
                        document.getElementsByName("sr")[0].value = "";
                    }

					function updatePrivileges(meme = true) {
						var result = 0;
						$("input:checkbox[name=privilege]:checked").each(function(){
							result = Number(result) + Number($(this).val());
						});

						// Remove donor if needed
						var selectValue;
						if (result != '. (Privileges::UserDonor | Privileges::UserNormal | Privileges::UserPublic).') {
							selectValue = result & ~'.Privileges::UserDonor.'
						} else {
							selectValue = result;
						}

						$("#privileges-value").val(result);
						$("#privileges-group").val(selectValue);
						// bootstrap-select is a dank meme
						$("#privileges-group").selectpicker("refresh");
					}

					function groupUpdated() {
						var privileges = $("#privileges-group option:selected").val();
						if (privileges > -1) {
							$("input:checkbox[name=privilege]").each(function(){
								if ( ($(this).val() & privileges) > 0) {
									$(this).prop("checked", true);
								} else {
									$(this).prop("checked", false);
								}
							});
						}
						updatePrivileges();
					}
                </script>
                ';
	break;
	case 114:
		echo '
				<script type="text/javascript">
					function quickReportResponse(i)
					{
						var c;
						switch(i)
						{
							case 0: c = "Thank you for your bug report! That bug is now on our tasklist, we\'ll fix it as soon as possible. The status will change to \'closed\' when the bug will be fixed."; break;
							case 1: c = "Thank you for your bug report! That bugfix was already on our tasklist, we\'ll fix it as soon as possible."; break;
							case 2: c = "That bug is now fixed, you have contributed to make Ripple better! Thank you!"; break;
							case 3: c = "Thank you for your feature request! That feature is now on our tasklist and it\'ll be added as soon as possible. The status will change to \'closed\' when the feature will be added."; break;
							case 4: c = "Thank you for your feature request! A similar feature was already on our tasklist, we\'ll add it as soon as possible."; break;
							case 5: c = "Your feature has been added! You have contributed to make Ripple better! Thank you!"; break;
							case 6: c = "Please do not abuse our report system. If you keep sending fake/spam reports, your account will be banned."; break;
						}
						document.getElementsByName("r")[0].value = c;
					}
				</script>';
	break;
	case 22:
		echo '
				<script type="text/javascript">
					function changeTitlePlaceholder()
					{
						var c;
						if (document.getElementsByName("t")[0].value == 0)
							c = "Bug name";
						else
							c = "Feature request name";

						document.getElementsByName("n")[0].placeholder = c;
					}

					// Update title when the page is loaded
					window.onload = changeTitlePlaceholder;
				</script>';
	break;

	// Supporter page
	case 34:
		echo '
			<!-- <script src="./js/money.min.js"></script> -->
			<script src="./js/bitcoinprices.js"></script>
			<script type="text/javascript">
				// Called when slider changes
				function onSlide() {
					updatePrice(slider.getValue());
				};

				// Updates price in EUR/USD/GBP, months number and paypal.me link
				var updatePrice = function (months) {
					try {
						var priceEUR = Math.pow(months * 30 * 0.2, 0.70).toFixed(2);
						var str = "<b>"+months+"</b> months = <b>"+priceEUR+"€</b>"+"<br>";
						var priceUSD = bitcoinprices.convert(priceEUR, "EUR", "USD").toFixed(2);
						var priceMBTC = (bitcoinprices.convert(priceEUR, "EUR", "BTC")*1000).toFixed(4);
						str += "<i>("+priceUSD+"$ USD/"+priceMBTC+" mBTC)</i>"
						$("#supporter-btc").show();
					} catch(err) {
						var str = "<b>Move the slider above to show the price</b>";
						$("#supporter-btc").hide();
					}

					$("#supporter-prices").html(str);
					$("#supporter-btc-price").html(priceMBTC);
					$("#paypal-supporter-period").val(months+" months");
				};


				// Slider
				var slider = $(".slider").slider().on("slide", onSlide).data("slider");

				// Load currencies
				bitcoinprices.init({
					url: "https://api.bitcoinaverage.com/ticker/all",
					marketRateVariable: "24h_avg",
					currencies: ["BTC", "USD", "EUR"],
					defaultCurrency: "BTC",
				});

				// Initialize price for 1 month
				updatePrice(1);
			</script>
		';
	break;

	case 119:
	echo '
		<script type="text/javascript">
			function updatePrivileges() {
				var result = 0;
				$("input:checkbox[name=privileges]:checked").each(function(){
					result = Number(result) + Number($(this).val());
				});
				$("#privileges-value").attr("value", result);
			}
		</script>
	';
	break;
}

// Userpage JS
if (isset($_GET["u"]) && !isset($_GET["p"])) {
	echo '<script src="/js/user.js"></script>';
}
?>
</body>

</html>
<?php
// clear redirpage if we're not on login page
if ($p != 2) {
	unset($_SESSION['redirpage']);
}
ob_end_flush();
