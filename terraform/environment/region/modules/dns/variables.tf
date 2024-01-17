variable "zone_id" {
  description = "The zone id of the DNS zone"
  type        = string
}

variable "dns_name" {
  description = "The DNS name of the DNS record"
  type        = string
}

variable "environment_name" {
  description = "The environment name of the DNS record"
  type        = string
}

variable "create_health_check" {
  description = "Create a health check for the DNS record"
  type        = bool
  default     = false
}

variable "create_alarm" {
  description = "Create an alarm for the DNS record's health check"
  type        = bool
  default     = false
}

variable "dns_namespace_env" {
  description = "The environment name of the DNS namespace"
  type        = string
}

variable "loadbalancer" {
  description = "The loadbalancer object to point the DNS record to"
  type = object({
    dns_name = string
    zone_id  = string
  })
}

variable "service_name" {
  description = "The service name of the DNS record"
  type        = string
  default     = ""
}

variable "is_active_region" {
  description = "Should traffic be routed to this region. Used to create a weighted record set"
  type        = bool
  default     = false
}

variable "current_region" {
  description = "The current region"
  type        = string
  default     = "eu-west-1"
}
