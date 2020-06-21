#!/bin/bash

REPO_PATH="/c/wamp/www/mmrpg-world.net.prototype/public_html/content/${1}/"
REPO_GIT=".git/"
REPO_CONFIG=".git/config"
TEMP_CONFIG="/c/wamp/tmp/${1}_config"

echo "================================"
echo "-- FACTORY RESET ${1^^} REPO --"
echo "================================"
echo ""

if test -d  "${REPO_PATH}"; then

    if test -f  "${REPO_PATH}${REPO_CONFIG}"; then

        echo "Switching to to ${REPO_PATH} repo ..."
        echo ""
        cd "${REPO_PATH}"

        echo "Copying ${REPO_CONFIG} to ${TEMP_CONFIG} ..."
        echo ""
        rm -f "${TEMP_CONFIG}"
        cp "${REPO_CONFIG}" "${TEMP_CONFIG}"

        echo "Deleting old ${REPO_GIT} directory ...";
        echo ""
        rm -rf "${REPO_GIT}"

        echo "Initializing new ${REPO_GIT} directory ..."
        echo ""
        git init

        echo ""
        echo "Copying ${TEMP_CONFIG} back to ${REPO_CONFIG} ..."
        echo ""
        rm -f "${REPO_CONFIG}"
        cp "${TEMP_CONFIG}" "${REPO_CONFIG}"

        echo "Re-adding all changes and commiting ...";
        echo ""
        git add .
        git commit -m "Initial commit"

        echo "Force-push the changes as if first commit ..."
        echo ""
        git push -u --force origin master

        echo ""
        echo "DONE!"

    else

        echo "${REPO_PATH}${REPO_CONFIG} does not exist!"
        echo ""
        echo "FAILED!"

    fi

else

    echo "${REPO_PATH} does not exist!"
    echo ""
    echo "FAILED!"

fi