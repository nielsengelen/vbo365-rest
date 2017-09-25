Veeam Backup for Office 365
==================

Veeam Backup for Office 365 RESTful API demo (Beta)

## Dependencies
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

## About
This serves as an example on how to work with the RESTful API calls and shouldn't be used in production. Feel free to modify and re-use it however many calls are done with default values which can be modified if needed.

## Known issues
* When performing item restore via the full admin view it is required to terminate the session via the button 'End item restore' or the session will keep running in the background.

**Note:** There is currently no SSL verification due to beta and self signed certificate testing, please change settings 'verify' to true or remove it accordingly in `veeam.class.php`.
