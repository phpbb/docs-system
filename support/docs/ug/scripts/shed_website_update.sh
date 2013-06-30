#!/bin/bash

if [ -f /var/www/phpbb.com/htdocs/community/cache/.update ];
then
	cd /var/www/phpbb.com/htdocs
	svn update > community/cache/.svn_update_result 2>&1

	cat community/cache/.update > community/cache/.update_result
	cat community/cache/.svn_update_result >> community/cache/.update_result
	cat community/cache/.update_result

	rm community/cache/*tpl_*
	# sql_*
	# data_*
	rm community/cache/.update
elif [ -f /var/www/phpbb.com/htdocs/community/cache/.docs_update ];
then
	#
	# This updates the English 3.0 user guide on the site.
	#

	# Path to the user guide section of the site
	path="/var/www/phpbb.com/htdocs/support/docs/ug"

	cd $path/data/3.0/_updater

	echo "Update SVN..."
	svn up

	echo "Removing build directory..."
	rm -rf build

	echo "Moving images folder in place..."
	cp -r ./content/en/images $path/images/3.0

	echo "Building docs..."
	xsltproc --xinclude xsl/olympus_phpbb_dot_com.xsl olympus_doc.xml

	rm -rf $path/data/3.0/en

	/data/phpbb/documentation_update.php

	rm /var/www/phpbb.com/htdocs/community/cache/.docs_update
fi