## Requirements

No requirements.

## Providers

| Name | Version |
|------|---------|
| aws  | n/a     |

## Modules

No Modules.

## Resources

| Name                                                                                                                                     |
|------------------------------------------------------------------------------------------------------------------------------------------|
| [aws_iam_policy_document](https://registry.terraform.io/providers/hashicorp/aws/latest/docs/data-sources/iam_policy_document)            |
| [aws_iam_role](https://registry.terraform.io/providers/hashicorp/aws/latest/docs/resources/iam_role)                                     |
| [aws_iam_role_policy](https://registry.terraform.io/providers/hashicorp/aws/latest/docs/resources/iam_role_policy)                       |
| [aws_iam_role_policy_attachment](https://registry.terraform.io/providers/hashicorp/aws/latest/docs/resources/iam_role_policy_attachment) |
| [aws_lambda_function](https://registry.terraform.io/providers/hashicorp/aws/latest/docs/resources/lambda_function)                       |

## Inputs

| Name                                                                                                                                                    | Description                                                                                                             | Type           | Default   | Required |
|---------------------------------------------------------------------------------------------------------------------------------------------------------|-------------------------------------------------------------------------------------------------------------------------|----------------|-----------|:--------:|
| <a name="input_aws_cloudwatch_log_group_kms_key_id"></a> [aws\_cloudwatch\_log\_group\_kms\_key\_id](#input\_aws\_cloudwatch\_log\_group\_kms\_key\_id) | The ARN of the KMS Key to use when encrypting log data.                                                                 | `string`       | n/a       |   yes    |
| <a name="input_command"></a> [command](#input\_command)                                                                                                 | The CMD for the docker image.                                                                                           | `list(string)` | `null`    |    no    |
| <a name="input_description"></a> [description](#input\_description)                                                                                     | Description of your Lambda Function (or Layer)                                                                          | `string`       | `null`    |    no    |
| <a name="input_ecr_arn"></a> [ecr\_arn](#input\_ecr\_arn)                                                                                               | The ARN for the ECR Repository                                                                                          | `string`       | `null`    |    no    |
| <a name="input_entry_point"></a> [entry\_point](#input\_entry\_point)                                                                                   | The ENTRYPOINT for the docker image.                                                                                    | `list(string)` | `null`    |    no    |
| <a name="input_environment_variables"></a> [environment\_variables](#input\_environment\_variables)                                                     | A map that defines environment variables for the Lambda Function.                                                       | `map(string)`  | `{}`      |    no    |
| <a name="input_image_uri"></a> [image\_uri](#input\_image\_uri)                                                                                         | The URI for the container image to use                                                                                  | `string`       | `null`    |    no    |
| <a name="input_lambda_name"></a> [lambda\_name](#input\_lambda\_name)                                                                                   | A unique name for your Lambda Function                                                                                  | `string`       | n/a       |   yes    |
| <a name="input_lambda_role_policy_document"></a> [lambda\_role\_policy\_document](#input\_lambda\_role\_policy\_document)                               | The policy JSON for the lambda IAM role. This policy JSON is merged with Logging and ECR access included in the module. | `string`       | `null`    |    no    |
| <a name="input_package_type"></a> [package\_type](#input\_package\_type)                                                                                | The Lambda deployment package type.                                                                                     | `string`       | `"Image"` |    no    |
| <a name="input_timeout"></a> [timeout](#input\_timeout)                                                                                                 | The amount of time your Lambda Function has to run in seconds.                                                          | `number`       | `3`       |    no    |
| <a name="input_working_directory"></a> [working\_directory](#input\_working\_directory)                                                                 | The working directory for the docker image.                                                                             | `string`       | `null`    |    no    |

## Outputs

No output.
