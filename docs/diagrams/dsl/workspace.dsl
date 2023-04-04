workspace {

    model {
        !include https://raw.githubusercontent.com/ministryofjustice/opg-technical-guidance/main/dsl/poas/persons.dsl

        group "Use and View an LPA" {
            lpaCaseManagement = softwareSystem "LPA Case Management" "PKA Sirius." "Existing System" {
                lpaCaseManagement_lpaCodesService = container "LPA Codes Service" "Container"
                lpaCaseManagement_siriusCaseManagement = container "Sirius Case Management" "Container" {
                    -> lpaCaseManagement_lpaCodesService "Makes requests to"
                }
                lpaCaseManagement_lpasCollectionService = container "LPAs Collection" "Container" {
                    -> lpaCaseManagement_siriusCaseManagement "Makes requests to"
                }
                lpaCaseManagement_opgDataApiGateway = container "OPG Data Api Gateway" "Container" {
                    -> lpaCaseManagement_lpaCodesService "Makes requests to"
                    -> lpaCaseManagement_lpasCollectionService "Makes requests to"
                }
            }

            !include ualpa.dsl

            donor -> ualpa_useFrontEnd
            attorney -> ualpa_useFrontEnd
            thirdparty -> ualpa_viewFrontEnd
            caseWorker -> ualpa_adminApplication
        }
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
