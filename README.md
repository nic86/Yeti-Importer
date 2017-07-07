# Yeti-Importer
Command line script for import csv and pdf (barcode code 128)


----------------------------------------

Install dependency:

composer: https://getcomposer.org/download/

$ php composer.phar install

--------------------------------

Create your custom import profile:

default path for csv profile: profile/csv

default path for pdf profile: profile/pdf

--------------------------------

Upload your files to import:

upload path construction method: upload/json_var_id/json_var_proflie

----------------------------------------

Start .csv import:

Command for start process all json profile (default path: profile/csv):

$ php import_csv.php

----------------------------------------

Start .pdf import:

Command for start process all json profile (default path: profile/pdf):

$ php import_pdf.php



------------------------------------
Install Dependencies for Barcode reader (Debian/Ubuntu)

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

https://github.com/mkoppanen/php-zbarcode
