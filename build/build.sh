#!/bin/bash
# build.sh
# script to build a win32 AgilityContest installable distribution
#
# Copyright 2015 by Juan Antonio Martínez <juansgaviota@gmail.com>
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License along
# with this program; if not, write to the Free Software Foundation, Inc.,
# 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA

set -euo pipefail

usage() { echo "Usage: $0 [-X] [-d DOC_DIR] [-C CHRONO_DIR]" 1>&2; exit 1; }

BUILD_DMG=false
DROPBOX=
# TODO: build SerialChronometer from https://github.com/jonsito/AgilityContest_SerialChronometer
CHRONO_DIR=

while getopts ":Xd:C:" option; do
    case "${option}" in
        X) BUILD_DMG=true;;
        d) DROPBOX="$OPTARG";;
        C) CHRONO_DIR="$OPTARG";;
        *) usage;;
    esac
done
shift $((OPTIND-1))

BASE_DIR="$(cd -- "$(dirname "$0")"/.. >/dev/null 2>&1 || exit ; pwd -P)"
BUILD_DIR="${BASE_DIR}/target"
DOWNLOADS="${BASE_DIR}/extra_pkg"
EXE_DIR="${BASE_DIR}/build/launcher"
CONF_DIR="${BASE_DIR}/extras"
NSIS="${BASE_DIR}/build/AgilityContest.nsi"
XAMPP=xampp-portable-win32-7.2.6-0-VC15.zip
# TODO Website is no longer available
# DOC_DIR=/home/jantonio/work/agility/manuals

rm -fr "${BUILD_DIR}"
mkdir -p "${BUILD_DIR}" "${DOWNLOADS}"
cd "${BUILD_DIR}" || exit
# mkdir -p ${DOC_DIR}

# extract version and revision number from ChangeLog
read -r Version AC_VERSION AC_REVISION <<< "$( head -1 "${BASE_DIR}"/ChangeLog )"

#retrieve xampp from server if not exists
if [ ! -f "${DOWNLOADS}/${XAMPP}" ]; then
    echo "Download xampp from server ..."
    if ! wget --no-check-certificate -O "${DOWNLOADS}/${XAMPP}" http://sourceforge.net/projects/xampp/files/XAMPP%20Windows/7.2.6/${XAMPP}; then
        echo "Cannot download xampp. Aborting"
        exit 1
    fi
fi
echo "Extracting xampp ... "
unzip -q "${DOWNLOADS}/${XAMPP}"

# personalize xampp files
# notice that relocation will be done at nsi install time with "setup_xampp.bat"
echo "Setting up apache.conf ..."
cat <<__EOF >>xampp/apache/conf/httpd.conf
<IfModule mpm_winnt_module>
    ThreadStackSize 8388608
</IfModule>
Include "conf/extra/AgilityContest_apache2.conf"
__EOF
sed -i 's/www.example.com/localhost/' xampp/apache/conf/httpd.conf
unix2dos xampp/apache/conf/httpd.conf

# create certificates
echo "Creating new Certificate ..."
/bin/bash "${CONF_DIR}"/create_certificate.command certs >/dev/null 2>&1
mv certs/server.csr xampp/apache/conf/ssl.csr/server.csr
mv certs/server.crt xampp/apache/conf/ssl.crt/server.crt
mv certs/server.key xampp/apache/conf/ssl.key/server.key

# add AC config file and remove "/" to use relative paths
echo "Adding AgilityContest config file ..."
cp "${CONF_DIR}"/AgilityContest_apache2.conf xampp/apache/conf/extra
sed -i -e "s|__HTTP_BASEDIR__|C:|g" \
    -e "s|__AC_BASENAME__|AgilityContest|g" \
    -e "s|__AC_WEBNAME__|agility|g" \
    xampp/apache/conf/extra/AgilityContest_apache2.conf

# enable OpenSSL and Locale support into php
echo "Setting up php/php.ini ..."
cp xampp/php/php.ini xampp/php/php.ini.orig
# para php-5.x
sed -i "s/;extension=php_openssl.dll/extension=php_openssl.dll/g" xampp/php/php.ini
sed -i "s/;extension=php_intl.dll/extension=php_intl.dll/g" xampp/php/php.ini
#para php-7.x
sed -i "s/;extension=openssl/extension=openssl/g" xampp/php/php.ini
sed -i "s/;extension=intl/extension=intl/g" xampp/php/php.ini

# add module php_dio (direct i/o) to support serial line (for in-built chrono software)
# dio module is php_version dependent. on update xampp will need to replace
if [ -f "${CONF_DIR}"/php_dio.dll ]; then
    mkdir -p xampp/php/ext
	cp "${CONF_DIR}"/php_dio.dll xampp/php/ext/php_dio.dll
	sed -i "/^extension=php_openssl.dll/i extension=php_dio.dll" xampp/php/php.ini
fi

# fix options for mysql
# notice that in 5.6.20 cannot simply add options at the end, so must provide our own
# personalized copy of my.ini
echo "Setting up mysql/my.ini ..."
cp "${BASE_DIR}"/build/ac_my.ini xampp/mysql/my.ini
unix2dos xampp/mysql/my.ini

# compile AgilityContest.exe
echo "Compiling launcher..."
cp -a "${EXE_DIR}" launcher
make -C launcher AC_VERSION="${AC_VERSION}" AgilityContest.exe
mv launcher/AgilityContest.exe .

