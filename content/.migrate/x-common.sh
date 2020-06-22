#!/bin/bash

CURRENT_PATH="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )/"
CONTENT_PATH="$(dirname "${CURRENT_PATH}")/"
BASE_PATH="$(dirname "${CONTENT_PATH}")/"
GITHUB_BASE="https://github.com/AdrianMarceau/"

declare -a REPO_KINDS=(
    "types"
    "battles"
    "players"
    "robots"
    "abilities"
    "items"
    "fields"
    )