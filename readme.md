Veeam Backup for Microsoft Office 365 Self-Service Web Portal
==================

## About
This web based portal offers Self-Service to tenant admins leveraging the RESTful API service included in Veeam Backup for Microsoft Office 365. This allows them to perform restores to the original location or a download as a plain/zip file.

Every feature is an independent page, therefor it is easy to remove or add Exchange, OneDrive or SharePoint based on your offering.

## Dependencies
Make sure you download dependencies using `composer`. 

For more information on how to install `composer`:
- Linux (https://getcomposer.org/doc/00-intro.md#installation-linux-unix-osx)
- Windows (https://getcomposer.org/doc/00-intro.md#installation-windows)

This project relies on [GuzzleHTTP](https://github.com/guzzle/guzzle), [jQuery](https://jquery.com/), [Font Awesome] (http://fontawesome.com/), [Twitter Bootstrap](http://getbootstrap.com/) and [Flatpickr.js](http://flatpickr.js.org/).

Required to have a webserver running with PHP5 or higher. As an example you can use the following [Linux Ubuntu with Apache guide](https://www.linode.com/docs/web-servers/lamp/install-lamp-stack-on-ubuntu-16-04) or [Windows with IIS guide](https://docs.microsoft.com/en-us/iis/application-frameworks/scenario-build-a-php-website-on-iis/configure-a-php-website-on-iis).

## Installation
### 1. Download and install composer
    curl -sS https://getcomposer.org/installer | /usr/bin/php && /bin/mv -f composer.phar /usr/local/bin/composer

### 2. Clone this repository
    git clone https://github.com/nielsengelen/vbo365-rest.git

### 3. Initialize Composer
    composer install

## Configuration
Modify the config.php file with your Veeam Backup for Office 365 hostname/IP and port (default: 4443). Additionally you can configure a custom title to be shown.

## Usage
Open a webbrowser and go to index.php. From here you can either login as an admin or a tenant.

You should see the following login screen:
![Login form](http://foonet.be/img/VBO-Login.png)

Logged in as an admin:
![Dashboard](http://foonet.be/img/VBO-Dashboard.png)

Exchange view:
![Exchange overview](http://foonet.be/img/VBO-Exchange.png)

OneDrive view:
![OneDrive overview](http://foonet.be/img/VBO-OneDrive.png)

SharePoint view:
![SharePoint overview](http://foonet.be/img/VBO-SharePoint.png)

## About
This serves as an example on how to work with the RESTful API calls and should be tested before using it in production. Feel free to modify and re-use it however many calls are done with default values which can be modified if needed.

## Known issues
* No known issues

**Note:** There is currently no SSL verification due to self signed certificate testing, please change settings 'verify' to true or remove the specific line accordingly in `veeam.class.php`.

## Questions and feature request
Please use the GitHub issue tracker(https://github.com/nielsengelen/vbo365-rest/issues) for any questions or feature requests.

## Distributed under MIT license
Copyright (c) 2018 VeeamHub

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.