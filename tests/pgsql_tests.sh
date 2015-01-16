#!/usr/bin/env bash

phpunit.phar -c phpunit_pgsql.xml "$@"
read -p "Press [Enter] key to continue..."
