#
# Integria IMS
#
%define name        IntegriaIMS
%define version     5.0
%define release     161223
%define httpd_name  httpd
# User and Group under which Apache is running
%define httpd_name  apache2
%define httpd_user  wwwrun
%define httpd_group www

# Evaluate PHP version
%define phpver_lt_430 %(out=`rpm -q --queryformat='%{VERSION}' php` 2>&1 >/dev/null || out=0 ; out=`echo $out | tr . : | sed s/://g` ; if [ $out -lt 430 ] ; then out=1 ; else out=0; fi ; echo $out)

Summary:            Integria IMS
Name:               %{name}
Version:            %{version}
Release:            %{release}
License:            GPL
Vendor:             Artica ST <info@artica.es>
Source0:            %{name}-%{version}.tar.gz
URL:                http://www.integriaims.com
Group:              Productivity/Other
Packager:           Sancho Lerena <slerena@artica.es>
Prefix:             /srv/www/htdocs
BuildRoot:          %{_tmppath}/%{name}
BuildArchitectures: noarch
AutoReq:            0
Requires:           apache2
Requires:           php >= 4.3.0
Requires:           php5-gd, php5-json, php5-gettext, curl, php5-ldap, php5-imap
Requires:           php5-mysql, php5-ldap, php5-mbstring, php5, php5-ctype, php5-phar
Requires:           graphviz, xorg-x11-fonts-core
Provides:           %{name}-%{version}

%description
Integria IMS is a management software for SME. It includes a complete approach to project management, CRM, incident management/ticketing, CMDB/Inventory, file distribution, time tracking management, knowledge base, integrated WIKI and Agenda. Integria is a multiuser WEB Application, with an integrated email reporting and notification system. There is a companion Android/iPhone front end app.

%prep
rm -rf $RPM_BUILD_ROOT

%setup -q -n integriaims

%build

%install
rm -rf $RPM_BUILD_ROOT
mkdir -p $RPM_BUILD_ROOT%{prefix}/integria
cp -aRf * $RPM_BUILD_ROOT%{prefix}/integria
if [ -f $RPM_BUILD_ROOT%{prefix}/integria/integria.spec ] ; then
   rm $RPM_BUILD_ROOT%{prefix}/integria/integria.spec
fi

%clean
rm -rf $RPM_BUILD_ROOT

%preun

# Upgrading

if [ "$1" = "1" ]; then
        exit 0
fi


%post

# Has an install already been done, if so we only want to update the files
# push install.php aside so that the console works immediately using existing
# configuration.
#

# Install crontab each 5 min

echo "01,05,10,15,20,25,30,35,40,45,50,55 * * * * root php /srv/www/htdocs/integria/include/integria_cron.php" > /etc/cron.d/integria

if [ -f %{prefix}/integria/include/config.php ] ; then
   echo "Seems you have already an existing config.php file. Installer has been renamed to install.done"
   mv %{prefix}/integria/install.php %{prefix}/integria/install.done
else
   echo "Please, now, point your browser to http://your_IP_address/integria/install.php and follow all the steps described on it to complete Integria IMS installation"
fi

%files
%defattr(0644,%{httpd_user},%{httpd_group},0755)
%{prefix}/integria
