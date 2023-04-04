ualpa_viewSoftwareSystem = softwareSystem "View an LPA Service" "Generates UIDs and stores donor details." {
    ualpa_viewSoftwareSystem_database = container "Database" "Stores LPA UIDs." "DynamoDB" "Database"
    ualpa_viewSoftwareSystem_lambda = container "Lambda" "Executes code for generating and returning new LPA UID" "AWS Lambda, Go" "Component" {
        -> ualpa_viewSoftwareSystem_database "Queries and writes to"
    }
    ualpa_viewSoftwareSystem_apiGateway = container "API Gateway" "Provides a REST API for communication to the service." "AWS API Gateway v2, OpenAPI" "Component" {
        -> ualpa_viewSoftwareSystem_lambda "Forwards requests to and Returns responses from"
    }
}
