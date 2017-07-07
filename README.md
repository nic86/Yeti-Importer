# Yeti-Importer
Command line script for import csv and pdf (barcode code 128)


##Dependencies for Barcode reader (Debian/Ubuntu)

Zbar

ImageMagick

$ apt-get install php5-dev php5-imagick libmagickwand-dev libmagickcore-dev libzbar-dev

Imagick support builds in Ubuntu 14.04 but not on 12.04.

##Build

$ phpize
$ ./configure
$ make test
$ make install
Do not forget to add the module to your php.ini

Please check that whether your zbarcode.so is loaded using :

$ php -m|grep zbarcode
