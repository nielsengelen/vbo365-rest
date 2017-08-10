Veeam Backup for Office 365
==================

Veeam Backup for Office 365 RESTful API demo (Beta)

## Dependencies
Make sure you download dependencies using `composer`. This project relies on [GuzzleHTTP](https://github.com/guzzle/guzzle) and [Twitter Bootstrap](http://getbootstrap.com/).

## Installation
### 1. Download and install composer
    curl -sS https://getcomposer.org/installer | /usr/bin/php && /bin/mv -f composer.phar /usr/local/bin/composer

### 2. Clone this repository
    git clone https://github.com/nielsengelen/vbo.git

### 3. Initialize Composer
    composer install

## Configuration
Modify the config.php file with your username, password and RESTful URI and port (default: 4443).

## Usage
Open a webbrowser and go to index.php 

## Known issues
* Restore session can't be stopped via RESTful API call due to bug in the beta.

**Note:** There is currently no SSL verification due to beta and self signed certificate testing, please change settings 'verify' to true or remove it accordingly in `__construct()` in `veeam.class.php`.
