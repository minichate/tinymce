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
	$zip->deleteFile("/tinymce/examples/translate.html");
	$zip->deleteFile("/tinymce/jscripts/tiny_mce/tiny_mce_dev.js");
	$zip->deleteFile("/tinymce/jscripts/tiny_mce/tiny_mce_jquery.js");
	$zip->deleteFile("/tinymce/jscripts/tiny_mce/tiny_mce_prototype.js");
	$zip->deleteDir("/tinymce/jscripts/tiny_mce/plugins/spellchecker");
	$zip->deleteFile("/tinymce/JSTrim.config");
	$zip->deleteFile("/tinymce/JSTrim.exe");
	$zip->deleteFile("/tinymce/JSTrim_mono.exe");
	$zip->commit("tinymce_". $tinyMCEVer .".zip");
	$zip->close();

	// Create spellchecker PHP
	$ver = getVersion('tinymce_spellchecker_php/changelog');

	// Replace version in source
	$data = file_get_contents("tinymce/jscripts/tiny_mce/plugins/spellchecker/editor_plugin_src.js");
	$data = str_replace('tinymce.majorVersion + "." + tinymce.minorVersion', '"' . str_replace("_", ".", $ver) . '"', $data);
	$data = str_replace('"{backend}"', "this.url+'/rpc.php'", $data);
	file_put_contents("tinymce_spellchecker_php/editor_plugin_src.js", $data);

	// Replace version in min
	$data = file_get_contents("tinymce/jscripts/tiny_mce/plugins/spellchecker/editor_plugin.js");
	$data = str_replace('tinymce.majorVersion+"."+tinymce.minorVersion', '"' . str_replace("_", ".", $ver) . '"', $data);
	$data = str_replace('"{backend}"', "this.url+'/rpc.php'", $data);
	file_put_contents("tinymce_spellchecker_php/editor_plugin.js", $data);

	$zip =& new ZipFile("tinymce_spellchecker_php_". $ver .".zip");
 	$zip->open();
	$zip->addDirectory("/spellchecker", "tinymce_spellchecker_php");
	$zip->addDirectory("/spellchecker/img", "tinymce/jscripts/tiny_mce/plugins/spellchecker/img");
	$zip->addDirectory("/spellchecker/css", "tinymce/jscripts/tiny_mce/plugins/spellchecker/css");
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

	// Create .NET package
	$ver = getVersion('tinymce_package_dotnet/changelog.txt');
	$zip =& new ZipFile("tinymce_package_dotnet_". $ver .".zip");
 	$zip->open();
	$zip->addDirectory("/tinymce", "tinymce_package_dotnet");
	$zip->addDirectory("/tinymce/js/tiny_mce", "tinymce/jscripts/tiny_mce");
	$zip->deleteDir("/tinymce/js/tiny_mce/classes");
	$zip->deleteFile("/tinymce/js/tiny_mce/tiny_mce_dev.js");
	$zip->deleteFile("/tinymce/js/tiny_mce/tiny_mce_dev.js");
	$zip->deleteFile("/tinymce/js/tiny_mce/tiny_mce_jquery.js");
	$zip->deleteFile("/tinymce/js/tiny_mce/tiny_mce_prototype.js");
	$zip->deleteFile("/tinymce/bin/Moxiecode.TinyMCE.xml");
	$zip->addFile("/tinymce/tinymce_changelog.txt", "tinymce/changelog.txt");
	$zip->commit();
	$zip->close();

	$end = time() - $start;
	echo "Deploy done in " . $end . " sec\n";
?>