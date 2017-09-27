#!/bin/bash

# Integria IMS
# ==================================================
# Copyright (c) 2012 Artica Soluciones Tecnologicas
# Please see http://integriaims.com

# This program is free software; you can redistribute it and/or
# modify it under the terms of the  GNU Lesser General Public License
# as published by the Free Software Foundation; version 2

# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.

integria_version=`cat ../integria.spec | grep "define version" | awk '{ print $3 }'`

for param in $@
do
	if [ $param = "-h" -o $param = "--help" ]
	then
		echo "No help, damm, this is easy to use !"
		exit 0
	fi
done

echo "Test if you have all the needed tools to make the packages."
whereis dpkg-deb | cut -d":" -f2 | grep dpkg-deb > /dev/null
if [ $? = 1 ]
then
	echo "No found \"dpkg-deb\" aplication, please install."
	exit 1
else
	echo "Found \"dpkg-debs\"."
fi

whereis dpkg-buildpackage | cut -d":" -f2 | grep dpkg-buildpackage > /dev/null
if [ $? = 1 ]
then
	echo " \"dpkg-buildpackage\" aplication not found, please install."
	exit 1
else
	echo "Found \"dpkg-buildpackage\"."
fi

cd ..

echo "Make a \"temp_package\" temporary dir for job."
mkdir -p temp_package

	mkdir -p temp_package/var/www/integria

	echo "Make directory system tree for package."
	cp -R $(ls | grep -v temp_package | grep -v DEBIAN ) temp_package/var/www/integria
	cp -R DEBIAN temp_package
	find temp_package -name ".svn" | xargs rm -Rf 
	find temp_package -name "*~" | xargs rm -Rf
	rm -Rf temp_package/var/www/integria/*.spec
	chmod 755 -R temp_package/DEBIAN

	# Special configuration file, need to be defined here
	# later will be deleted / updated or backup on upgrade.

	touch temp_package/var/www/integria/include/config.php

	echo "Calculate md5sum for md5sums package control file."
	for item in `find temp_package`
	do
		echo -n "."
		if [ ! -d $item ]
		then
			echo $item | grep "DEBIAN" > /dev/null
			#last command success
			if [ $? -eq 1 ]
			then
				md5=`md5sum $item | cut -d" " -f1`
				
				#delete "temp_package" in the path
				final_path=${item#temp_package}
				echo  $md5" "$final_path >> temp_package/DEBIAN/md5sums
			fi
		fi
	done
	echo "END"

	echo "Make the package \"Integria IMS\"."
	dpkg-deb --build temp_package
	mv temp_package.deb /tmp/IntegriaIMS_$integria_version.deb
	rm -Rf temp_package

echo "Delete the \"temp_package\" temporary dir for job."
rm -Rf temp_package.deb

echo "DONE: Package ready at: /tmp/IntegriaIMS_$integria_version.deb"
