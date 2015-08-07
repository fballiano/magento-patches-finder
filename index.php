<?php

require "simple_html_dom.php";
$html = file_get_html('download');
date_default_timezone_set('UTC');
$lastmodification = time() - filemtime("download");
$hoursago = (int)date("H", $lastmodification);
$minago = (int)date("i", $lastmodification);

$releases_patches = array();
$releases = array();
foreach($html->find('.download-panes li', 1)->find('.download-releases .release-download') as $downloads) {
	$release = trim($matches[1]);
	$includedpatches = array();
	$tmp = (string)$downloads->innertext;
	preg_match_all("/SUPEE-\d+/", $tmp, $includedpatches);
	foreach ($downloads->find("strong") as $version) {
		if (preg_match("/^ver (.+)/", $version->innertext, $matches)) {
			$version = trim(str_replace("ver ", "", $version->innertext));
			$releases[$version] = @$includedpatches[0];
		}
	}
}
$releases["1.0"] = array();

$patches = array();
foreach($html->find('.download-releases') as $downloads) {
	$title = $downloads->find("h3");
	$title = $title[0]->innertext;
	if (!stripos($title, "patches")) continue;
	
	foreach ($downloads->find(".release-download") as $patch) {
		$i = 0;
		$patch_name = $patch->find("strong")[0]->innertext;
		$cat_id = explode("_", $patch->find("select")[0]->id);
		$cat_id = $cat_id[1];
		foreach ($patch->find("select option") as $patch_version) {
			if ($i++ == 0) continue;
			preg_match_all("(1\..\..\..|1\..\..)", $patch_version->innertext, $tmp);
			$start_version = $tmp[0][0];
			$end_version = $tmp[0][1];
			if (!$end_version) $end_version = $start_version;
			$patches[] = array(
				$start_version,
				$end_version,
				$patch_version->value,
				$patch_version->innertext,
				$patch_name,
				$cat_id
			);
		}
	}
}

$release_and_patches = array();
foreach ($releases as $release=>$includedpatches) {
	foreach ($patches as $patch) {
		if (in_array($patch[4], $includedpatches)) continue;
		
		$min = $patch[0];
		$max = $patch[1];
		
		$min = str_replace(".x", ".0", $min);
		$max = str_replace(".x", ".999", $max);
		
		if (version_compare($release, $min, ">=") and version_compare($release, $max, "<=")) {
			$release_and_patches[$release][] = $patch;
		}
	}
}

?><html>
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Magento Patches Finder</title>
<meta name="description" content="Looking for all security patches for a specific Magento version? Here you have the handy list and one click download!">
<link rel="shortcut icon" href="/magento-patches/favicon.ico" type="image/x-icon">
<link rel="icon" href="/magento-patches/favicon.ico" type="image/x-icon">
<meta property="og:type" content="website"/>
<meta property="og:site_name" content="Magento Patches Finder"/>
<meta property="og:locale" content="en_US" />
<meta property="og:image" content="http://fabrizioballiano.net/magento-patches/magento-security-patches-finder.jpg" />
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap-theme.min.css">
<style type="text/css">
	.patches {display:none}
	ul {padding-left: 20px}
	li i {margin-right: 5px}
	.navbar-fixed-bottom {padding-top: 5px}
</style>
</head>
<body>
	<div class="container" style="max-width:750px">
		<div class="page-header">
			<h1>Magento patches finder</h1>
		</div>
		
		Are you looking for all security patches for a specific Magento version?<br />
		<label>
			Select your Magento version
			<select id="releases" autofocus>
				<option></option>
				<?php foreach ($releases as $release=>$patches): ?>
					<option><?= $release ?></option>
				<?php endforeach ?>
			</select>
		</label>
		<br /><br />
		
		<div class="patches alert alert-warning" style="display:block">
			Thanks to some changes to formKey management on the magento.com/download page the direct download of the patches doesn't work anymore...<br /><br />
			I'm thinking about a way to make it work again...
		</div>

		<?php foreach ($releases as $release=>$includedpatches): ?>
			<?php $patches = @$release_and_patches[$release] ?>
			<div id="<?= str_replace(".", "_", $release) ?>" class="patches">
				<?php if ($patches): ?>
					<div class="panel panel-danger">
						<div class="panel-heading"><strong>Magento <?= $release ?></strong> needs the following patches</div>
						<div class="panel-body">
							<ul class="list-unstyled">
								<?php foreach ($patches as $patch): ?>
									<li>
										<!--<a target="_blank" href="https://www.magentocommerce.com/products/downloads/magento/downloadFile/file_id/<?= $patch[2] ?>/file_category/<?= $patch[5] ?>/store_id/1/form_key/Ppyy4QcgxlSkYVHi" data-patchid="<?= $patch[2] ?>" data-catid="<?= $patch[5] ?>"><i class="glyphicon glyphicon-download-alt"></i> <strong><?= $patch[4] ?></strong>: <?= $patch[3] ?></a>-->
										<i class="glyphicon glyphicon-download-alt"></i> <strong><?= $patch[4] ?></strong>: <?= $patch[3] ?>
									</li>
								<?php endforeach ?>
							</ul>
						</div>
						<?php if ($includedpatches): ?>
							<div class="panel-footer"><i class="glyphicon glyphicon-info-sign"></i> <?= implode(", ", $includedpatches) ?> are already bundled in the release thus no need to download them separately.</div>
						<?php endif ?>
					</div>
				<?php else: ?>
					<div class="alert alert-success" role="alert">
						No patches needed for your Magento version, great!<br />
						Unless this version is so old that is no more supported...
					</div>
				<?php endif ?>
			</div>
		<?php endforeach ?>
	</div>

	<nav class="navbar navbar-default navbar-fixed-bottom">
	<div class="container-fluid text-center">
		<p class="text-muted credit">
			This page was automatically updated <?= $hoursago ? "$hoursago hours, " : "" ?><?= $minago ?> minutes ago.<br />
			Patches are copyright by <a href="http://magento.com" target="_blank">Magento</a>, this page is by <a href="http://fabrizioballiano.com" target="_blank">Fabrizio Balliano</a>.<br />
			<a href="https://github.com/fballiano/magento-patches-finder" target="_blank">Source code here</a>, if you find any mistake in this list please <a href="mailto:fabrizio@fabrizioballiano.it">drop me a line</a>.
		</p>
	</div>
	</nav>

	<script type="text/javascript" src="https://code.jquery.com/jquery-2.1.4.min.js"></script>
	<script type="text/javascript">
	$(function () {
		$("#releases").change(function () {
			$(".patches").hide();
			if ($("#releases").val()) $("#" + $("#releases").val().replace(/\./g, "_")).slideDown();
		});
	});
	</script>

</body>
</html>