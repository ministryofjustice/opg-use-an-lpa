ualpa_SoftwareSystem = softwareSystem "Use A Lasting Power of Attorney" "Allows LPA Actors to retrieve and share LPAs with People and Organisations interested in LPAs" {
    ualpa_applicationDatabase = container "Application Database" "Stores generated code, user accounts, Sirius LPA IDs, and LPA/Actor codes." "DynamoDB" "Database"
    ualpa_cloudwatchServerless = container "AWS Cloudwatch" "Logging and observability." "AWS Cloudwatch" "Database"
    ualpa_apiLayer = container "API Layer" "Provides features to generate sharing codes, and look up LPA data from Sirius." "PHP" "Container" {
        -> ualpa_applicationDatabase "Connects to"
        -> lpaCaseManagement_opgDataApiGateway "Makes REST requests to"
    }
    ualpa_viewFrontEnd = container "View an LPA Frontend" "Provides features to view LPAs shared by LPA Actors." "PHP, CSS, JS, TWIG" "Container" {
        -> ualpa_apiLayer "Makes requests to"
    }
    ualpa_useFrontEnd = container "Use an LPA Frontend" "Provides features to retrieve LPAs and generate codes for sharing LPAs." "PHP, CSS, JS, TWIG" "Container" {
        -> ualpa_apiLayer "Makes requests to"
    }

    ualpa_adminApplication = container "Admin Application" "Provides search and stats features for service team users." "Go, CSS, JS, TWIG" "Container" {
        -> ualpa_applicationDatabase "Makes requests to"
        -> ualpa_cloudwatchServerless "Makes requests to"
    }
}
