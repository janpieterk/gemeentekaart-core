#!/bin/bash

# https://stackoverflow.com/a/246128
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"
PARENTDIR="${DIR}/.."

if [[ "$1" ]]
  then
    FILTER=$1
fi

COMM="${PARENTDIR}/vendor/bin/phpunit --bootstrap ${PARENTDIR}/vendor/autoload.php --include-path ${PARENTDIR}"
if [[ $FILTER ]]
  then
    COMM="$COMM --filter $FILTER"
fi
COMM="$COMM ${DIR}/KaartTest.php"

eval $COMM

