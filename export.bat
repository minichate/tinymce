@echo off

:: Delete old
rd /Q /s tinymce
rd /Q /s tinymce_spellchecker_php
rd /Q /s tinymce_compressor_cfm
rd /Q /s tinymce_compressor_jsp
rd /Q /s tinymce_compressor_net
rd /Q /s tinymce_compressor_php
rd /Q /s tinymce_package_dotnet
del /Q *.zip

:: Export new
svn export https://tinymce.svn.sourceforge.net/svnroot/tinymce/tinymce/trunk tinymce
svn export https://tinymce.svn.sourceforge.net/svnroot/tinymce/spellchecker_php/trunk tinymce_spellchecker_php
svn export https://tinymce.svn.sourceforge.net/svnroot/tinymce/tinymce_compressor_cfm/trunk tinymce_compressor_cfm
svn export https://tinymce.svn.sourceforge.net/svnroot/tinymce/tinymce_compressor_jsp/trunk tinymce_compressor_jsp
svn export https://tinymce.svn.sourceforge.net/svnroot/tinymce/tinymce_compressor_net/trunk tinymce_compressor_net
svn export https://tinymce.svn.sourceforge.net/svnroot/tinymce/tinymce_compressor_php/trunk tinymce_compressor_php
svn export https://tinymce.svn.sourceforge.net/svnroot/tinymce/tinymce_package_dotnet/trunk tinymce_package_dotnet

:: Compress
php.exe -n compress.php

pause
