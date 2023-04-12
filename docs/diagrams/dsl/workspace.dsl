workspace {

    model {
        !include https://raw.githubusercontent.com/ministryofjustice/opg-technical-guidance/main/dsl/poas/persons.dsl

        lpaCaseManagement = softwareSystem "LPA Case Management" "PKA Sirius." "Existing System" {
            lpaCaseManagement_lpaCodesService = container "LPA Codes Service" "Manages User Verification" "Software System" "Existing System"
            lpaCaseManagement_siriusCaseManagement = container "Sirius Case Management" "Stores data about LPAs" "Software System" "Existing System" {
                -> lpaCaseManagement_lpaCodesService "Makes requests to"
            }
            lpaCaseManagement_lpasCollectionService = container "LPAs Collection" "Provides LPA data" "Software System" "Existing System" {
                -> lpaCaseManagement_siriusCaseManagement "Makes requests to"
            }
            lpaCaseManagement_opgDataApiGateway = container "OPG Data Api Gateway" "API Gateway" "AWS Service" "Existing System" {
                -> lpaCaseManagement_lpaCodesService "Makes requests to"
                -> lpaCaseManagement_lpasCollectionService "Makes requests to"
            }
        }

        !include ualpa.dsl

        ualpa_adminApplication -> lpaCaseManagement_opgDataApiGateway "Makes REST requests to"
        ualpa_apiLayer -> lpaCaseManagement_opgDataApiGateway "Makes REST requests to"

        donor -> ualpa_useFrontEnd
        attorney -> ualpa_useFrontEnd
        organisation -> ualpa_viewFrontEnd
        caseWorker -> ualpa_adminApplication
    }

    views {

        systemContext ualpa_SoftwareSystem {
            include *
            autoLayout
        }

        container ualpa_SoftwareSystem {
            include *
            autoLayout
        }

        container lpaCaseManagement {
            include *
            autoLayout
        }

        component ualpa_adminApplication {
            include *
            autoLayout
        }

        component ualpa_viewFrontEnd {
            include *
            autoLayout
        }

        component ualpa_useFrontEnd {
            include *
            autoLayout
        }

        component ualpa_apiLayer {
            include *
            autoLayout
        }

        component ualpa_instructionsPreferences {
            include *
            autoLayout
        }

        theme default

        styles {
            element "Existing System" {
                background #999999
                color #ffffff
            }
            element "Web Browser" {
                shape WebBrowser
            }
            element "Database" {
                shape Cylinder
            }
        }
    }
}
