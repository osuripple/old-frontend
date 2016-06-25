<?php

class UserLookup {
	const PageID = 28;
	const URL = 'lookup';
	const Title = 'Ripple - User lookup';
	const LoggedIn = true;

	public function P() {
		?>
		<h1><i class="fa fa-search"></i>	User Lookup</h1><br>
		<form class="form-inline user-lookup-form">
			<fieldset>
				<div class="form-group" style="width:100%;">
					<input type="text" style="width:300px;" class="form-control" name="query" id="query" placeholder="Start typing an username...">
					<!--<button type="submit" class="btn btn-primary">Go!</button>-->
				</div>
			</fieldset>
		</form>
		<?php
		APITokens::PrintScript();
	}
}
