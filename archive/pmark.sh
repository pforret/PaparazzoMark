#!/bin/bash

PROG=/Users/pforret/PaparazzoMark/php_version/pmark.php

if [ ! "$1" == "" ] ; then
	php -f "$PROG" "$1"
	exit
fi

TEST=$(cd)/pmark.ini
if [ -f "$TEST" ] ; then
	php -f "$PROG" "$TEST"
	exit
fi
