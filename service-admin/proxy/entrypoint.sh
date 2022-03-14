#!/bin/sh

echo "Generating keypair..."
openssl ecparam -name prime256v1 -genkey -noout -out key.pem
openssl ec -in key.pem -pubout > pub-key.pem

echo "Running proxy..."
exec "$@"
