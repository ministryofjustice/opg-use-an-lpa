resource "tls_private_key" "onelogin_auth_pk" {
  algorithm = "RSA"
  rsa_bits  = 2048
}

resource "tls_private_key" "lpa_data_store_pk" {
  algorithm = "RSA"
  rsa_bits  = 2048
}
