ualpa_useSoftwareSystem = softwareSystem "Use an LPA Service" "Generates UIDs and stores donor details." {
    ualpa_useSoftwareSystem_database = container "Database" "Stores LPA UIDs." "DynamoDB" "Database"
    ualpa_useSoftwareSystem_lambda = container "Lambda" "Executes code for generating and returning new LPA UID" "AWS Lambda, Go" "Component" {
        -> ualpa_useSoftwareSystem_database "Queries and writes to"
    }
    ualpa_useSoftwareSystem_apiGateway = container "API Gateway" "Provides a REST API for communication to the service." "AWS API Gateway v2, OpenAPI" "Component" {
        -> ualpa_useSoftwareSystem_lambda "Forwards requests to and Returns responses from"
    }
}
