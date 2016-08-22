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
		startSessionIfNotStarted();
		P::GlobalAlert();
		P::MaintenanceStuff();
		$isSupporter = hasPrivilege(Privileges::UserDonor);
		if ($isSupporter) {
			$expire = $GLOBALS["db"]->fetch("SELECT donor_expire FROM users WHERE id = ?", [$_SESSION["userid"]]);
			if ($expire) {
				$expire = current($expire);
				if ($expire > time()) {
					$expireString = timeDifference(time(), $expire, false);
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
						<div align="center"><span class="label label-default">Coming soon</span></div>
						<div class="col-padding coming-soon">
							<h3><i class="fa fa-pencil"></i>	Custom badge editor</h3>
							<p>You will also (soon) be able to make <b>your own badge</b> for your user profile with <b>custom icon and text</b>! How cool is that?!?</p>
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
						<div class="slider" style="width: 100%;" data-slider-min="1" data-slider-max="24" data-slider-value="1" data-slider-tooltip="hide"></div><br>
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
								<tr><td><input type="hidden" name="on0" value="Period">Period</td></tr><tr><td>
								<select name="os0" id="paypal-supporter-period">
									<option value="1 months">1 months €3,51 EUR</option>
									<option value="2 months">2 months €5,69 EUR</option>
									<option value="3 months">3 months €7,56 EUR</option>
									<option value="4 months">4 months €9,25 EUR</option>
									<option value="5 months">5 months €10,81 EUR</option>
									<option value="6 months">6 months €12,29 EUR</option>
									<option value="7 months">7 months €13,69 EUR</option>
									<option value="8 months">8 months €15,03 EUR</option>
									<option value="9 months">9 months €16,32 EUR</option>
									<option value="10 months">10 months €17,57 EUR</option>
									<option value="11 months">11 months €18,78 EUR</option>
									<option value="12 months">12 months €19,96 EUR</option>
									<option value="13 months">13 months €21,11 EUR</option>
									<option value="14 months">14 months €22,23 EUR</option>
									<option value="15 months">15 months €23,33 EUR</option>
									<option value="16 months">16 months €24,41 EUR</option>
									<option value="17 months">17 months €25,47 EUR</option>
									<option value="18 months">18 months €26,51 EUR</option>
									<option value="19 months">19 months €27,53 EUR</option>
									<option value="20 months">20 months €28,54 EUR</option>
									<option value="21 months">21 months €29,53 EUR</option>
									<option value="22 months">22 months €30,51 EUR</option>
									<option value="23 months">23 months €31,47 EUR</option>
									<option value="24 months">24 months €32,42 EUR</option>
								</select>

								<!-- Memini divertentini for more than 10 options -->
								<input type="hidden" name="option_index" value="0">
								<input type="hidden" name="option_select0" value="1 months">
								<input type="hidden" name="option_amount0" value="3.51">
								<input type="hidden" name="option_select1" value="2 months">
								<input type="hidden" name="option_amount1" value="5.69">
								<input type="hidden" name="option_select2" value="3 months">
								<input type="hidden" name="option_amount2" value="7.56">
								<input type="hidden" name="option_select3" value="4 months">
								<input type="hidden" name="option_amount3" value="9.25">
								<input type="hidden" name="option_select4" value="5 months">
								<input type="hidden" name="option_amount4" value="10.81">
								<input type="hidden" name="option_select5" value="6 months">
								<input type="hidden" name="option_amount5" value="12.29">
								<input type="hidden" name="option_select6" value="7 months">
								<input type="hidden" name="option_amount6" value="13.69">
								<input type="hidden" name="option_select7" value="8 months">
								<input type="hidden" name="option_amount7" value="15.03">
								<input type="hidden" name="option_select8" value="9 months">
								<input type="hidden" name="option_amount8" value="16.32">
								<input type="hidden" name="option_select9" value="10 months">
								<input type="hidden" name="option_amount9" value="17.57">
								<input type="hidden" name="option_select10" value="11 months">
								<input type="hidden" name="option_amount10" value="18.78">
								<input type="hidden" name="option_select11" value="12 months">
								<input type="hidden" name="option_amount11" value="19.96">
								<input type="hidden" name="option_select12" value="13 months">
								<input type="hidden" name="option_amount12" value="21.11">
								<input type="hidden" name="option_select13" value="14 months">
								<input type="hidden" name="option_amount13" value="22.23">
								<input type="hidden" name="option_select14" value="15 months">
								<input type="hidden" name="option_amount14" value="23.33">
								<input type="hidden" name="option_select15" value="16 months">
								<input type="hidden" name="option_amount15" value="24.41">
								<input type="hidden" name="option_select16" value="17 months">
								<input type="hidden" name="option_amount16" value="25.47">
								<input type="hidden" name="option_select17" value="18 months">
								<input type="hidden" name="option_amount17" value="26.51">
								<input type="hidden" name="option_select18" value="19 months">
								<input type="hidden" name="option_amount18" value="27.53">
								<input type="hidden" name="option_select19" value="20 months">
								<input type="hidden" name="option_amount19" value="28.54">
								<input type="hidden" name="option_select20" value="21 months">
								<input type="hidden" name="option_amount20" value="29.53">
								<input type="hidden" name="option_select21" value="22 months">
								<input type="hidden" name="option_amount21" value="30.51">
								<input type="hidden" name="option_select22" value="23 months">
								<input type="hidden" name="option_amount22" value="31.47">
								<input type="hidden" name="option_select23" value="24 months">
								<input type="hidden" name="option_amount23" value="32.42">

								</td></tr>
								<tr><td><input type="hidden" name="on1" value="Ripple user to give donor">Ripple user to give donor</td></tr><tr><td><input type="text" name="os1" maxlength="200" value="'.$_SESSION["username"].'"></td></tr>
								</table>
								<input type="hidden" name="currency_code" value="EUR">
								<input type="hidden" name="business" value="to@nyo.zz.mu">
								<input type="hidden" name="cmd" value="_xclick">
								<input type="hidden" name="display" value="0">
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
							<b><span style="text-decoration: underline;">Write your Ripple username in donation\'s message!</span></b>
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

	public function D() {
		startSessionIfNotStarted();
		$d = $this->DoGetData();
		if (isset($d["error"])) {
			addError($d['error']);
			redirect("index.php?p=29");
		} else {
			// No errors, log new IP address
			logIP($_SESSION["userid"]);
			redirect("index.php?p=1");
		}
	}

	public function DoGetData() {
		try {
			// Get tokenID
			$token = $GLOBALS["db"]->fetch("SELECT * FROM 2fa WHERE userid = ? AND ip = ? AND token = ?", [$_SESSION["userid"], getIp(), $_POST["token"]]);
			// Make sure the token exists
			if (!$token) {
				throw new Exception("Invalid 2FA code.");
			}
			// Make sure the token is not expired
			if ($token["expire"] < time()) {
				throw new Exception("Your 2FA token is expired. Please enter the new code you've just received.");
			}
			// Everything seems fine, delete 2FA token to allow this session
			$GLOBALS["db"]->execute("DELETE FROM 2fa WHERE id = ?", [$token["id"]]);
		} catch (Exception $e) {
			$ret["error"] = $e->getMessage();
		}

		return $ret;
	}
}
