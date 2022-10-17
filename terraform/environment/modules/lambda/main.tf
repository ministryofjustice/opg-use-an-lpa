resource "aws_cloudwatch_log_group" "lambda" {
  name = "/aws/lambda/${var.lambda_name}"
}

resource "aws_lambda_function" "lambda_function" {
  function_name = "${var.lambda_name}-${var.environment}"
  image_uri     = var.image_uri
  package_type  = var.package_type
  role          = aws_iam_role.lambda_role.arn
  timeout       = var.timeout
  memory_size   = var.memory
  depends_on    = [aws_cloudwatch_log_group.lambda]

#  vpc_config {
#    subnet_ids         = var.aws_subnet_ids
#    security_group_ids = [data.aws_security_group.lambda_api_ingress.id]
#  }

  tracing_config {
    mode = "Active"
  }

  dynamic "environment" {
    for_each = length(keys(var.environment_variables)) == 0 ? [] : [true]
    content {
      variables = var.environment_variables
    }
  }
}
