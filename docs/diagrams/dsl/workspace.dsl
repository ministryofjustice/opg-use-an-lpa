workspace {

    model {
        !include https://raw.githubusercontent.com/ministryofjustice/opg-technical-guidance/main/dsl/poas/persons.dsl

        group "Use and View an LPA" {
            !include ualpa-use.dsl
            !include ualpa-view.dsl

            donor -> ualpa_useSoftwareSystem
            attorney -> ualpa_useSoftwareSystem
            thirdparty -> ualpa_viewSoftwareSystem
        }
    }

    views {

        systemlandscape "SystemLandscape" {
            include *
            autoLayout
        }

        systemContext ualpa_useSoftwareSystem {
            include *
            autoLayout
        }

        systemContext ualpa_viewSoftwareSystem {
            include *
            autoLayout
        }

        container ualpa_useSoftwareSystem {
            include *
            autoLayout
        }

        container ualpa_viewSoftwareSystem {
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
