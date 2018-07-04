<?php

class Support {
	const PageID = 34;
	const URL = 'support';
	const Title = 'Ripple - Support us';
	const LoggedIn = true;
	public $error_messages = [];
	public $mh_GET = [];
	public $mh_POST = [];

	public function P() {
		global $paypalConfig;
		$maxDonor = 24;
		startSessionIfNotStarted();
		P::GlobalAlert();
		P::MaintenanceStuff();
		$isSupporter = hasPrivilege(Privileges::UserDonor);
		if ($isSupporter) {
			$expire = $GLOBALS["db"]->fetch("SELECT donor_expire FROM users WHERE id = ?", [$_SESSION["userid"]]);
			if ($expire) {
				$expire = current($expire);
				if ($expire > time()) {
					$expireString = trim(timeDifference(time(), $expire, false));
				} else {
					$expireString = 'less than one hour';
				}
			} else {
				$expire = 0;
			}
		}
		echo '
		<div id="content">
			<div align="center">
				<div class="row">
					<h1 class="support-color"><i class="fa fa-heart animated infinite pulse"></i>	Support us</h1>
					<br>
					<p class="half">
						We, the <b>Ripple developers</b>, run Ripple in our spare time, and keeping it up is <b>quite expensive</b>, both in money and effort. We have been building Ripple ever since <b>August 2015</b>, and we are doing our best every day to fix bugs, implement features, and generally speaking <b>keeping the server up</b>. If you like Ripple, you should <b>really consider supporting us</b>: it\'d help us a lot to pay all the expenses that come with having such a <b>rapidly growing service</b>.
					</p>
				</div>
				<div class="row"><hr>';
					if ($isSupporter) {
						echo '
						<h2><i class="fa fa-smile-o"></i>	You are a donor!</h2>
						<b>Your Donor tag expires in '.$expireString.'!</b><br>
						Thank you for donating! :3
						';
					} else {
						echo '
						<h2><i class="fa fa-frown-o"></i>	You are not a donor</h2>
						<b>You don\'t have a Donor tag at the moment.</b><br>
						Follow the instructions below to get it.
						';
					}
				echo '</div>
				<hr>
				<h2><i class="fa fa-gift"></i>	What do donors get?</h2>
				<div class="row grid-divider" align="left">
					<div class="col-sm-4">
						<div class="col-padding">
							<h3><i class="fa fa-paint-brush"></i>	Unique username color</h3>
							<p>Just like in osu!, you will get a <b>shiny yellow username</b> in the game chat, to remind everyone you\'re one of the cool guys who is helping us out economically.</p>
						</div>
					</div>
					<div class="col-sm-4">
						<div class="col-padding">
							<h3><i class="fa fa-certificate"></i>	Donor badge</h3>
							<p>Again, just like in osu!, you will get a <b>donor badge</b> on your profile, to show everyone you\'re supporting us.</p>
						</div>
					</div>
					<div class="col-sm-4">
						<div class="col-padding">
							<h3><i class="fa fa-pencil"></i>	Custom badge editor</h3>
							<p>You will also be able to make <b>your own badge</b> for your user profile with <b>custom icon and text</b>! How cool is that?!?</p>
						</div>
					</div>
				</div>
				<hr>
				<div class="row grid-divider" align="left">
					<div class="col-sm-4">
						<div class="col-padding">
							<h3><i class="fa fa-area-chart"></i>	Friends ranking</h3>
							<p>Want to compete with your <b>friends</b>? Not a problem if you have donated! It takes an <b>enormous amount of resources</b> to make friend rankings, but we\'d do anything for our beloved donors &lt;3</p>
						</div>
					</div>
					<div class="col-sm-4">
						<div align="center"><span class="label label-default">Coming soon</span></div>
						<div class="col-padding coming-soon">
							<h3><i class="fa fa-quote-left"></i>	Username change</h3>
							<p>Everyone gets a free username change. If you are a donor, you will be able to <b>change your username twice!</b></p>
						</div>
					</div>
					<div class="col-sm-4">
						<div class="col-padding">
							<h3><i class="fa fa-comments"></i>	Discord privileges</h3>
							<p>You\'ll get exclusive access to the "<b>#donators</b>" text and voice <b>channels</b>, you\'ll be able to change your discord nickname and you\'ll get a custom <b>role</b> with custom username <b>color</b>!</p>
						</div>
					</div>
				</div>
				<div class="row">
					<hr>
					<h2><i class="fa fa-credit-card"></i>	How do I become a donor?</h2>
					<h4>Read carefully</h4>
					<p class="half">
						You may donate through either <b>PayPal</b> (or credit/debit card linked to PayPal) or <b>Bitcoin</b>. Use the <b>slider</b> below to choose the amount of months you\'d like to have the donor benefits, and the cost will be instantly calculated. Remember that if, for instance, you choose to donate € 4 instead of € 3.51, you will only be given one donor month.<br><br>
						<div class="slider" style="width: 100%;" data-slider-min="1" data-slider-max="'.$maxDonor.'" data-slider-value="1" data-slider-tooltip="hide"></div><br>
						<span id="supporter-prices"></span>
					</p>
					<hr>
				</div>
				<div class="row" align="center">
					<div class="col-sm-6">
						<div class="col-padding">
							<h3><i class="fa fa-paypal"></i>	Prefer PayPal?</h3></h3>
							<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_self">
								<table>
								<tr><td><input type="hidden" name="on0" value="Period">Period</td></tr><tr><td>';

								echo '<select name="os0" id="paypal-supporter-period">';
								for ($i=0; $i < $maxDonor; $i++) { 
									echo '<option value="'.($i+1).' months">'.($i+1).' months - €'.getDonorPrice($i+1).'</option>';
								}
								echo '</select>';

								//echo '<input type="hidden" name="option_index" value="0">';
								for ($i=0; $i < $maxDonor; $i++) { 
									echo '<input type="hidden" name="option_select'.$i.'" value="'.($i+1).' months">';
									echo '<input type="hidden" name="option_amount'.$i.'" value="'.getDonorPrice($i+1).'">';
								}

								echo '</td></tr>
								<tr><td><input type="hidden" name="on1" value="Ripple user to give donor">Ripple user to give donor</td></tr><tr><td><input type="text" name="os1" maxlength="200" value="'.$_SESSION["username"].'"></td></tr>
								</table>
								<!-- <input type="hidden" name="currency_code" value="EUR"> -->
								<input type="hidden" name="business" value="'.$paypalConfig["email"].'">
								<input type="hidden" name="cmd" value="_xclick">
								<!-- <input type="hidden" name="display" value="0"> -->

								<input type="hidden" name="lc" value="GB">
								<!-- <input type="hidden" name="button_subtype" value="services"> -->
								<input type="hidden" name="no_note" value="0">
								<input type="hidden" name="currency_code" value="EUR">
								<br>
								<button type="submit" class="btn btn-danger" name="submit"><i class="fa fa-heart"></i>	Donate now</button>
								<!-- <input type="image" src="https://www.paypalobjects.com/it_IT/IT/i/btn/btn_buynowCC_LG.gif" border="0" name="submit"> -->
								<img alt="" border="0" src="https://www.paypalobjects.com/it_IT/i/scr/pixel.gif" width="1" height="1">
							</form>
						</div>
					</div>
					<div class="col-sm-6">
						<div class="col-padding">
							<h3><i class="fa fa-btc"></i>	Prefer Bitcoin?</h3>
							<b id="supporter-btc" hidden>Send <span id="supporter-btc-price"></span> mBTC to this Bitcoin address:</b><br>
							132HMmzADGG7fGfwuqUSP7gahTWVLkfZLR<br>
							<b>Afterwards, please send an email to <u><a href="mailto:howl@ripple.moe">howl@ripple.moe</a></u> containing the transaction hash!</b>
						</div>
					</div>
				</div>
				<div class="row">
					<hr>
					<h2><i class="fa fa-question-circle"></i>	I\'ve donated, and now?</h2>
					<p class="half">
						You\'ll have to wait until we verify and process your payment. It can take up to <b>12 hours</b>. If 12 hours have passed and you still haven\'t received your Donor tag, contact a <b>Dev/Community Manager</b> in our Discord server or send an email to <b>support@ripple.moe</b>.
						Once we have processed your payment, you\'ll receive an <b>email</b> to the address you\'ve used to sign up and you\'ll get <b>all the donor privileges</b>, except the <b>Discord</b> ones. To get the Discord donor privileges, <b>hover your name</b> in the navbar and select the "<b>Discord donor</b>" option (you\'ll be able to see it once you get the donor privileges).
					</p>
				</div>
			</div>


		</div>';
	}
}