# ok. time to add AgilityContest files
echo "Copying AgilityContest files ..."
cp -rH "${BASE_DIR}"/{.htaccess,config,logs} .
cp -rl "${BASE_DIR}"/{index.html,agility,server,applications,extras,COPYING,README.md,Contributors,ChangeLog} .
find \( -name '*.cw.dat' -o -name '*.cw127.php' -o -name '*.mtx.php' \) -delete

mkdir -p SerialChrono
if [ -n "${CHRONO_DIR}" ]; then
    # now add SerialChronometer files
    zipfile=$(find "${CHRONO_DIR}" -name "*zip" 2>/dev/null | tail -1)
    if [ -n "$zipfile" ]; then
        echo "Adding SerialChronometer files ..."
        unzip -q "$zipfile"
    else
        echo "WARNING: cannot find any chronometer compilation zip file in ${CHRONO_DIR}"
        touch SerialChrono/placeholder
    fi
else
    touch SerialChrono/placeholder
fi

# set first install mark and properly edit .htaccess
touch logs/first_install
sed -i -e "s|__HTTP_BASEDIR__|C:|g" \
    -e "s|__AC_BASENAME__|AgilityContest|g" \
    -e "s|__AC_WEBNAME__|agility|g" \
    .htaccess

# create directory for docs (some day...)
mkdir -p docs
if [ -d "${DROPBOX}" ]; then
    echo "Adding a bit of documentation ..."
    for i in ac_despliegue.pdf ReferenciasPegatinas.txt AgilityContest-1000x800.png Tarifas_2017.pdf ac_obs_livestreaming.pdf; do
        cp "${DROPBOX}/${i}" docs
    done
fi
cp -rl "${BASE_DIR}"/README* docs

# TODO Website is no longer available
# if necessary download available documentation from website
# mkdir -p logs/downloads
# cd ${DOC_DIR}
# wget -nv -O- https://www.agilitycontest.es/downloads/ |\
#     grep -e 'href="ac.*\.pdf' |\
#     sed -e 's/^.*href="//g' -e 's/\.pdf.*/.pdf/g' |\
#     while read x; do
#         sleep $(($RANDOM % 5 + 5))  ## to appear gentle and polite
#         wget -N -nv "https://www.agilitycontest.es/downloads/$x"
#     done
# cp ${DOC_DIR}/* logs/downloads

# fix version and revision number in system.ini
sed -i -e "s/.*version_name.*/version_name = \"${AC_VERSION}\"/" -e "s/.*version_date.*/version_date = \"${AC_REVISION}\"/" config/system.ini
sed -i "s/.*master_server.*/master_server = \"\"/" config/system.ini
unix2dos config/*.ini

# invoke makensis
echo "Prepare and execute makensis..."
if [ ! -f "${DOWNLOADS}/AccessControl.zip" ]; then
    wget --no-check-certificate -O "${DOWNLOADS}/AccessControl.zip" https://nsis.sourceforge.io/mediawiki/images/4/4a/AccessControl.zip
fi
unzip "${DOWNLOADS}/AccessControl.zip" Plugins/i386-unicode/AccessControl.dll
sed -e "s/__VERSION__/${AC_VERSION}/g" -e "s/__TIMESTAMP__/${AC_REVISION}/g" "${NSIS}" > AgilityContest.nsi
cp -rl "${BASE_DIR}"/build/{installer.bmp,License.txt,wellcome.bmp,*.nsh} .
makensis AgilityContest.nsi

APP_NAME=AgilityContest-"${AC_VERSION}"-"${AC_REVISION}"
sed "s/__VERSION__/${AC_VERSION}-${AC_REVISION}/g" "${BASE_DIR}"/applications/Eval_md5sum.html > "${APP_NAME}"_md5check.html
md5sum "${APP_NAME}".exe > "${APP_NAME}".md5sums
sed -i "s/__WINFILE__/$(cat "${APP_NAME}".md5sums)/g" "${APP_NAME}"_md5check.html

# prepare dmg image for MAC-OSX
if $BUILD_DMG; then
    # TODO Review this code
    echo "Creating disk image for Mac-OSX"
    mkdir -p AgilityContest-master
    # add build installer and certificate script
    cp extras/{osx_install.command,create_certificate.command} .
    chmod +x -- *.command
    # add .dmg background image
    mkdir -p .background
    cp agility/images/AgilityContest.png .background
    cp -r COPYING License.txt ChangeLog SerialChrono agility config logs applications extras server docs AgilityContest-master
    # restore original .htaccess
    cp "${BASE_DIR}"/.htaccess AgilityContest-master
    # do not include build and web dir in destination zipfile
    zip -q -r AgilityContest-master.zip AgilityContest-master/{SerialChrono,agility,applications,server,extras,logs,config,COPYING,index.html,.htaccess,ChangeLog}
    FILES="osx_install.command create_certificate.command COPYING ChangeLog License.txt AgilityContest-master.zip"
    mkisofs -quiet -A AgilityContest \
        -P jonsito@gmail.com \
        -V "${AC_VERSION}"_"${AC_REVISION}" \
        -J -r -o "${APP_NAME}".dmg \
        -graft-points /.background/=.background \
        "${FILES}"

    # prepare zip file
    mv AgilityContest-master.zip "${APP_NAME}".zip

    # create md5 sum file and html page
    zsum=$(md5sum "${APP_NAME}".zip)
    dsum=$(md5sum "${APP_NAME}".dmg)
    {
        echo "${zsum}"
        echo "${dsum}"
    } >> "${APP_NAME}".md5sums
    sed -i "s/__ZIPFILE__/${zsum}/g" "${APP_NAME}"_md5check.html
    sed -i "s/__MACFILE__/${dsum}/g" "${APP_NAME}"_md5check.html
fi

echo "That's all folks!"
