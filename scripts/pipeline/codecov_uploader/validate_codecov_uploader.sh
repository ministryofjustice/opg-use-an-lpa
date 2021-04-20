#!/usr/bin/env bash


curl -s https://codecov.io/env > env;
curl -s https://codecov.io/bash > codecov;
VERSION=$(grep 'VERSION=\"[0-9\.]*\"' codecov | cut -d'"' -f2);
echo $VERSION

for i in 1 256 512
do
  curl -s "https://raw.githubusercontent.com/codecov/codecov-bash/${VERSION}/SHA${i}SUM" > codecov-hashes
  shasum -a $i -c --ignore-missing codecov-hashes ||
  shasum -a $i -c codecov-hashes
done
