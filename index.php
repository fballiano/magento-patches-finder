<?php
$errors = [];
$success = [];
function refresh() {
    return file_put_contents('download', file_get_contents('http://magentocommerce.com/download'));
}

function sort_by_patch_name($a, $b) {
	$a = preg_replace(array("/^.*-/", "/\/.*$/"), "", $a[4]);
	$b = preg_replace(array("/^.*-/", "/\/.*$/"), "", $b[4]);
	return strcasecmp($a, $b);
}

require "simple_html_dom.php";
date_default_timezone_set('UTC');
$lastmodification = time() - filemtime("download");
$hoursago = (int)date("H", $lastmodification);
if (0 < $hoursago) {
    if (false === refresh()) {
        $errors[] = 'Could not write "download" file. Please check its permissions!';
    } else {
        $lastmodification = time() - filemtime("download");
        $hoursago = (int)date("H", $lastmodification);
        $success[] = 'Updated Magento download information.';
    }
}
$minago = (int)date("i", $lastmodification);

$releases_patches = array();
$releases = array();
$html = file_get_html('download');
foreach($html->find('.download-releases .release-download') as $downloads) {
	$includedpatches = array();
	$tmp = (string)$downloads->innertext;
	if (preg_match("/(XMLConnect|Database Repair Tool)/", $tmp)) continue;
	preg_match_all("/SUPEE-\d+/", $tmp, $includedpatches);
	foreach ($downloads->find("strong") as $version) {
		if (preg_match("/^ver (.+)/", trim(strip_tags($version->innertext)), $matches)) {
			$version = trim(str_replace("ver ", "", strip_tags($version->innertext)));
                        if (strpos($version, "later") !== false) continue;
			if (substr($version, 0, 1) == 2) continue;
			$releases[$version] = @$includedpatches[0];
		}
	}
}
$releases["1.0"] = array();
krsort($releases);

$patches = array();
foreach($html->find('.download-releases') as $downloads) {
	$title = $downloads->find("h3");
	$title = $title[0]->innertext;
	if (!stripos($title, "patches")) continue;
	
	foreach ($downloads->find(".release-download") as $patch) {
		$i = 0;
		$patch_name = $patch->find("strong")[0]->innertext;
		$patch_name = trim(strip_tags($patch_name));
		$cat_id = explode("_", $patch->find("select")[0]->id);
		$cat_id = $cat_id[1];
		foreach ($patch->find("select option") as $patch_version) {
			if ($i++ == 0) continue;
			preg_match_all("(1\..\..\..|1\..\..)", $patch_version->innertext, $tmp);
			if (!isset($tmp[0][0])) continue;
			$start_version = $tmp[0][0];
			$end_version = isset($tmp[0][1]) ? $tmp[0][1] : $start_version;
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

$patches[] = array(
	"1.9.1.0",
	"1.9.1.999",
	"512",
	"PATCH_SUPEE-4829_EE_1.14.1.0_v1.sh (0.01 MB)",
	"SUPEE-4829",
	"203"
);

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

$projects = [];
if (file_exists('projects.php')) {
    include 'projects.php';
}

function getShops($projects, $release) {
    $shops = [];
    foreach ($projects as $name => $info) {
        if ($info['version'] === $release) {
            $shops[] = $name;
        }
    }
    return $shops;
}

function isRequiredPatch($patch, $release, $projects) {
    foreach ($projects as $project) {
        if ($project['version'] === $release && false === in_array($patch, $project['patches'])) {
            return true;
        }
    }
    return false;
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
	.required {color: red}
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
		<?php if (count($errors)): ?><div class="patches alert alert-danger" style="display:block"><?php echo implode('<br />', $errors) ?></div><?php endif ?>
		<?php if (count($success)): ?><div class="patches alert alert-success" style="display:block"><?php echo implode('<br />', $success) ?></div><?php endif ?>
		<div class="patches alert alert-warning" style="display:block">
			Patches are becoming too many and too big, upgrade your whole Magento installation instead of installing them.
		</div>

		<div class="patches alert alert-warning" style="display:block">
			Magento 2 is not supported by this tool, the information is not provided by Magento itself.
		</div>

		<?php foreach ($releases as $release=>$includedpatches): ?>
			<?php $patches = @$release_and_patches[$release] ?>
			<?php if ($patches) usort($patches, "sort_by_patch_name") ?>
			<?php $shops = getShops($projects, $release) ?>
			<div id="<?= str_replace(".", "_", $release) ?>" class="patches<?php if (count($shops)): ?> project" style="display:block<?php endif ?>">
				<?php if ($patches): ?>
					<div class="panel panel-danger">
						<div class="panel-heading"><strong>Magento <?php echo $release . (count($shops) ? ' (' . implode(', ', $shops) . ')' : '') ?></strong> needs the following patches</div>
						<div class="panel-body">
							<ul class="list-unstyled">
								<?php foreach ($patches as $patch): ?>
									<li>
										<!--<a target="_blank" href="https://www.magentocommerce.com/products/downloads/magento/downloadFile/file_id/<?= $patch[2] ?>/file_category/<?= $patch[5] ?>/store_id/1/form_key/Ppyy4QcgxlSkYVHi" data-patchid="<?= $patch[2] ?>" data-catid="<?= $patch[5] ?>"><i class="glyphicon glyphicon-download-alt"></i> <strong><?= $patch[4] ?></strong>: <?= $patch[3] ?></a>-->
										<i class="glyphicon glyphicon-download-alt"></i>
										<strong class="<?php if (isRequiredPatch($patch[4], $release, $projects)): ?>required<?php endif ?>"><?= $patch[4] ?></strong>: <?= $patch[3] ?>
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
			else $(".patches.project").show();
		});
	});
	</script>

</body>
</html>
