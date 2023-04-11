ualpa_SoftwareSystem = softwareSystem "Use A Lasting Power of Attorney" "Allows LPA Actors to retrieve and share LPAs with People and Organisations interested in LPAs" {
    ualpa_applicationDatabase = container "Application Database" "Stores generated code, user accounts, Sirius LPA IDs, and LPA/Actor codes." "DynamoDB" "Database"
    ualpa_cloudwatchServerless = container "AWS Cloudwatch" "Logging and observability." "AWS Cloudwatch" "Database"
    ualpa_bruteForceDetection = container "Brute Force Cache" "Brute force protection." "AWS Elasticache (Redis)" "Database"
    ualpa_apiLayer = container "API Layer" "Provides features to generate sharing codes, and look up LPA data from Sirius." "PHP" "Container" {

        ualpa_apiLayer_webRouter = component "Web Router" "Routes traffic." "PHP" "Component"
        ualpa_apiLayer_domainLayer = component "Domain Layer" "Manages domain specific logic." "PHP" "Component"
        ualpa_apiLayer_securityLayer = component "Security Layer" "TBC." "PHP" "Component"
        ualpa_apiLayer_dataLayer = component "Data Layer" "TBC." "PHP" "Component"

        ualpa_apiLayer_webRouter -> ualpa_apiLayer_domainLayer
        ualpa_apiLayer_domainLayer -> ualpa_apiLayer_securityLayer
        ualpa_apiLayer_domainLayer -> ualpa_apiLayer_dataLayer

        ualpa_apiLayer_domainLayer -> lpaCaseManagement_opgDataApiGateway "Makes REST requests to"
        ualpa_apiLayer_dataLayer -> ualpa_applicationDatabase "Connects to"
    }
    ualpa_viewFrontEnd = container "View an LPA Frontend" "Provides features to view LPAs shared by LPA Actors." "PHP, CSS, JS, TWIG" "Container" {

        ualpa_viewFrontEnd_webRouter = component "Web Router" "Routes traffic." "PHP" "Component"
        ualpa_viewFrontEnd_staticAssets = component "Static Assets" "Contains static assets." "CSS, JS" "Component"
        ualpa_viewFrontEnd_viewLayer = component "View Layer" "Serves correct template and content." "PHP, Twig" "Component"
        ualpa_viewFrontEnd_sessionLayer = component "Session Layer" "Maintains session state." "PHP" "Component"
        ualpa_viewFrontEnd_domainLayer = component "Domain Layer" "Manages domain specific logic." "PHP" "Component"

        ualpa_viewFrontEnd_webRouter -> ualpa_viewFrontEnd_viewLayer
        ualpa_viewFrontEnd_viewLayer -> ualpa_viewFrontEnd_domainLayer
        ualpa_viewFrontEnd_domainLayer -> ualpa_viewFrontEnd_sessionLayer
        ualpa_viewFrontEnd_viewLayer -> ualpa_viewFrontEnd_staticAssets
        ualpa_viewFrontEnd_domainLayer -> ualpa_bruteForceDetection

        ualpa_viewFrontEnd_domainLayer -> ualpa_apiLayer_webRouter "Makes requests to"
    }
    ualpa_useFrontEnd = container "Use an LPA Frontend" "Provides features to retrieve LPAs and generate codes for sharing LPAs." "PHP, CSS, JS, TWIG" "Container" {

        ualpa_useFrontEnd_webRouter = component "Web Router" "Routes traffic." "PHP" "Component"
        ualpa_useFrontEnd_staticAssets = component "Static Assets" "Contains static assets." "CSS, JS" "Component"
        ualpa_useFrontEnd_viewLayer = component "View Layer" "Serves correct template and content." "PHP, Twig" "Component"
        ualpa_useFrontEnd_sessionLayer = component "Session Layer" "Maintains session state." "PHP" "Component"
        ualpa_useFrontEnd_domainLayer = component "Domain Layer" "Manages domain specific logic." "PHP" "Component"

        ualpa_useFrontEnd_webRouter -> ualpa_useFrontEnd_viewLayer
        ualpa_useFrontEnd_viewLayer -> ualpa_useFrontEnd_domainLayer
        ualpa_useFrontEnd_domainLayer -> ualpa_useFrontEnd_sessionLayer
        ualpa_useFrontEnd_viewLayer -> ualpa_useFrontEnd_staticAssets
        ualpa_useFrontEnd_domainLayer -> ualpa_bruteForceDetection

        ualpa_useFrontEnd_domainLayer -> ualpa_apiLayer_webRouter "Makes requests to"
    }

    ualpa_adminApplication = container "Admin Application" "Provides search and stats features for service team users." "Go, CSS, JS, TWIG" "Container" {

        ualpa_adminApplication_webRouter = component "Web Router" "Routes traffic." "Go" "Component"
        ualpa_adminApplication_staticAssets = component "Static Assets" "Contains static assets." "CSS, JS" "Component"
        ualpa_adminApplication_viewLayer = component "View Layer" "Serves correct template and content." "Go" "Component"
        ualpa_adminApplication_sessionLayer = component "Session Layer" "Maintains session state." "Go" "Component"
        ualpa_adminApplication_domainLayer = component "Domain Layer" "Manages domain specific logic." "Go" "Component"

        ualpa_adminApplication_webRouter -> ualpa_adminApplication_viewLayer
        ualpa_adminApplication_viewLayer -> ualpa_adminApplication_domainLayer
        ualpa_adminApplication_domainLayer -> ualpa_adminApplication_sessionLayer
        ualpa_adminApplication_viewLayer -> ualpa_adminApplication_staticAssets

        ualpa_adminApplication_domainLayer -> ualpa_applicationDatabase "Makes requests to"
        ualpa_adminApplication_domainLayer -> ualpa_cloudwatchServerless "Makes requests to"
    }
}
