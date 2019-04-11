data "aws_vpc" "default" {
  default = "true"
}

data "aws_s3_bucket" "access_log" {
  bucket = "opg-use-an-lpa-${terraform.workspace}-lb-access-log"
}

data "aws_subnet_ids" "private" {
  vpc_id = "${data.aws_vpc.default.id}"

  tags = {
    Name = "*private*"
  }
}

data "aws_subnet" "private" {
  count = "${length(data.aws_subnet_ids.private.ids)}"
  id    = "${data.aws_subnet_ids.private.ids[count.index]}"
}

data "aws_subnet_ids" "public" {
  vpc_id = "${data.aws_vpc.default.id}"

  tags = {
    Name = "public"
  }
}

data "aws_subnet" "public" {
  count = "${length(data.aws_subnet_ids.public.ids)}"
  id    = "${data.aws_subnet_ids.public.ids[count.index]}"
}

data "aws_cloudwatch_log_group" "use-an-lpa" {
  name = "use-an-lpa"
}
