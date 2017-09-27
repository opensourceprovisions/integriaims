#
# Integria IMS
#
%define name        IntegriaIMS
%define version     5.0
%define release     161223
%define httpd_name  httpd
# User and Group under which Apache is running
%define httpd_user  apache
%define httpd_group apache

Summary:            Integria IMS
Name:               %{name}
Version:            %{version}
Release:            %{release}
License:            GPL2
Vendor:             Artica ST <info@artica.es>
Source0:            %{name}-%{version}.tar.gz
URL:                http://www.integriaims.com
Group:              Productivity/Web/Utilities
Packager:           Sancho Lerena <slerena@artica.es>
Prefix:             /var/www/html
BuildRoot:          %{_tmppath}/%{name}
BuildArchitectures: noarch
AutoReq:            0
Requires:           httpd
Requires:           php >= 5.1.0
Requires:           php, php-cli, php-gd, php-intl, curl, php-ldap, php-imap
Requires:           php-mysql, php-mbstring, php
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
if [ -f $RPM_BUILD_ROOT%{prefix}/integria/integria.centos.spec ] ; then
   rm $RPM_BUILD_ROOT%{prefix}/integria/integria.centos.spec
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

echo "01,05,10,15,20,25,30,35,40,45,50,55 * * * * root php /var/www/html/integria/include/integria_cron.php" > /etc/cron.d/integria

if [ -f %{prefix}/integria/include/config.php ] ; then
   echo "Seems you have already an existing config.php file. Installer has been renamed to install.done"
   mv %{prefix}/integria/install.php %{prefix}/integria/install.done
else
   echo "Please, now, point your browser to http://your_IP_address/integria/install.php and follow all the steps described on it to complete Integria IMS installation"
fi

%files
%defattr(0644,%{httpd_user},%{httpd_group},0755)
%{prefix}/integria
