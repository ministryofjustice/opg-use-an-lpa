output "ecs_task_roles" {
  description = "The ECS task roles"
  value = {
    admin_task_role  = aws_iam_role.admin_task_role
    api_task_role    = aws_iam_role.api_task_role
    use_task_role  = aws_iam_role.use_task_role
    viewer_task_role = aws_iam_role.viewer_task_role
    pdf_task_role    = aws_iam_role.pdf_task_role
  }
}

output "ecs_execution_role" {
  description = "The ECS execution role"
  value       = aws_iam_role.execution_role
}