resource "tls_private_key" "onelogin_auth_pk" {
  algorithm = "RSA"
  rsa_bits  = 2048
}