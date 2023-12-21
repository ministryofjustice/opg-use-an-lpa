#!/bin/sh
# Set secrets in Secrets Manager
awslocal secretsmanager create-secret --name gov_uk_onelogin_identity_public_key \
    --region "eu-west-1" \
    --description "Local development public key" \
    --secret-string file:///public_key.pem

awslocal secretsmanager create-secret --name gov_uk_onelogin_identity_private_key \
    --region "eu-west-1" \
    --description "Local development private key" \
    --secret-string file:///private_key.pem

awslocal secretsmanager create-secret --name gov_uk_onelogin_userinfo_public_key \
    --region "eu-west-1" \
    --description "Local development user info public key" \
    --secret-string file:///onelogin_mock_public_key.pem
