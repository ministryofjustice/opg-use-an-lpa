#!/usr/bin/env bash
set -euxo pipefail

function make_encrypted_image() {
  date=$(date '+%Y%m%d')
  folder=/tmp/codes-${date}

  mkdir -p $folder
  echo $1 > ${folder}/codes.txt

  hdiutil create -fs HFS+ -encryption AES-256 -srcfolder ${folder} -volname codes-${date} ${2}/codes-${date}.dmg

  rm -R /tmp/codes-${date}
}

read JSON
DEFAULTOUT="${HOME}/Desktop"
OUT=${1:-$DEFAULTOUT}

make_encrypted_image "$JSON" $OUT
