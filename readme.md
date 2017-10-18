Veeam Backup for Office 365
==================

Veeam Backup for Office 365 RESTful API demo

## Dependencies
Required to have a webserver running with PHP5 or higher. As an example you can use the following [Ubuntu guide](https://www.linode.com/docs/web-servers/lamp/install-lamp-stack-on-ubuntu-16-04).

Make sure you download dependencies using `composer`. This project relies on [GuzzleHTTP](https://github.com/guzzle/guzzle), [jQuery](https://jquery.com/), [Twitter Bootstrap](http://getbootstrap.com/) and [Bootbox.js](http://bootboxjs.com/).

## Installation
### 1. Download and install composer
    curl -sS https://getcomposer.org/installer | /usr/bin/php && /bin/mv -f composer.phar /usr/local/bin/composer

### 2. Clone this repository
    git clone https://github.com/nielsengelen/vbo365-rest.git

### 3. Initialize Composer
    composer install

## Configuration
Modify the config.php file with your Veeam Backup for Office 365 hostname/IP and port (default: 4443).

## Usage
Open a webbrowser and go to index.php

You should see something like:
![Login form](http://foonet.be/img/VBO-REST01.png)

And once logged in it looks like:
![Dashboard](http://foonet.be/img/VBO-REST02.png)

Example of an organization mailbox overview:
![Mailbox overview](http://foonet.be/img/VBO-REST03.png)

## About
This serves as an example on how to work with the RESTful API calls and shouldn't be used in production. Feel free to modify and re-use it however many calls are done with default values which can be modified if needed.

## Known issues
* When performing item restore via the full admin view it is required to terminate the session via the button 'End item restore' or the session will keep running in the background.

**Note:** There is currently no SSL verification due to self signed certificate testing, please change settings 'verify' to true or remove the specific line accordingly in `veeam.class.php`.


## Distributed under MIT license
Copyright (c) 2017 VeeamHub

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.