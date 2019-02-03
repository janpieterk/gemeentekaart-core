#!/bin/bash

# https://stackoverflow.com/a/246128
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"
PARENTDIR="${DIR}/.."

${PARENTDIR}/vendor/bin/phpunit --bootstrap ${PARENTDIR}/vendor/autoload.php --include-path ${PARENTDIR} ${DIR}/KaartTest.php

