#!/bin/sh
# Set secrets in Secrets Manager
awslocal secretsmanager create-secret --name gov-uk-onelogin-identity-public-key \
    --region "eu-west-1" \
    --description "Local development public key" \
    --secret-string file:///public_key.pem

awslocal secretsmanager create-secret --name gov-uk-onelogin-identity-private-key \
    --region "eu-west-1" \
    --description "Local development private key" \
    --secret-string file:///private_key.pem
