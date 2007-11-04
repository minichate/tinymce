<?php
	@set_time_limit(5*60); // 5 minutes execution time

	require_once("includes/ZipFile.class.php");
	require_once("includes/general.php");

	$start = time();

	function getVersion($filename) {
		$data = file_get_contents($filename);
		// Version 3.0.4 (2007-xx-xx)
		preg_match ("/^Version ([0-9xabrc.]+)/", $data, $matches);

		return str_replace(".", "_", $matches[1]);
	}

	// Create development package
	$tinyMCEVer = getVersion('tinymce/changelog.txt');
	$zip =& new ZipFile("tinymce_". $tinyMCEVer ."_dev.zip");
	$zip->open();
	$zip->addDirectory("/tinymce", "tinymce");
	$zip->commit();
	$zip->close();

	// Create production package
	$zip =& new ZipFile("tinymce_". $tinyMCEVer ."_dev.zip");
	$zip->open();
	$zip->deleteDir("/tinymce/examples/testcases");
	$zip->deleteDir("/tinymce/jscripts/tiny_mce/classes");
	$zip->deleteFile("/tinymce/jscripts/tiny_mce/tiny_mce_dev.js");
	$zip->deleteFile("/tinymce/jscripts/tiny_mce/tiny_mce_jquery.js");
	$zip->deleteFile("/tinymce/jscripts/tiny_mce/tiny_mce_prototype.js");
	$zip->deleteFile("/tinymce/JSTrim.config");
	$zip->deleteFile("/tinymce/JSTrim.exe");
	$zip->deleteFile("/tinymce/JSTrim_mono.exe");
	$zip->commit("tinymce_". $tinyMCEVer .".zip");
	$zip->close();

	// Create spellchecker PHP
	$ver = getVersion('tinymce_spellchecker_php/changelog');
	$zip =& new ZipFile("tinymce_spellchecker_php_". $ver .".zip");
 	$zip->open();
	$zip->addDirectory("/spellchecker", "tinymce_spellchecker_php");
	$zip->deleteFile("/spellchecker/JSTrim.config");
	$zip->deleteFile("/spellchecker/JSTrim.exe");
	$zip->deleteFile("/spellchecker/JSTrim_mono.exe");
	$zip->commit();
	$zip->close();

	// Create compressor CFM
	$ver = getVersion('tinymce_compressor_cfm/changelog.txt');
	$zip =& new ZipFile("tinymce_compressor_cfm_". $ver .".zip");
 	$zip->open();
	$zip->addDirectory("/tinymce_compressor_cfm", "tinymce_compressor_cfm");
	$zip->deleteDir("/tinymce_compressor_cfm/src");
	$zip->commit();
	$zip->close();

	// Create compressor JSP
	$ver = getVersion('tinymce_compressor_jsp/changelog.txt');
	$zip =& new ZipFile("tinymce_compressor_jsp_". $ver .".zip");
 	$zip->open();
	$zip->addDirectory("/tinymce_compressor_jsp", "tinymce_compressor_jsp");
	$zip->commit();
	$zip->close();

	// Create compressor NET
	$ver = getVersion('tinymce_compressor_net/changelog.txt');
	$zip =& new ZipFile("tinymce_compressor_net_". $ver .".zip");
 	$zip->open();
	$zip->addDirectory("/tinymce_compressor_net", "tinymce_compressor_net");
	$zip->commit();
	$zip->close();

	// Create compressor PHP
	$ver = getVersion('tinymce_compressor_php/changelog.txt');
	$zip =& new ZipFile("tinymce_compressor_php_". $ver .".zip");
 	$zip->open();
	$zip->addDirectory("/tinymce_compressor_php", "tinymce_compressor_php");
	$zip->commit();
	$zip->close();

	$end = time() - $start;
	echo "Deploy done in " . $end . " sec\n";
?>