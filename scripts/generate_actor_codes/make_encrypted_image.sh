#!/usr/bin/env bash
set -euo pipefail
function make_encrypted_image() {

  hdiutil create -srcfolder /tmp/$FILENAME/ -fs HFS+ -encryption AES-256 -volname $FILENAME  ~/Documents/$FILENAME.dmg
  rm -r /tmp/$FILENAME
}

function filecheck() {
  if [ ! -f "/tmp/$FILENAME/$FILENAME.txt" ]; then
    echo "file to encrypt is not present, please recreate file"
    exit 1
  fi
}

FILENAME=${1:?}
filecheck
make_encrypted_image ${1:?}
