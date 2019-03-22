data "aws_iam_policy_document" "viewer_loadbalancer" {
  statement {
    sid = "accessLogBucketAccess"

    resources = [
      "${aws_s3_bucket.access_log.arn}",
      "${aws_s3_bucket.access_log.arn}/*",
    ]

    effect  = "Allow"
    actions = ["s3:PutObject"]

    principals {
      // AWS docs, Account ID that corresponds to the region for your load balancer and bucket.
      // https://docs.aws.amazon.com/elasticloadbalancing/latest/application/load-balancer-access-logs.html
      identifiers = ["652711504416"]

      type = "AWS"
    }
  }
}

resource "aws_s3_bucket" "access_log" {
  bucket = "opg-use-an-lpa-${terraform.workspace}-lb-access-log"
  acl    = "private"
  tags   = "${local.default_tags}"

  server_side_encryption_configuration {
    "rule" {
      "apply_server_side_encryption_by_default" {
        sse_algorithm = "aws:kms"
      }
    }
  }
}

resource "aws_s3_bucket_policy" "access_log" {
  bucket = "${aws_s3_bucket.access_log.id}"
  policy = "${data.aws_iam_policy_document.viewer_loadbalancer.json}"
}
