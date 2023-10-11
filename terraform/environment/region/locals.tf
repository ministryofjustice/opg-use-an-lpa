locals {
  policy_region_prefix = lower(replace(data.aws_region.current.name, "-", ""))
}