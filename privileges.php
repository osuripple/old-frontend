<?php die(); require_once("inc/functions.php"); ?>
<html>
	<body style="width:20%;">
		<?php
		if (isset($_GET["meme"])) {
			$GLOBALS["db"]->execute("UPDATE users SET privileges = ? WHERE id = 1000", [$_GET["meme"]]);
			echo "<b>Orewa ochinchin to $_GET[meme]!</b><hr>";
		}
		?>
		<fieldset>
			<legend>Privileges</legend>
			<?php
				$privileges = [
					"UserPublic",
					"UserNormal",
					"UserDonor",
					"AdminAccessRAP",
					"AdminManageUsers",
					"AdminBanUsers",
					"AdminSilenceUsers",
					"AdminWipeUsers",
					"AdminManageBeatmaps",
					"AdminManageServers",
					"AdminManageSettings",
					"AdminManageBetaKeys",
					"AdminManageReports",
					"AdminManageDocs",
					"AdminManageBadges",
					"AdminViewRAPLogs",
					"AdminManagePrivileges",
				];

				foreach ($privileges as $i => $v) {
					$b = ($i > 0) ? (2 << ($i-1)) : 1;
					$c = $i == 0 ? "checked" : "";
					echo '<label><input name="privilege" value="'.$b.'" type="checkbox" onclick="update();" '.$c.'>'.$v.'</label><br>';
				}
			?>
		</fieldset>
		<br>
		<b>Result: <span id="result">1</span></b>
		<br>
		<a id="set-href" href="privileges.php?meme=1">Set</a>

		<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.0.0/jquery.min.js"></script>
		<script type="text/javascript">
			function update() {
				var result = 0;
				$("input:checkbox[name=privilege]:checked").each(function(){
					result = Number(result) + Number($(this).val());
				});
				$("#result").html(result);
				$("#set-href").attr("href", "privileges.php?meme="+result)
			}
		</script>
	</body>
</html>
