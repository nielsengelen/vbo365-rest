Self-Service Web Portal for Veeam Backup for Microsoft Office 365
==================

## About
This web based portal offers Self-Service to tenant admins leveraging the RESTful API service included in Veeam Backup for Microsoft Office 365. This allows them to perform restores to either the original or a different location as well as downloading items as a plain/PST/ZIP file.

Every feature act as an independent page, therefor it is easy to remove or add Exchange, OneDrive or SharePoint based on your offering by modifying the navigation bar on top.

## Dependencies
Make sure you download dependencies using `composer`. 

For more information on how to install `composer`:
- Linux (https://getcomposer.org/doc/00-intro.md#installation-linux-unix-osx)
- Windows (https://getcomposer.org/doc/00-intro.md#installation-windows)

This project leverages a mixture HTML, PHP and Javascript. The following libraries are used:
- [Flatpickr.js](http://flatpickr.js.org/)
- [Font Awesome](http://fontawesome.com/)
- [GuzzleHTTP](https://github.com/guzzle/guzzle)
- [jQuery](https://jquery.com/) 
- [SweetAlert2](https://sweetalert2.github.io)
- [Twitter Bootstrap](http://getbootstrap.com/)

It is required to have a webserver running with PHP5 or higher and the mod_rewrite module enabled. The easiest way to do this is leverage a Linux VM with Apache however Windows with IIS should work as well.

As an example you can use the following [Linux Ubuntu with Apache guide](https://www.linode.com/docs/web-servers/lamp/install-lamp-stack-on-ubuntu-16-04) or [Windows with IIS guide](https://docs.microsoft.com/en-us/iis/application-frameworks/scenario-build-a-php-website-on-iis/configure-a-php-website-on-iis).

This portal leverages rewrite rules via .htaccess and therefor mod_rewrite needs to be enabled in Apache. More information on this can be found via [Enabling mod_rewrite for Apache running on Linux Ubuntu](https://www.digitalocean.com/community/tutorials/how-to-rewrite-urls-with-mod_rewrite-for-apache-on-ubuntu-16-04).

For IIS the web.config file is required. More information can be found via [importing the IIS web config](https://docs.microsoft.com/en-us/iis/extensions/url-rewrite-module/creating-rewrite-rules-for-the-url-rewrite-module).

**It is advised to increase or disable the PHP maximum execution time limit.**
This can modified in the php.ini file as described per [changing the maximum execution time limit](https://www.simplified.guide/php/increase-max-execution-time)

## Installation
### 1. Download and install composer
    a. Linux: curl -sS https://getcomposer.org/installer | /usr/bin/php && /bin/mv -f composer.phar /usr/local/bin/composer
	b. Windows: Download and run Composer-Setup.exe from the composer website.

### 2. Clone this repository
    git clone https://github.com/nielsengelen/vbo365-rest.git

	Place these files under the web service root (/var/www/html or c:\Inetpub\wwwroot)
	
### 3. Initialize Composer from the specific folder (/var/www/html or c:\Inetpub\wwwroot)
    composer install


## Configuration
Once composer has finished, open a webbrowser and go to setup.php, this allows you to generate a config file. 

If this doesn't work, modify the original config.php file with your Veeam Backup for Microsoft Office 365 hostname/IP, port (default: 4443) and API version to be used. Additionally, you can configure the custom title to be shown.

**_Remember to enable mod_rewrite as described in the dependencies._**
**_Remove the setup.php file once this is done._**

## Usage
Open a webbrowser and go to index.php. From here you can either login as an admin or a tenant.

You should see the following login screen:
![Login form](http://foonet.be/img/VBOv3-Login.png)

Logged in as an admin:
![Dashboard view](http://foonet.be/img/VBOv3-Dashboard.png)

Exchange view:
![Exchange view](http://foonet.be/img/VBOv3-Exchange.png)

OneDrive view:
![OneDrive view](http://foonet.be/img/VBOv3-OneDrive.png)

SharePoint view:
![SharePoint view](http://foonet.be/img/VBOv3-SharePoint.png)

## About
This serves as an example on how to work with the RESTful API calls and should be tested before using it in production. Feel free to modify and re-use it however many calls are done with default values which can be modified if needed.

## Known issues
* No known issues

**Note:** There is currently no SSL verification due to self signed certificate testing, please change settings 'verify' to true or remove the specific line accordingly in `veeam.class.php`.

## Questions and feature request
Please use the [GitHub issue tracker](https://github.com/nielsengelen/vbo365-rest/issues) for any questions or feature requests.

## Distributed under MIT license
Copyright (c) 2019 nielsengelen

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.