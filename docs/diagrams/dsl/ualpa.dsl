ualpa_SoftwareSystem = softwareSystem "Use and View an LPA Service" "TBC" {
    ualpa_applicationDatabase = container "Application Database" "TBC." "DynamoDB" "Database"
    ualpa_cloudwatchServerless = container "AWS Cloudwatch" "TBC." "AWS Cloudwatch" "Database"
    ualpa_apiLayer = container "API Layer" "TBC." "PHP" "Container" {
        -> ualpa_applicationDatabase "Connects to"
        -> lpaCaseManagement_opgDataApiGateway "Makes REST requests to"
    }
    ualpa_viewFrontEnd = container "View an LPA Frontend" "TBC." "PHP, CSS, JS, TWIG" "Container" {
        -> ualpa_apiLayer "Makes requests to"
    }
    ualpa_useFrontEnd = container "Use an LPA Frontend" "TBC." "PHP, CSS, JS, TWIG" "Container" {
        -> ualpa_apiLayer "Makes requests to"
    }

    ualpa_adminApplication = container "Admin Application" "TBC." "Go, CSS, JS, TWIG" "Container" {
        -> ualpa_applicationDatabase "Makes requests to"
        -> ualpa_cloudwatchServerless "Makes requests to"
        -> lpaCaseManagement_opgDataApiGateway "Makes REST requests to"
    }
}
